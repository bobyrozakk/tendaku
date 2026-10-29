<?php

namespace App\Services\Booking;

use App\Models\MasterItem;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;

/**
 * Menyusun data ringkasan halaman checkout.
 * Semua angka & teks berasal dari DB (vendors, master_items, payment_methods).
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
     * Ongkir hanya untuk user login yang memilih delivery. Tarifnya dari tabel vendors.
     */
    public function deliveryFee(Vendor $vendor, string $pickupMethod, bool $isLoggedIn): int
    {
        return ($isLoggedIn && $pickupMethod === self::PICKUP_DELIVERY)
            ? (int) $vendor->delivery_fee
            : 0;
    }

    public function build(array $cart, ?User $user, ?string $requestedMethod): array
    {
        $isLoggedIn = $user !== null;

        $masterItem = MasterItem::with(['vendor', 'category'])->findOrFail($cart['master_item_id']);
        $vendor = $masterItem->vendor;

        $pickupMethod = $this->resolvePickupMethod($isLoggedIn, $requestedMethod);
        $quantity = $this->quantityOf($cart);
        $pickupTime = $vendor->pickup_time ?: config('rental.default_pickup_time', '09:00');
        $returnTime = $vendor->return_time ?: config('rental.default_return_time', '09:00');

        $rentalDays = app(RentalService::class)->calculateRentalDays(
            Carbon::parse($cart['pickup_date'])->toDateString(),
            Carbon::parse($cart['return_date'])->toDateString(),
            $pickupTime,
            $returnTime,
        );

        $dailyRate = (int) $masterItem->daily_rate;
        $depositPerUnit = 0;
        $discount = 0;

        $itemSubtotal = $dailyRate * $quantity * $rentalDays;
        $adminFee = (int) round($itemSubtotal * 0.05);
        $totalDeposit = 0;
        $deliveryFee = $this->deliveryFee($vendor, $pickupMethod, $isLoggedIn);
        $grandTotal = $itemSubtotal + $adminFee + $deliveryFee - $discount;

        $dpPercentage = (int) ($vendor->dp_percentage ?: config('payment.dp_percentage', 50));
        $dpAmount = (int) ceil(($itemSubtotal * $dpPercentage / 100) + $adminFee);

        return [
            'booking_code' => 'TDK-PENDING',
            'status' => 'pending',
            'status_label' => 'Menunggu Pembayaran',
            'created_at' => now()->format('d F Y, H:i T'),
            'expires_at' => now()->addHours((int) config('rental.payment_expiry_hours'))->format('d F Y, H:i T'),
            'is_guest' => ! $isLoggedIn,
            'vendor' => $this->vendorData($vendor),
            'customer' => [
                'name' => $user?->name ?? self::GUEST_LABEL,
                'phone' => $user?->phone ?? '',
                'email' => $user?->email ?? '',
            ],
            'pickup_date' => Carbon::parse($cart['pickup_date'])->translatedFormat('l, d F Y'),
            'pickup_time' => $this->clock($pickupTime),
            'return_date' => Carbon::parse($cart['return_date'])->translatedFormat('l, d F Y'),
            'return_time' => $this->clock($returnTime),
            'rental_days' => $rentalDays,
            'pickup_method' => $pickupMethod,
            'delivery_address' => $user?->address ?? '',
            'delivery_notes' => '',
            'items' => [$this->itemData($masterItem, $quantity, $dailyRate, $depositPerUnit, $itemSubtotal)],
            'subtotal' => $itemSubtotal,
            'admin_fee' => $adminFee,
            'deposit' => $totalDeposit,
            'delivery_fee' => $deliveryFee,
            'discount' => $discount,
            'grand_total' => $grandTotal,
            'dp_amount' => $dpAmount,
            'dp_percentage' => $dpPercentage,
            'payment_options' => ['full', 'dp'],
            'payment_methods' => $this->paymentMethods(),
        ];
    }

    private function resolvePickupMethod(bool $isLoggedIn, ?string $requested): string
    {
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

        private function itemData(MasterItem $item, int $qty, int $dailyRate, int $depositPerUnit, int $subtotal): array
    {
        $stock = app(\App\Services\Booking\AvailabilityService::class)->checkAvailability(
            $item->id,
            request('pickup_date', now()->toDateString()),
            request('return_date', now()->addDay()->toDateString())
        );

        $realStock = max(1, $stock ?: $item->units()->where('status', '!=', 'retired')->count());

        return [
            'id' => $item->id,
            'name' => $item->name,
            'category' => $item->category?->name,
            'variant' => '',
            'specs' => $item->description ?? '',
            'price_per_day' => $dailyRate,
            'deposit_per_unit' => $depositPerUnit,
            'qty' => min($qty, $realStock),
            'stock' => $realStock,
            'max_qty' => $realStock,
            'subtotal' => $subtotal,
            'icon' => $item->category?->icon,
        ];
    }

    private function paymentMethods(): array
    {
        return collect(config('payment.enabled_channels', []))
            ->map(fn ($channel) => [
                'id' => is_array($channel) ? $channel['id'] : $channel,
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
