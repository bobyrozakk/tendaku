<?php

namespace App\Services\Finance;

use App\Models\Payment;
use InvalidArgumentException;

class EscrowService
{
    /**
     * Transisi status escrow yang diizinkan.
     *
     * Status `released` dan `refunded` bersifat final. Refund setelah dana
     * dicairkan diselesaikan lewat adjustment batch, bukan dengan mengubah
     * status escrow historis.
     *
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        'not_applicable' => ['held', 'refunded', 'void'],
        'held' => ['released', 'refunded', 'void'],
        'released' => [],
        'refunded' => [],
        'void' => [],
    ];

    /**
     * Tahan dana pembayaran Midtrans setelah webhook menyatakan lunas.
     */
    public function markHeld(Payment $payment): bool
    {
        if ($payment->payment_method !== 'midtrans_snap' || $payment->status !== 'paid') {
            return false;
        }

        return $this->transition($payment, 'held');
    }

    /**
     * Tandai dana sudah masuk batch pencairan vendor.
     */
    public function markReleased(Payment $payment): bool
    {
        if ($payment->payment_method !== 'midtrans_snap' || $payment->status !== 'paid') {
            return false;
        }

        return $this->transition($payment, 'released');
    }

    /**
     * Tandai dana dikembalikan ke customer.
     */
    public function markRefunded(Payment $payment): bool
    {
        if ($payment->payment_method !== 'midtrans_snap' || $payment->status !== 'refunded') {
            return false;
        }

        return $this->transition($payment, 'refunded');
    }

    /**
     * Tandai pembayaran batal/kedaluwarsa.
     */
    public function markVoid(Payment $payment): bool
    {
        if ($payment->payment_method !== 'midtrans_snap' || ! in_array($payment->status, ['failed', 'expired', 'cancelled'], true)) {
            return false;
        }

        return $this->transition($payment, 'void');
    }

    private function transition(Payment $payment, string $status): bool
    {
        if (! array_key_exists($status, self::TRANSITIONS)) {
            throw new InvalidArgumentException("Unknown escrow status [{$status}].");
        }

        $current = $payment->escrow_status ?? 'not_applicable';

        if ($current === $status) {
            return false;
        }

        if (! in_array($status, self::TRANSITIONS[$current], true)) {
            return false;
        }

        $payment->update(['escrow_status' => $status]);

        return true;
    }
}
