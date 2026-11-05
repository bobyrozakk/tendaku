<?php

namespace Tests\Concerns;

use App\Models\Payment;
use App\Models\PlatformCommission;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Str;

trait CreatesRevenueFixtures
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createRevenueVendor(string $name = 'Vendor Pendapatan', array $attributes = []): Vendor
    {
        return Vendor::query()->create(array_merge([
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'phone' => fake()->numerify('08##########'),
            'address' => fake()->address(),
            'city' => 'Malang',
            'status' => 'active',
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createRevenueUser(string $role, ?Vendor $vendor = null, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role,
            'vendor_id' => $vendor?->id,
            'email_verified_at' => now(),
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $paymentAttributes
     * @param  array<string, mixed>  $rentalAttributes
     * @return array{0: Rental, 1: Payment}
     */
    private function createPaidRevenuePayment(
        Vendor $vendor,
        float $amount = 100000,
        array $paymentAttributes = [],
        array $rentalAttributes = [],
    ): array {
        $bookingCode = $paymentAttributes['booking_code']
            ?? $rentalAttributes['booking_code']
            ?? 'TND-REV-'.fake()->unique()->numerify('######');
        $paidAt = $paymentAttributes['paid_at'] ?? now();

        $rental = Rental::query()->create(array_merge([
            'vendor_id' => $vendor->id,
            'user_id' => null,
            'booking_code' => $bookingCode,
            'guest_name' => 'Pelanggan Uji',
            'guest_phone' => '081200000099',
            'pickup_date' => Carbon::today()->toDateString(),
            'return_date' => Carbon::today()->addDay()->toDateString(),
            'rental_days' => 1,
            'subtotal' => $amount,
            'grand_total' => $amount,
            'final_total' => $amount,
            'status' => 'completed',
            'completed_at' => $paidAt,
        ], $rentalAttributes));

        unset($paymentAttributes['booking_code']);

        $payment = Payment::query()->create(array_merge([
            'rental_id' => $rental->id,
            'vendor_id' => $vendor->id,
            'payment_type' => 'full',
            'payment_method' => 'midtrans_snap',
            'payment_channel' => 'qris',
            'amount' => $amount,
            'gateway_fee_amount' => 0,
            'midtrans_order_id' => 'TND-ORDER-'.fake()->unique()->numerify('######'),
            'status' => 'paid',
            'escrow_status' => 'held',
            'paid_at' => now(),
        ], $paymentAttributes));

        return [$rental, $payment];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createRevenueCommission(Payment $payment, float $amount, array $attributes = []): PlatformCommission
    {
        return PlatformCommission::withoutGlobalScope('vendor')->create(array_merge([
            'payment_id' => $payment->id,
            'rental_id' => $payment->rental_id,
            'vendor_id' => $payment->vendor_id,
            'base_amount' => $payment->amount,
            'rate' => 0.05,
            'amount' => $amount,
            'status' => 'accrued',
            'recognized_at' => $payment->paid_at ?? now(),
        ], $attributes));
    }
}
