<?php

namespace App\Services\Booking;

use App\Models\MasterItem;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Payment\PaymentService;
use Carbon\Carbon;

/**
 * Menyusun data ringkasan halaman checkout.
 * Semua angka dan teks berasal dari database.
 */
class CheckoutSummaryBuilder
{
    public const PICKUP_SELF = 'self_pickup';

    public const PICKUP_DELIVERY = 'delivery';

    private const GUEST_LABEL = 'Tamu (Guest)';

    public function quantityOf(array $cart): int
    {
        return max(1, (int) ($cart['quantity'] ?? $cart['qty'] ?? 1));
    }

    /**
     * Membuat ringkasan checkout untuk keranjang multi-item
     * maupun struktur keranjang lama (single-item).
     */
    public function build(
        array $cart,
        ?User $user,
        ?string $requestedMethod
    ): array {
        $isLoggedIn = $user !== null;

        // Ambil item keranjang. Tetap mendukung struktur lama.
        $rawItems = ! empty($cart['items']) && is_array($cart['items'])
            ? array_values($cart['items'])
            : [
                [
                    'master_item_id' => $cart['master_item_id'] ?? 0,
                    'qty' => $this->quantityOf($cart),
                ],
            ];

        if (empty($rawItems)) {
            throw new \RuntimeException(
                'Keranjang belanja masih kosong.'
            );
        }

        // Ambil semua produk dari database.
        $masterItemIds = array_unique(array_map(
            fn ($item) => (int) ($item['master_item_id'] ?? 0),
            $rawItems
        ));

        $masterItems = MasterItem::with(['vendor', 'category'])
            ->whereIn('id', $masterItemIds)
            ->get()
            ->keyBy('id');

        $firstItem = $masterItems->first();

        if (! $firstItem) {
            throw new \RuntimeException(
                'Alat camping di keranjang tidak ditemukan.'
            );
        }

        $vendor = $firstItem->vendor;

        if (! $vendor) {
            throw new \RuntimeException(
                'Vendor produk tidak ditemukan.'
            );
        }

        // Pastikan semua produk berasal dari vendor yang sama.
        foreach ($masterItems as $itemModel) {
            if ((int) $itemModel->vendor_id !== (int) $vendor->id) {
                throw new \RuntimeException(
                    'Keranjang tidak boleh berisi produk dari vendor berbeda.'
                );
            }
        }

        $pickupMethod = $this->resolvePickupMethod(
            $isLoggedIn,
            $requestedMethod
        );

        $pickupTime = $vendor->pickup_time
            ?: config('booking.default_pickup_time');

        $returnTime = $pickupTime;

        $rentalDays = max(1, (int) app(RentalService::class)
            ->calculateRentalDays(
                Carbon::parse($cart['pickup_date'])->toDateString(),
                Carbon::parse($cart['return_date'])->toDateString(),
                $pickupTime,
                $returnTime
            ));

        $discount = 0;

        // Hitung subtotal seluruh item.
        $itemsData = [];
        $totalSubtotal = 0;

        foreach ($rawItems as $rawItem) {
            $itemId = (int) ($rawItem['master_item_id'] ?? 0);
            $itemModel = $masterItems->get($itemId);

            if (! $itemModel) {
                throw new \RuntimeException(
                    "Produk dengan ID {$itemId} tidak ditemukan."
                );
            }

            $requestedQty = max(
                1,
                (int) ($rawItem['qty'] ?? $rawItem['quantity'] ?? 1)
            );

            $stock = $this->stockFor(
                $itemModel,
                Carbon::parse($cart['pickup_date'])->toDateString(),
                Carbon::parse($cart['return_date'])->toDateString()
            );

            // Jumlah yang ditagih = jumlah yang ditampilkan (tidak melebihi stok).
            $qty = min($requestedQty, $stock);

            $dailyRate = (int) $itemModel->daily_rate;
            $depositPerUnit = (int) $itemModel->deposit_amount;

            // Pengali harga = jumlah unit x jumlah hari.
            $subtotal = $dailyRate * $qty * $rentalDays;

            $totalSubtotal += $subtotal;

            $itemsData[] = $this->itemData(
                $itemModel,
                $qty,
                $dailyRate,
                $depositPerUnit,
                $subtotal,
                $stock
            );
        }

        $deliveryFee = $pickupMethod === self::PICKUP_DELIVERY ? null : 0;
        $grandTotal = $deliveryFee === null ? null : max(0, $totalSubtotal - $discount);

        $payments = app(PaymentService::class);
        $dpPercentage = $payments->resolveDpPercentage($vendor->dp_percentage);

        $dpAmount = $deliveryFee === null ? null : intdiv(
            max(0, $totalSubtotal + $deliveryFee - $discount) * $dpPercentage + 99,
            100
        );

        return [
            'booking_code' => 'TDK-PENDING',
            'status' => 'pending',
            'status_label' => 'Menunggu Pembayaran',
            'created_at' => now()->format('d F Y, H:i T'),
            'expires_at' => now()
                ->addMinutes((int) config('booking.payment_expiry_minutes'))
                ->format('d F Y, H:i T'),

            'is_guest' => ! $isLoggedIn,

            'vendor' => $this->vendorData($vendor),

            'customer' => [
                'name' => $user?->name ?? self::GUEST_LABEL,
                'phone' => $user?->phone ?? '',
                'email' => $user?->email ?? '',
            ],

            'pickup_date' => Carbon::parse(
                $cart['pickup_date']
            )->translatedFormat('l, d F Y'),

            'pickup_time' => $this->clock($pickupTime),

            'return_date' => Carbon::parse(
                $cart['return_date']
            )->translatedFormat('l, d F Y'),

            'return_time' => $this->clock($returnTime),

            'rental_days' => $rentalDays,
            'pickup_date_iso' => Carbon::parse($cart['pickup_date'])->toDateString(),
            'return_date_iso' => Carbon::parse($cart['return_date'])->toDateString(),
            'pickup_method' => $pickupMethod,
            'delivery_address' => $user?->address ?? '',
            'delivery_notes' => '',

            // Data seluruh produk dalam keranjang.
            'items' => $itemsData,

            'subtotal' => $totalSubtotal,
            'admin_fee' => 0,
            'deposit' => 0,
            'delivery_fee' => $deliveryFee,
            'discount' => $discount,
            'grand_total' => $grandTotal,

            'dp_amount' => $dpAmount,
            'dp_percentage' => $dpPercentage,

            'payment_options' => ['full', 'dp'],
            'payment_methods' => $this->paymentMethods(),
        ];
    }

    private function resolvePickupMethod(
        bool $isLoggedIn,
        ?string $requested
    ): string {
        return ($isLoggedIn && $requested === self::PICKUP_DELIVERY)
            ? self::PICKUP_DELIVERY
            : self::PICKUP_SELF;
    }

    private function vendorData(Vendor $vendor): array
    {
        return [
            'id' => $vendor->id,
            'name' => $vendor->name,
            'badge' => $vendor->badge,
            'city' => $vendor->city,
            'address' => $vendor->address,
            'phone' => $vendor->phone,
            'rating' => $vendor->rating_avg,
            'total_reviews' => $vendor->reviews_count,
            'pickup_location' => $vendor->address,
            'operational_hours' => $vendor->operational_hours,
        ];
    }

    private function itemData(
        MasterItem $item,
        int $qty,
        int $dailyRate,
        int $depositPerUnit,
        int $subtotal,
        int $realStock
    ): array {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'category' => $item->category?->name,
            'variant' => '',
            'specs' => $item->description ?? '',
            'price_per_day' => $dailyRate,
            'weight_kg' => (float) $item->weight_kg,
            'deposit_per_unit' => $depositPerUnit,
            'qty' => $qty,
            'stock' => $realStock,
            'max_qty' => $realStock,
            'subtotal' => $subtotal,
            'icon' => $item->category?->icon,
        ];
    }

    /**
     * Stok unit yang bisa disewa untuk rentang tanggal keranjang (minimal 1).
     */
    private function stockFor(MasterItem $item, string $pickupDate, string $returnDate): int
    {
        $available = app(AvailabilityService::class)->checkAvailability(
            $item->id,
            $pickupDate,
            $returnDate
        );

        return max(
            1,
            $available ?: $item->units()
                ->where('status', '!=', 'retired')
                ->count()
        );
    }

    private function paymentMethods(): array
    {
        return collect(config('payment.enabled_channels', []))
            ->map(fn ($channel) => [
                'id' => is_array($channel)
                    ? $channel['id']
                    : $channel,

                'name' => is_array($channel)
                    ? ($channel['name'] ?? $channel['id'])
                    : $channel,

                'desc' => is_array($channel)
                    ? ($channel['desc'] ?? '')
                    : '',

                'badge' => is_array($channel)
                    ? ($channel['badge'] ?? null)
                    : null,
            ])
            ->values()
            ->all();
    }

    private function clock(?string $time): string
    {
        if (empty($time)) {
            return '-';
        }

        return Carbon::parse($time)->format('H:i');
    }
}
