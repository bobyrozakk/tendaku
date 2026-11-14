<?php

namespace App\Services\Finance;

use App\Enums\PaymentType;
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
     * Periode mengikuti tanggal rental selesai (`completed_at`), bukan
     * `paid_at`. Pembayaran yang belum sempat masuk batch sebelumnya ikut
     * diambil sebagai catch-up selama rental selesai pada/sebelum periode ini.
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
            ->whereIn('payment_type', PaymentType::values())
            ->whereHas('rental', fn ($query) => $query
                ->where('status', 'completed')
                ->where(function ($completed) use ($periodEnd): void {
                    // completed_at null = data lama; tetap ikut catch-up.
                    $completed->whereNull('completed_at')
                        ->orWhere('completed_at', '<=', $periodEnd);
                }))
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
                $periodStart,
            );

            if ($settlement !== null) {
                $created->push($settlement);
            }
        }

        return $created;
    }

    /**
     * Hitung ringkasan saldo yang benar-benar dipegang platform untuk vendor.
     *
     * Pembayaran tunai tidak masuk gross karena uangnya sudah diterima vendor;
     * komisi dari transaksi tunai tetap mengurangi saldo dan dapat membuat
     * estimated_net negatif. Saldo negatif akan dibawa ke batch berikutnya.
     *
     * @return array{
     *     eligible_count: int,
     *     gross_total: float,
     *     estimated_commission: float,
     *     cash_commission_offset: float,
     *     adjustment_total: float,
     *     estimated_net: float
     * }
     */
    public function getWithdrawableSummary(int $vendorId): array
    {
        $payments = $this->eligiblePaymentsForVendor($vendorId);
        $commissions = $this->accruedCommissionsFor($payments);
        $summary = $this->summarizeSettlementPayments($payments, $commissions);
        $adjustment = $this->outstandingNegativeBalance($vendorId)['amount'];
        $netPayout = $this->calculateNetPayout(
            $summary['gross_qris'],
            $summary['gross_fine_qris'],
            $summary['commission_total'],
            $adjustment,
        );

        return [
            'eligible_count' => $payments->count(),
            'gross_total' => round($summary['gross_qris'] + $summary['gross_fine_qris'], 2),
            'estimated_commission' => round($summary['commission_total'], 2),
            'cash_commission_offset' => round($summary['cash_commission_offset'], 2),
            'adjustment_total' => round($adjustment, 2),
            'estimated_net' => $netPayout,
        ];
    }

    /**
     * Vendor mengajukan permohonan penarikan dana / pencairan (on-demand settlement).
     */
    public function requestPayoutForVendor(int $vendorId): Settlement
    {
        $vendor = Vendor::query()->with('paymentSetting')->findOrFail($vendorId);
        $setting = $vendor->paymentSetting;

        if (blank($setting?->bank_name) || blank($setting?->bank_account_number) || blank($setting?->bank_account_name)) {
            throw new \InvalidArgumentException('Silakan lengkapi informasi rekening bank Anda terlebih dahulu di menu Pengaturan Rekening sebelum mengajukan pencairan.');
        }

        $activeSettlement = Settlement::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->whereIn('status', ['draft', 'approved'])
            ->first();

        if ($activeSettlement !== null) {
            throw new \RuntimeException('Anda sudah memiliki pengajuan pencairan yang sedang diproses (Batch #'.$activeSettlement->id.'). Tunggu hingga proses transfer selesai.');
        }

        $payments = $this->eligiblePaymentsForVendor($vendorId);

        if ($payments->isEmpty()) {
            throw new \RuntimeException('Tidak ada saldo pembayaran sewa selesai yang siap dicairkan saat ini.');
        }

        $commissions = $this->accruedCommissionsFor($payments);
        [$pStart, $pEnd] = $this->resolvePayoutPeriod($vendorId);

        $summary = $this->summarizeSettlementPayments($payments, $commissions);
        $adjustment = $this->outstandingNegativeBalance($vendorId)['amount'];
        $netPayout = $this->calculateNetPayout(
            $summary['gross_qris'],
            $summary['gross_fine_qris'],
            $summary['commission_total'],
            $adjustment,
        );

        if ($netPayout <= 0) {
            $deficit = 'Rp '.number_format(abs($netPayout), 0, ',', '.');

            throw new \RuntimeException($netPayout < 0
                ? "Saldo bersih Anda masih negatif ({$deficit}). Kekurangan ini akan dipotong dari pencairan berikutnya; pengajuan pencairan baru dapat dilakukan setelah saldo kembali positif."
                : 'Belum ada saldo bersih yang dapat dicairkan saat ini.');
        }

        $settlement = $this->createDraftForVendor(
            $vendorId,
            $pStart,
            $pEnd,
            $payments,
            $commissions,
        );

        if ($settlement === null) {
            throw new \RuntimeException('Gagal membuat pengajuan pencairan. Silakan coba beberapa saat lagi.');
        }

        return $settlement;
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

            // Invoice diterbitkan sebelum transfer agar vendor menerima nota
            // pencairan lebih dulu. Escrow tetap ditahan sampai markPaid().
            $this->generateInvoice($settlement);

            return true;
        });
    }

    /**
     * Tandai batch sudah dibayar: simpan bukti transfer, lepas escrow, dan
     * ubah invoice menjadi paid. Hanya batch approved yang dapat dibayar.
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

            return $this->markInvoicePaid($settlement);
        });
    }

    /**
     * Tandai invoice batch sebagai paid, atau buat invoice legacy bila batch
     * lama belum memilikinya.
     */
    public function markInvoicePaid(Settlement $settlement, ?string $notes = null): VendorInvoice
    {
        $invoice = $this->generateInvoice($settlement);

        $invoice->update([
            'status' => 'paid',
            'paid_at' => $settlement->paid_at ?? now(),
            'notes' => $notes ?? $invoice->notes,
        ]);

        return $invoice->refresh();
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

            VendorInvoice::withoutGlobalScope('vendor')
                ->where('settlement_id', $settlement->getKey())
                ->where('status', '!=', 'paid')
                ->update(['status' => 'void']);

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

        $isPaid = $settlement->isPaid();

        return VendorInvoice::withoutGlobalScope('vendor')->create([
            'vendor_id' => $settlement->vendor_id,
            'settlement_id' => $settlement->getKey(),
            'number' => $prefix.$sequence,
            'type' => 'settlement',
            'period_start' => $settlement->period_start,
            'period_end' => $settlement->period_end,
            'subtotal' => round($subtotal, 2),
            'commission_amount' => $settlement->commission_total,
            'fee_amount' => 0,
            'total' => $settlement->net_payout,
            'status' => $isPaid ? 'paid' : 'issued',
            'issued_at' => now(),
            'paid_at' => $isPaid ? $settlement->paid_at : null,
        ]);
    }

    /**
     * @return Collection<int, Payment>
     */
    private function eligiblePaymentsForVendor(int $vendorId): Collection
    {
        return Payment::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->where('status', 'paid')
            ->whereNull('settlement_id')
            ->whereIn('payment_type', PaymentType::values())
            ->whereHas('rental', fn ($query) => $query->where('status', 'completed'))
            ->where(function ($query): void {
                $query->where(function ($cash): void {
                    $cash->where('payment_method', 'cash')
                        ->where('payment_type', '!=', 'fine');
                })->orWhere('escrow_status', 'held');
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, Payment>  $payments
     * @return Collection<int, PlatformCommission>
     */
    private function accruedCommissionsFor(Collection $payments): Collection
    {
        if ($payments->isEmpty()) {
            return collect();
        }

        return PlatformCommission::withoutGlobalScope('vendor')
            ->whereIn('payment_id', $payments->pluck('id'))
            ->where('status', 'accrued')
            ->get()
            ->keyBy('payment_id');
    }

    /**
     * Ringkas komponen settlement dari pembayaran yang sama untuk semua jalur.
     *
     * @param  Collection<int, Payment>  $payments
     * @param  Collection<int, PlatformCommission>  $commissions
     * @return array{
     *     gross_qris: float,
     *     gross_fine_qris: float,
     *     commission_total: float,
     *     cash_commission_offset: float,
     *     has_non_cash_payment: bool,
     *     items: list<array<string, mixed>>
     * }
     */
    private function summarizeSettlementPayments(Collection $payments, Collection $commissions): array
    {
        $summary = [
            'gross_qris' => 0.0,
            'gross_fine_qris' => 0.0,
            'commission_total' => 0.0,
            'cash_commission_offset' => 0.0,
            'has_non_cash_payment' => false,
            'items' => [],
        ];

        foreach ($payments as $payment) {
            $commission = $commissions->get($payment->getKey());
            $commissionAmount = (float) ($commission?->amount ?? 0);
            $isFine = $payment->payment_type === PaymentType::Fine->value;
            $isCash = $payment->payment_method === 'cash';

            if ($isFine && $isCash) {
                // Denda tunai sudah diterima vendor; tidak masuk remitansi platform.
                continue;
            }

            if ($isFine) {
                $summary['gross_fine_qris'] += (float) $payment->amount;
                $summary['has_non_cash_payment'] = true;

                $summary['items'][] = [
                    'payment_id' => $payment->getKey(),
                    'platform_commission_id' => null,
                    'type' => 'fine',
                    'gross_amount' => $payment->amount,
                    'commission_amount' => 0,
                    'fee_amount' => 0,
                    'net_amount' => round((float) $payment->amount, 2),
                ];

                continue;
            }

            if ($isCash) {
                $summary['commission_total'] += $commissionAmount;
                $summary['cash_commission_offset'] += $commissionAmount;

                $summary['items'][] = [
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

            $summary['gross_qris'] += (float) $payment->amount;
            $summary['commission_total'] += $commissionAmount;
            $summary['has_non_cash_payment'] = true;

            $summary['items'][] = [
                'payment_id' => $payment->getKey(),
                'platform_commission_id' => $commission?->getKey(),
                'type' => 'payment',
                'gross_amount' => $payment->amount,
                'commission_amount' => $commissionAmount,
                'fee_amount' => 0,
                'net_amount' => round((float) $payment->amount - $commissionAmount, 2),
            ];
        }

        return $summary;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePayoutPeriod(int $vendorId): array
    {
        $periodStart = Carbon::today()->startOfWeek(Carbon::MONDAY);
        $periodEnd = Carbon::today()->endOfWeek(Carbon::SUNDAY);

        $weeklySettlementExists = Settlement::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->whereDate('period_start', $periodStart->toDateString())
            ->whereDate('period_end', $periodEnd->toDateString())
            ->exists();

        if ($weeklySettlementExists) {
            return [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()];
        }

        return [$periodStart, $periodEnd];
    }

    /**
     * Saldo negatif terbaru yang belum tertutup oleh batch positif setelahnya.
     *
     * @return array{amount: float, settlement: ?Settlement}
     */
    private function outstandingNegativeBalance(int $vendorId, ?Carbon $before = null): array
    {
        $query = Settlement::withoutGlobalScope('vendor')
            ->where('vendor_id', $vendorId)
            ->whereIn('status', ['draft', 'approved', 'paid']);

        if ($before !== null) {
            $query->where('period_end', '<', $before->toDateString());
        }

        $settlement = $query
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->first();

        if ($settlement === null || (float) $settlement->net_payout >= 0) {
            return ['amount' => 0.0, 'settlement' => null];
        }

        return [
            'amount' => (float) $settlement->net_payout,
            'settlement' => $settlement,
        ];
    }

    private function calculateNetPayout(
        float $grossQris,
        float $grossFineQris,
        float $commissionTotal,
        float $adjustment,
    ): float {
        return round(
            $grossQris + $grossFineQris - $commissionTotal + $adjustment,
            2,
        );
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
        ?Carbon $carryForwardBefore = null,
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

        $carryForward = $this->outstandingNegativeBalance($vendorId, $carryForwardBefore);
        $adjustment = $carryForward['amount'];
        $previous = $carryForward['settlement'];

        $summary = $this->summarizeSettlementPayments($payments, $commissions);
        $grossQris = $summary['gross_qris'];
        $grossFineQris = $summary['gross_fine_qris'];
        $commissionTotal = $summary['commission_total'];
        $cashCommissionOffset = $summary['cash_commission_offset'];
        $hasNonCashPayment = $summary['has_non_cash_payment'];
        $items = $summary['items'];

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
        $netPayout = $this->calculateNetPayout(
            $grossQris,
            $grossFineQris,
            $commissionTotal,
            $adjustment,
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
                'gateway_fee_total' => 0,
                'fine_fee_total' => 0,
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
