<?php

namespace App\Services\Finance;

use App\Models\Payment;
use App\Models\PlatformCommission;
use App\Models\Settlement;
use App\Models\SettlementItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    public function __construct(
        private readonly EscrowService $escrowService,
    ) {}

    /**
     * Buat draft batch settlement mingguan untuk seluruh vendor yang punya
     * pembayaran/komisi belum masuk batch pada periode tersebut.
     *
     * Idempoten: kombinasi vendor + periode unik, dan hanya pembayaran yang
     * belum punya settlement_id yang diambil.
     *
     * @return Collection<int, Settlement>
     */
    public function buildWeeklyDraft(Carbon $weekStart): Collection
    {
        $periodStart = $weekStart->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $periodEnd = $periodStart->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $payments = Payment::withoutGlobalScope('vendor')
            ->where('status', 'paid')
            ->whereNull('settlement_id')
            ->whereBetween('paid_at', [$periodStart, $periodEnd])
            ->whereIn('payment_type', ['dp', 'settlement', 'fine'])
            ->whereHas('rental', fn ($query) => $query->where('status', 'completed'))
            ->where(function ($query): void {
                $query->where(function ($cash): void {
                    $cash->where('payment_method', 'cash')
                        ->where('payment_type', '!=', 'fine');
                })->orWhere('escrow_status', 'held');
            })
            ->orderBy('id')
            ->get();

        if ($payments->isEmpty()) {
            return collect();
        }

        $commissions = PlatformCommission::withoutGlobalScope('vendor')
            ->whereIn('payment_id', $payments->pluck('id'))
            ->where('status', 'accrued')
            ->get()
            ->keyBy('payment_id');

        $created = collect();

        foreach ($payments->groupBy('vendor_id') as $vendorId => $vendorPayments) {
            $settlement = $this->createDraftForVendor(
                (int) $vendorId,
                $periodStart,
                $periodEnd,
                $vendorPayments,
                $commissions,
            );

            if ($settlement !== null) {
                $created->push($settlement);
            }
        }

        return $created;
    }

    /**
     * Setujui draft: kunci pembayaran & komisi ke batch, tetapi dana escrow
     * baru dilepas saat batch ditandai paid.
     */
    public function approve(Settlement $settlement, User $admin): bool
    {
        return DB::transaction(function () use ($settlement, $admin): bool {
            $settlement = Settlement::withoutGlobalScope('vendor')
                ->lockForUpdate()
                ->find($settlement->getKey());

            if ($settlement === null || ! $settlement->isDraft()) {
                return false;
            }

            $settlement->update([
                'status' => 'approved',
                'approved_by' => $admin->getKey(),
                'approved_at' => now(),
            ]);

            $this->assignItemsToSettlement($settlement, lockAsSettled: true);

            return true;
        });
    }

    /**
     * Tandai batch sudah dibayar: simpan bukti transfer, lepas escrow, dan
     * terbitkan invoice. Hanya batch berstatus approved yang dapat dibayar.
     */
    public function markPaid(
        Settlement $settlement,
        User $admin,
        string $referenceNumber,
        string $proofUrl,
        ?string $notes = null,
    ): ?VendorInvoice {
        return DB::transaction(function () use ($settlement, $referenceNumber, $proofUrl, $notes): ?VendorInvoice {
            $settlement = Settlement::withoutGlobalScope('vendor')
                ->lockForUpdate()
                ->find($settlement->getKey());

            if ($settlement === null || ! $settlement->isApproved()) {
                return null;
            }

            $settlement->update([
                'status' => 'paid',
                'reference_number' => $referenceNumber,
                'proof_url' => $proofUrl,
                'paid_at' => now(),
                'notes' => $notes,
            ]);

            $this->assignItemsToSettlement($settlement, lockAsSettled: true);
            $this->releaseEscrow($settlement);

            return $this->generateInvoice($settlement);
        });
    }

    /**
     * Batalkan draft/approved yang belum dibayar. Pembayaran dan komisi
     * dikembalikan agar bisa masuk batch berikutnya.
     */
    public function cancel(Settlement $settlement): bool
    {
        return DB::transaction(function () use ($settlement): bool {
            $settlement = Settlement::withoutGlobalScope('vendor')
                ->lockForUpdate()
                ->find($settlement->getKey());

            if ($settlement === null || $settlement->isPaid() || $settlement->status === 'cancelled') {
                return false;
            }

            $settlement->update(['status' => 'cancelled']);

            Payment::withoutGlobalScope('vendor')
                ->where('settlement_id', $settlement->getKey())
                ->update(['settlement_id' => null]);

            PlatformCommission::withoutGlobalScope('vendor')
                ->where('settlement_id', $settlement->getKey())
                ->update(['settlement_id' => null, 'status' => 'accrued']);

            return true;
        });
    }

    /**
     * Terbitkan invoice settlement. Idempoten per batch.
     */
    public function generateInvoice(Settlement $settlement): VendorInvoice
    {
        $existing = VendorInvoice::withoutGlobalScope('vendor')
            ->where('settlement_id', $settlement->getKey())
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $prefix = sprintf(
            'INV/%d/%s/',
            $settlement->vendor_id,
            $settlement->period_end->format('Ym'),
        );

        $lastNumber = VendorInvoice::withoutGlobalScope('vendor')
            ->where('number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('number');

        $sequence = $lastNumber === null
            ? 1
            : ((int) substr((string) $lastNumber, strlen($prefix))) + 1;

        $subtotal = (float) $settlement->gross_qris
            + (float) $settlement->gross_fine_qris
            + (float) $settlement->adjustment_amount;

        return VendorInvoice::withoutGlobalScope('vendor')->create([
            'vendor_id' => $settlement->vendor_id,
            'settlement_id' => $settlement->getKey(),
            'number' => $prefix.$sequence,
            'type' => 'settlement',
            'period_start' => $settlement->period_start,
            'period_end' => $settlement->period_end,
            'subtotal' => round($subtotal, 2),
            'commission_amount' => $settlement->commission_total,
            'fee_amount' => round((float) $settlement->fine_fee_total, 2),
            'total' => $settlement->net_payout,
            'status' => 'issued',
            'issued_at' => now(),
            'paid_at' => $settlement->paid_at,
        ]);
    }

    /**
     * @param  Collection<int, Payment>  $payments
     * @param  Collection<int, PlatformCommission>  $commissions
     */
    private function createDraftForVendor(
        int $vendorId,
        Carbon $periodStart,
        Carbon $periodEnd,
        Collection $payments,
        Collection $commissions,
    ): ?Settlement {
        $alreadyExists = Settlement::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->whereDate('period_start', $periodStart->toDateString())
            ->whereDate('period_end', $periodEnd->toDateString())
            ->exists();

        if ($alreadyExists) {
            return null;
        }

        $vendor = Vendor::query()->with('paymentSetting')->find($vendorId);

        if ($vendor === null) {
            return null;
        }

        $previous = Settlement::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where('period_end', '<', $periodStart->toDateString())
            ->whereIn('status', ['draft', 'approved', 'paid'])
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->first();

        $adjustment = $previous !== null && (float) $previous->net_payout < 0
            ? (float) $previous->net_payout
            : 0.0;

        $grossQris = 0.0;
        $grossFineQris = 0.0;
        $commissionTotal = 0.0;
        $gatewayFeeTotal = 0.0;
        $fineFeeTotal = 0.0;
        $cashCommissionOffset = 0.0;
        $items = [];
        $hasNonCashPayment = $payments->contains(fn (Payment $payment): bool => $payment->payment_method !== 'cash');

        foreach ($payments as $payment) {
            $commission = $commissions->get($payment->getKey());
            $commissionAmount = (float) ($commission?->amount ?? 0);
            $feeAmount = (float) $payment->gateway_fee_amount;
            $isFine = $payment->payment_type === 'fine';
            $isCash = $payment->payment_method === 'cash';

            if ($isFine && $isCash) {
                // Denda tunai sudah diterima vendor; tidak masuk remitansi platform.
                continue;
            }

            if ($isFine) {
                $grossFineQris += (float) $payment->amount;
                $fineFeeTotal += $feeAmount;

                $items[] = [
                    'payment_id' => $payment->getKey(),
                    'platform_commission_id' => null,
                    'type' => 'fine',
                    'gross_amount' => $payment->amount,
                    'commission_amount' => 0,
                    'fee_amount' => $feeAmount,
                    'net_amount' => round((float) $payment->amount - $feeAmount, 2),
                ];

                continue;
            }

            if ($isCash) {
                $commissionTotal += $commissionAmount;
                $cashCommissionOffset += $commissionAmount;

                $items[] = [
                    'payment_id' => $payment->getKey(),
                    'platform_commission_id' => $commission?->getKey(),
                    'type' => 'cash_commission',
                    'gross_amount' => $payment->amount,
                    'commission_amount' => $commissionAmount,
                    'fee_amount' => 0,
                    'net_amount' => -$commissionAmount,
                ];

                continue;
            }

            $grossQris += (float) $payment->amount;
            $commissionTotal += $commissionAmount;
            $gatewayFeeTotal += $feeAmount;

            $items[] = [
                'payment_id' => $payment->getKey(),
                'platform_commission_id' => $commission?->getKey(),
                'type' => 'payment',
                'gross_amount' => $payment->amount,
                'commission_amount' => $commissionAmount,
                'fee_amount' => $feeAmount,
                'net_amount' => round((float) $payment->amount - $commissionAmount, 2),
            ];
        }

        if (! $hasNonCashPayment && $commissionTotal <= 0 && $adjustment === 0.0) {
            return null;
        }

        if ($adjustment !== 0.0) {
            $items[] = [
                'payment_id' => null,
                'platform_commission_id' => null,
                'type' => 'adjustment',
                'gross_amount' => 0,
                'commission_amount' => 0,
                'fee_amount' => 0,
                'net_amount' => round($adjustment, 2),
            ];
        }

        $transferFee = $hasNonCashPayment
            ? (float) config('monetization.settlement.transfer_fee', 3500)
            : 0.0;

        // Biaya transfer adalah beban platform (PRD §4.4), bukan potongan hak vendor.
        $netPayout = round(
            $grossQris + $grossFineQris - $commissionTotal - $fineFeeTotal + $adjustment,
            2,
        );

        return DB::transaction(function () use (
            $vendorId,
            $vendor,
            $periodStart,
            $periodEnd,
            $items,
            $grossQris,
            $grossFineQris,
            $commissionTotal,
            $gatewayFeeTotal,
            $fineFeeTotal,
            $cashCommissionOffset,
            $transferFee,
            $adjustment,
            $netPayout,
            $previous,
        ): Settlement {
            $settlement = Settlement::withoutGlobalScope('vendor')->create([
                'vendor_id' => $vendorId,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'status' => 'draft',
                'gross_qris' => round($grossQris, 2),
                'gross_fine_qris' => round($grossFineQris, 2),
                'commission_total' => round($commissionTotal, 2),
                'gateway_fee_total' => round($gatewayFeeTotal, 2),
                'fine_fee_total' => round($fineFeeTotal, 2),
                'cash_commission_offset' => round($cashCommissionOffset, 2),
                'transfer_fee' => round($transferFee, 2),
                'adjustment_amount' => round($adjustment, 2),
                'net_payout' => $netPayout,
                'bank_name' => $vendor->paymentSetting?->bank_name,
                'bank_account_number' => $vendor->paymentSetting?->bank_account_number,
                'bank_account_name' => $vendor->paymentSetting?->bank_account_name,
                'notes' => $previous !== null && $adjustment !== 0.0
                    ? 'Carry-forward saldo negatif dari settlement #'.$previous->getKey()
                    : null,
            ]);

            foreach ($items as $item) {
                SettlementItem::withoutGlobalScope('vendor')->create(array_merge($item, [
                    'settlement_id' => $settlement->getKey(),
                    'vendor_id' => $vendorId,
                ]));
            }

            return $settlement;
        });
    }

    private function assignItemsToSettlement(Settlement $settlement, bool $lockAsSettled): void
    {
        $items = SettlementItem::withoutGlobalScope('vendor')
            ->where('settlement_id', $settlement->getKey())
            ->get();

        $paymentIds = $items->pluck('payment_id')->filter()->unique()->values();
        $commissionIds = $items->pluck('platform_commission_id')->filter()->unique()->values();

        if ($paymentIds->isNotEmpty()) {
            Payment::withoutGlobalScope('vendor')
                ->whereIn('id', $paymentIds)
                ->update(['settlement_id' => $settlement->getKey()]);
        }

        if ($commissionIds->isNotEmpty()) {
            $attributes = ['settlement_id' => $settlement->getKey()];

            if ($lockAsSettled) {
                $attributes['status'] = 'settled';
            }

            PlatformCommission::withoutGlobalScope('vendor')
                ->whereIn('id', $commissionIds)
                ->update($attributes);
        }
    }

    private function releaseEscrow(Settlement $settlement): void
    {
        Payment::withoutGlobalScope('vendor')
            ->where('settlement_id', $settlement->getKey())
            ->where('payment_method', 'midtrans_snap')
            ->where('escrow_status', 'held')
            ->get()
            ->each(fn (Payment $payment) => $this->escrowService->markReleased($payment));
    }
}
