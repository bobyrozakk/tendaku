<?php

namespace App\Services\Booking;

use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BookingReceiptService
{
    public function build(Rental $rental): array
    {
        $payments = $rental->payments;

        $paidPayments = $payments->where('status', 'paid');

        $paidPayment = $paidPayments
            ->sortByDesc('paid_at')
            ->first();

        $latestPayment = $payments
            ->sortByDesc('id')
            ->first();

        $activePayment = $paidPayment ?? $latestPayment;

        $isPaid = $paidPayments->isNotEmpty();

        $days = max(1, (int) $rental->rental_days);

        $vaBanks = [
            'bca' => 'BCA',
            'bni' => 'BNI',
            'bri' => 'BRI',
            'permata' => 'Permata',
            'cimb' => 'CIMB Niaga',
            'mandiri' => 'Mandiri',
        ];

        $channel = strtolower((string) (
            $activePayment?->payment_channel
            ?? $activePayment?->channel
            ?? $activePayment?->method
            ?? ''
        ));

        $bank = strtolower((string) data_get(
            $activePayment?->va_details,
            'bank',
            ''
        ));

        $channelKey = str_replace(
            ['_va', 'va_', 'va'],
            '',
            $channel
        );

        $bankCode = isset($vaBanks[$bank])
            ? $bank
            : (isset($vaBanks[$channelKey]) ? $channelKey : null);

        $bankName = $bankCode ? $vaBanks[$bankCode] : null;

        $paymentMethod = $bankName
            ? ($bankName === 'Mandiri'
                ? 'Mandiri Bill Payment'
                : "{$bankName} Virtual Account")
            : match ($channel) {
                'qris' => 'QRIS Realtime',
                'cash' => 'Tunai di Kasir Vendor',
                default => $activePayment
                    ? 'Pembayaran online'
                    : 'Belum tersedia',
            };

        $statusLabel = match ($rental->status) {
            'pending' => 'Menunggu Pembayaran',
            'cancelled' => 'Pesanan Dibatalkan',
            'expired' => 'Kedaluwarsa',
            default => $isPaid
                ? 'Pembayaran Terverifikasi'
                : 'Menunggu Konfirmasi',
        };

        $subtotal = (float) $rental->subtotal;
        $deliveryFee = (float) $rental->delivery_fee;
        $discount = (float) $rental->discount_amount;

        $adminFee = $rental->adminFee();

        $grandTotal = (float) (
            $rental->final_total ?: $rental->grand_total
        );

        $amountPaid = (float) $paidPayments->sum('amount');
        $balance = max(0, $grandTotal - $amountPaid);

        $items = $rental->details
            ->groupBy('master_item_id')
            ->map(function (Collection $group) use ($days) {
                $first = $group->first();
                $quantity = $group->count();
                $rate = (float) $first->daily_rate_snapshot;

                return [
                    'name' => $first->masterItem?->name
                        ?? 'Peralatan Camping',
                    'category' => $first->masterItem?->category?->name,
                    'quantity' => $quantity,
                    'daily_rate' => $rate,
                    'rental_days' => $days,
                    'subtotal' => $rate * $quantity * $days,
                ];
            })
            ->values();

        $customerName = $rental->isGuest()
            ? ($rental->guest_name ?? 'Penyewa Tamu')
            : ($rental->customer?->name ?? 'Pelanggan');

        $customerPhone = $rental->isGuest()
            ? ($rental->guest_phone ?? '-')
            : ($rental->customer?->phone ?? '-');

        return [
            'rental' => $rental,
            'vendor' => $rental->vendor,

            'customerName' => $customerName,
            'customerPhone' => $customerPhone,

            'pickup' => Carbon::parse($rental->pickup_date),
            'return' => Carbon::parse($rental->return_date),
            'days' => $days,

            'paidPayment' => $paidPayment,
            'latestPayment' => $latestPayment,
            'activePayment' => $activePayment,
            'isPaid' => $isPaid,
            'statusLabel' => $statusLabel,
            'paymentMethod' => $paymentMethod,

            'subtotal' => $subtotal,
            'adminFee' => $adminFee,
            'deliveryFee' => $deliveryFee,
            'discount' => $discount,
            'grandTotal' => $grandTotal,
            'amountPaid' => $amountPaid,
            'balance' => $balance,

            'items' => $items,
            'currency' => fn ($value) =>
                'Rp ' . number_format((float) $value, 0, ',', '.'),
        ];
    }
}
