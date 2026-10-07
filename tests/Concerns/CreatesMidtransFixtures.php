<?php

namespace Tests\Concerns;

use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vendor;

/**
 * Shared fixtures for Midtrans-related tests.
 *
 * Provides helpers to create a standard rental+payment setup and to build
 * signed Midtrans webhook notification payloads.
 *
 * Usage: add `use CreatesMidtransFixtures;` inside any TestCase class that
 * needs these helpers, then set the SERVER_KEY constant on that class:
 *
 *   private const SERVER_KEY = 'test-midtrans-server-key';
 */
trait CreatesMidtransFixtures
{
    /**
     * Create a vendor, a customer, a rental, and a pending settlement payment.
     *
     * @return array{User, Rental, Payment}
     */
    private function createRentalWithPendingPayment(): array
    {
        $vendor = Vendor::query()->create([
            'name' => 'Sandbox Vendor',
            'slug' => 'sandbox-vendor',
            'phone' => '081200000001',
            'address' => 'Sandbox address',
            'status' => 'active',
        ]);

        $owner = User::factory()->create(['role' => 'customer']);

        $rental = Rental::query()->create([
            'vendor_id' => $vendor->getKey(),
            'user_id' => $owner->getKey(),
            'booking_code' => 'TDK-TEST-001',
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-11',
            'rental_days' => 1,
            'subtotal' => 10000,
            'grand_total' => 10000,
            'status' => 'pending',
        ]);

        $payment = Payment::query()->create([
            'rental_id' => $rental->getKey(),
            'vendor_id' => $vendor->getKey(),
            'payment_type' => 'settlement',
            'payment_method' => 'midtrans_snap',
            'amount' => 10000,
            'midtrans_order_id' => 'TENDA-INV-001',
            'status' => 'pending',
        ]);

        return [$owner, $rental, $payment];
    }

    /**
     * Build a signed Midtrans webhook notification payload.
     *
     * The signature is computed from the SERVER_KEY constant defined on the
     * consuming test class, so the consuming class MUST define:
     *
     *   private const SERVER_KEY = '...';
     *
     * and call `config(['services.midtrans.server_key' => self::SERVER_KEY])`
     * in its setUp().
     *
     * @param  array<string, string>  $overrides  Values to override on the default payload.
     * @return array<string, string>
     */
    private function signedNotification(array $overrides = []): array
    {
        $notification = array_merge([
            'order_id' => 'TENDA-INV-001',
            'status_code' => '200',
            'gross_amount' => '10000.00',
            'transaction_status' => 'settlement',
            'transaction_id' => 'sandbox-transaction',
            'fraud_status' => 'accept',
            'signature_key' => '',
        ], $overrides);

        $notification['signature_key'] = hash(
            'sha512',
            $notification['order_id'].$notification['status_code'].$notification['gross_amount'].static::SERVER_KEY,
        );

        return $notification;
    }
}
