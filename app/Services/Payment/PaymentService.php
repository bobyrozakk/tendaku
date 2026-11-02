<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Payment;
use App\Models\Rental;
use App\Services\Finance\CommissionService;
use InvalidArgumentException;
use RuntimeException;

class PaymentService
{
    public function __construct(
        private readonly CommissionService $commissions,
    ) {}

    //  persen DP dari grand_total.
    public function defaultDpPercentage(): int
    {
        return (int) config('payment.dp_percentage', 50);
    }

    //  Nominal DP, dibulatkan ke atas agar selalu bilangan bulat rupiah.
    public function calculateDpAmount(int $grandTotal): int
    {
        return (int) ceil($grandTotal * $this->defaultDpPercentage() / 100);
    }

    //  order_id Midtrans unik berprefix TDK.
    //  Digit tipe: full=1, dp=2, remaining=3, fine=4, lainnya=9.
    public function generateOrderId(string $bookingCode, string $paymentType, int $attempt = 1): string
    {
        $cleanCode = str_replace(['TDK-', 'TDK'], '', strtoupper($bookingCode));

        $datePart = substr($cleanCode, 0, 8);
        $seqPart = substr($cleanCode, 8);

        $typeNumber = match ($paymentType) {
            PaymentType::Full->value => '1',
            PaymentType::Dp->value => '2',
            PaymentType::Remaining->value => '3',
            PaymentType::Fine->value => '4',
            default => '9',
        };

        return "TDK{$datePart}{$typeNumber}{$seqPart}-{$attempt}";
    }

    // ── Pembayaran online (Midtrans) ──────────────────────────────────────────

    /**
     * Payment pending untuk bayar penuh, DP, atau pelunasan.
     * Nominal dihitung di server, tidak dari input browser.
     *
     * @param  'full'|'dp'|'remaining'  $paymentType
     */
    public function createOnlinePayment(
        Rental $rental,
        string $paymentType,
        int $attempt = 1,
        ?string $method = null
    ): Payment {
        if (! in_array($paymentType, PaymentType::onlinePaymentValues(), true)) {
            throw new InvalidArgumentException("payment_type harus 'full', 'dp', atau 'remaining' untuk pembayaran online.");
        }

        $grandTotal = (int) $rental->grand_total;
        $amount = match ($paymentType) {
            PaymentType::Dp->value => $this->calculateDpAmount($grandTotal),
            PaymentType::Remaining->value => $this->remainingAmountOrFail($rental),
            default => $grandTotal,
        };

        if ($amount <= 0) {
            throw new RuntimeException('Nominal pembayaran harus lebih dari 0.');
        }

        if ($paymentType === PaymentType::Dp->value) {
            $rental->update(['dp_amount' => $amount]);
        }

        return $this->createMidtransPayment($rental, $paymentType, $amount, $attempt, [
            'payment_channel' => $method,
            'method' => $method,
            'expires_at' => $rental->expires_at,
        ]);
    }

    //  Payment pending untuk pelunasan sisa via Midtrans.
    public function createRemainingPayment(Rental $rental, int $attempt = 1): Payment
    {
        return $this->createOnlinePayment($rental, PaymentType::Remaining->value, $attempt);
    }

    //  Payment pending untuk denda via Midtrans.
    public function createOnlineFinePayment(Rental $rental, int $fineAmount, int $attempt = 1): Payment
    {
        $this->assertFineAmount($fineAmount);

        return $this->createMidtransPayment($rental, PaymentType::Fine->value, $fineAmount, $attempt);
    }

    // ── Pembayaran tunai (kasir)

    public function createCashRemainingPayment(Rental $rental, int $receivedBy): Payment
    {
        $payment = $this->createCashPayment(
            $rental, PaymentType::Remaining->value, $this->remainingAmountOrFail($rental), $receivedBy
        );

        $this->commissions->accrueForPayment($payment);

        return $payment;
    }

    /**
     * Denda tunai. Langsung ke vendor, tidak ikut payout.
     */
    public function createCashFinePayment(Rental $rental, int $fineAmount, int $receivedBy): Payment
    {
        $this->assertFineAmount($fineAmount);

        return $this->createCashPayment(
            $rental, PaymentType::Fine->value, $fineAmount, $receivedBy
        );
    }

    /**
     * Sisa tagihan = grand_total - total pembayaran sewa yang sudah paid.
     */
    public function calculateRemainingAmount(Rental $rental): int
    {
        $paidSoFar = (int) $rental->payments()
            ->where('status', PaymentStatus::Paid->value)
            ->whereIn('payment_type', PaymentType::rentalValues())
            ->sum('amount');

        return max(0, (int) $rental->grand_total - $paidSoFar);
    }

    /**
     * Transisi status payment dan simpan (lihat PaymentStatus).
     *
     * @return bool true jika status berubah, false jika diabaikan (idempotent)
     */
    public function advanceStatus(Payment $payment, string $newStatus): bool
    {
        if (! $payment->transitionTo(PaymentStatus::from($newStatus))) {
            return false;
        }

        $payment->save();

        return true;
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $extra  atribut tambahan (mis. method, expires_at)
     */
    private function createMidtransPayment(
        Rental $rental,
        string $paymentType,
        int $amount,
        int $attempt,
        array $extra = []
    ): Payment {
        return $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => $paymentType,
            'channel' => 'midtrans',
            'amount' => $amount,
            'order_id' => $this->generateOrderId($rental->booking_code, $paymentType, $attempt),
            // Kolom legacy dipertahankan untuk audit, tetapi tidak lagi dipakai
            // sebagai potongan. Komisi resmi dihitung CommissionService.
            'platform_fee' => 0,
            'status' => PaymentStatus::Pending->value,
        ] + $extra);
    }

    //  Tunai langsung lunas setelah kasir konfirmasi; tidak kena platform fee
    private function createCashPayment(Rental $rental, string $paymentType, int $amount, int $receivedBy): Payment
    {
        return $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => $paymentType,
            'payment_method' => 'cash',
            'payment_channel' => null,
            'channel' => 'cash',
            'method' => 'cash',
            'amount' => $amount,
            'platform_fee' => 0,
            'escrow_status' => 'not_applicable',
            'status' => PaymentStatus::Paid->value,
            'paid_at' => now(),
            'received_by' => $receivedBy,
        ]);
    }

    private function remainingAmountOrFail(Rental $rental): int
    {
        $remaining = $this->calculateRemainingAmount($rental);

        if ($remaining <= 0) {
            throw new RuntimeException('Tidak ada sisa yang perlu dilunasi.');
        }

        return $remaining;
    }

    private function assertFineAmount(int $fineAmount): void
    {
        if ($fineAmount <= 0) {
            throw new InvalidArgumentException('Nominal denda harus lebih dari 0.');
        }
    }
}
