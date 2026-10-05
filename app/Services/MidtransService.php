<?php

namespace App\Services;

use App\Exceptions\InvalidMidtransSignatureException;
use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    private const ORDER_ID_PREFIX = 'TENDA-INV-';

    public function __construct()
    {
        // TODO(Role 2 + PM): This currently uses platform credentials from .env.
        // Switch to the payment vendor's encrypted credentials if vendor-owned accounts are required.
        $serverKey = config('services.midtrans.server_key');
        if (! is_string($serverKey) || $serverKey === '') {
            throw new LogicException('The Midtrans server key is not configured.');
        }

        Config::$serverKey = $serverKey;
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = config('services.midtrans.is_sanitized');
        Config::$is3ds = config('services.midtrans.is_3ds');
    }

    public function createTransaction(Payment $payment): string
    {
        if (! $payment->exists || ! $payment->getKey()) {
            throw new InvalidArgumentException('A saved payment is required to create a Midtrans transaction.');
        }

        if ((float) $payment->amount <= 0 || floor((float) $payment->amount) !== (float) $payment->amount) {
            throw new InvalidArgumentException('The payment amount must be a positive whole number.');
        }

        $orderId = $payment->midtrans_order_id;
        if ($orderId === null) {
            $orderId = self::generateOrderId((int) $payment->getKey());
            $payment->midtrans_order_id = $orderId;
            $payment->save();
        }

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $payment->amount,
            ],
        ];

        return Snap::getSnapToken($params);
    }

    /**
     * Verify and apply a Midtrans payment notification.
     *
     * @param  array<string, string>  $notification
     */
    public function handleNotification(array $notification): Payment
    {
        $serverKey = config('services.midtrans.server_key');
        $expectedSignature = hash(
            'sha512',
            $notification['order_id'].$notification['status_code'].$notification['gross_amount'].$serverKey,
        );

        if (! hash_equals($expectedSignature, $notification['signature_key'])) {
            throw new InvalidMidtransSignatureException;
        }

        return DB::transaction(function () use ($notification): Payment {
            $payment = Payment::query()
                ->where('midtrans_order_id', $notification['order_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $paymentAmount = self::normalizeAmount((string) $payment->amount);
            $notificationAmount = self::normalizeAmount((string) $notification['gross_amount']);

            if ($paymentAmount === null || $notificationAmount === null || $paymentAmount !== $notificationAmount) {
                throw new InvalidArgumentException('The notification amount does not match the payment amount.');
            }

            $payment->midtrans_transaction_id = $notification['transaction_id'] ?? $payment->midtrans_transaction_id;
            $payment->midtrans_status = $notification['transaction_status'];

            // Midtrans "settlement" means paid; card "capture" is paid only when fraud_status is accept.
            if (in_array($notification['transaction_status'], ['settlement', 'capture'], true)
                && ($notification['transaction_status'] !== 'capture' || ($notification['fraud_status'] ?? null) === 'accept')) {
                if ($payment->status !== 'refunded') {
                    $payment->status = 'paid';
                    $payment->paid_at ??= now();
                }
            } elseif (in_array($notification['transaction_status'], ['deny', 'cancel', 'expire'], true)
                && $payment->status === 'pending') {
                $payment->status = 'failed';
            } elseif ($notification['transaction_status'] === 'capture'
                && ($notification['fraud_status'] ?? null) === 'deny'
                && $payment->status === 'pending') {
                $payment->status = 'failed';
            } elseif ($notification['transaction_status'] === 'refund' && $payment->status === 'paid') {
                $payment->status = 'refunded';
            }

            $payment->save();

            if ($payment->status === 'paid' && $payment->payment_type === 'settlement') {
                // TODO(Role 1 + PM): Define what to do if payment settles after its rental was cancelled or expired.
                Rental::query()
                    ->whereKey($payment->rental_id)
                    ->where('status', 'pending')
                    ->update(['status' => 'confirmed']);
            }

            // TODO(Role 1 + PM): Define rental state handling for refunds and partial refunds before enabling refund flows.
            return $payment;
        });
    }

    public static function generateOrderId(int $paymentId): string
    {
        if ($paymentId < 1) {
            throw new InvalidArgumentException('The payment ID must be a positive integer.');
        }

        return sprintf('%s%03d', self::ORDER_ID_PREFIX, $paymentId);
    }

    private static function normalizeAmount(string $amount): ?string
    {
        if (! preg_match('/\A\d+(?:\.\d{1,2})?\z/', $amount)) {
            return null;
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return (ltrim($whole, '0') ?: '0').'.'.str_pad($fraction, 2, '0');
    }
}
