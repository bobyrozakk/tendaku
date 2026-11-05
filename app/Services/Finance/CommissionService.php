<?php

namespace App\Services\Finance;

use App\Enums\PaymentType;
use App\Models\Payment;
use App\Models\PlatformCommission;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    public function __construct(
        private readonly CommissionCalculator $calculator,
    ) {}

    /**
     * Catat komisi untuk pembayaran sewa yang sudah lunas.
     *
     * Komisi dihitung per rental, bukan per pembayaran: pembayaran pertama
     * memakai tarif/minimum, lalu pelunasan hanya menambah selisih sampai
     * total komisi setara max(5% x total pembayaran rental, minimum).
     *
     * Idempoten: satu pembayaran hanya boleh menghasilkan satu baris komisi.
     */
    public function accrueForPayment(Payment $payment): ?PlatformCommission
    {
        if (! $this->isCommissionable($payment)) {
            return null;
        }

        return DB::transaction(function () use ($payment): ?PlatformCommission {
            $existing = PlatformCommission::withoutGlobalScope('vendor')
                ->where('payment_id', $payment->getKey())
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $rate = $this->calculator->rateFor($this->vendorRate($payment));

            $alreadyAccrued = (float) PlatformCommission::withoutGlobalScope('vendor')
                ->where('rental_id', $payment->rental_id)
                ->where('status', '!=', 'void')
                ->sum('amount');

            $totalRentalPaid = (float) Payment::withoutGlobalScope('vendor')
                ->where('rental_id', $payment->rental_id)
                ->where('status', 'paid')
                ->whereIn('payment_type', PaymentType::rentalValues())
                ->sum('amount');

            $amount = $this->calculator->incrementalCommissionFor(
                totalRentalPaid: $totalRentalPaid,
                alreadyAccrued: $alreadyAccrued,
                vendorRate: $rate,
            );

            if ($amount <= 0) {
                return null;
            }

            return PlatformCommission::withoutGlobalScope('vendor')->create([
                'payment_id' => $payment->getKey(),
                'rental_id' => $payment->rental_id,
                'vendor_id' => $payment->vendor_id,
                'base_amount' => $payment->amount,
                'rate' => $rate,
                'amount' => $amount,
                'status' => 'accrued',
                'recognized_at' => $payment->paid_at ?? now(),
            ]);
        });
    }

    /**
     * Void komisi yang belum masuk batch settlement.
     *
     * Komisi yang sudah settled tidak diubah; refund setelah settlement
     * diselesaikan lewat adjustment pada batch berikutnya.
     */
    public function voidForPayment(Payment $payment): bool
    {
        $commission = PlatformCommission::withoutGlobalScope('vendor')
            ->where('payment_id', $payment->getKey())
            ->first();

        if ($commission === null || $commission->status !== 'accrued') {
            return false;
        }

        $commission->update([
            'status' => 'void',
            'voided_at' => now(),
        ]);

        return true;
    }

    public function isCommissionable(Payment $payment): bool
    {
        return $payment->status === 'paid'
            && in_array($payment->payment_type, CommissionCalculator::COMMISSIONABLE_PAYMENT_TYPES, true)
            && (float) $payment->amount > 0;
    }

    /**
     * Tarif efektif vendor; null berarti memakai tarif global dari config.
     */
    private function vendorRate(Payment $payment): ?float
    {
        $vendorRate = $payment->vendor?->commission_rate;

        return $vendorRate === null ? null : (float) $vendorRate;
    }
}
