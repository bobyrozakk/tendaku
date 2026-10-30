<?php

namespace Tests\Feature\Monetization;

use App\Models\Payment;
use App\Services\Finance\EscrowService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\CreatesMidtransFixtures;
use Tests\TestCase;

class EscrowTest extends TestCase
{
    use CreatesMidtransFixtures;
    use LazilyRefreshDatabase;

    public function test_a_held_payment_can_be_released(): void
    {
        [, , $payment] = $this->createRentalWithPendingPayment();
        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'escrow_status' => 'held',
        ]);

        $this->assertTrue(app(EscrowService::class)->markReleased($payment));
        $this->assertSame('released', $payment->refresh()->escrow_status);
    }

    public function test_a_released_payment_cannot_be_refunded_by_changing_the_escrow_status(): void
    {
        [, , $payment] = $this->createRentalWithPendingPayment();
        $payment->update([
            'status' => 'refunded',
            'escrow_status' => 'released',
        ]);

        $this->assertFalse(app(EscrowService::class)->markRefunded($payment));
        $this->assertSame('released', $payment->refresh()->escrow_status);
    }

    public function test_a_cash_payment_is_not_applicable_for_escrow(): void
    {
        [, , $payment] = $this->createRentalWithPendingPayment();
        $payment->update([
            'payment_method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->assertFalse(app(EscrowService::class)->markHeld($payment));
        $this->assertSame('not_applicable', $payment->refresh()->escrow_status);
    }

    public function test_a_failed_midtrans_payment_is_voided(): void
    {
        [, , $payment] = $this->createRentalWithPendingPayment();
        $payment->update(['status' => 'failed']);

        $this->assertTrue(app(EscrowService::class)->markVoid($payment));
        $this->assertSame('void', $payment->refresh()->escrow_status);
    }

    public function test_repeating_a_transition_is_idempotent(): void
    {
        [, , $payment] = $this->createRentalWithPendingPayment();
        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'escrow_status' => 'held',
        ]);

        $service = app(EscrowService::class);

        $this->assertFalse($service->markHeld($payment));
        $this->assertSame('held', $payment->refresh()->escrow_status);
    }

    public function test_a_paid_payment_without_a_midtrans_method_keeps_escrow_not_applicable(): void
    {
        [, , $payment] = $this->createRentalWithPendingPayment();
        $payment->update([
            'payment_method' => 'transfer',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->assertFalse(app(EscrowService::class)->markHeld($payment));
        $this->assertSame('not_applicable', $payment->refresh()->escrow_status);
    }

    public function test_escrow_status_uses_the_database_default_for_new_payments(): void
    {
        $payment = Payment::factory()->create();

        $this->assertSame('not_applicable', $payment->refresh()->escrow_status);
    }
}
