<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\Concerns\CreatesMidtransFixtures;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use CreatesMidtransFixtures;
    use LazilyRefreshDatabase;

    private const SERVER_KEY = 'test-midtrans-server-key';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.midtrans.server_key' => self::SERVER_KEY]);
    }

    public function test_owner_can_request_a_snap_token_for_a_pending_payment(): void
    {
        [$owner, $rental, $payment] = $this->createRentalWithPendingPayment();

        $this->mock(MidtransService::class, function (MockInterface $mock) use ($payment): void {
            $mock->shouldReceive('createTransaction')
                ->once()
                ->with(Mockery::on(fn (Payment $requestedPayment): bool => $requestedPayment->is($payment)
                    && (float) $requestedPayment->amount === 10000.0))
                ->andReturn('sandbox-snap-token');
        });

        $response = $this->actingAs($owner)
            ->postJson(route('rentals.pay', $rental));

        $response->assertOk()->assertExactJson([
            'data' => [
                'payment_id' => $payment->getKey(),
                'order_id' => 'TENDA-INV-001',
                'snap_token' => 'sandbox-snap-token',
            ],
        ]);
    }

    public function test_unauthenticated_customer_cannot_request_a_snap_token(): void
    {
        [, $rental] = $this->createRentalWithPendingPayment();

        $this->postJson(route('rentals.pay', $rental))
            ->assertUnauthorized();
    }

    public function test_customer_cannot_request_a_snap_token_for_another_customers_rental(): void
    {
        [, $rental] = $this->createRentalWithPendingPayment();
        $otherCustomer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($otherCustomer)
            ->postJson(route('rentals.pay', $rental))
            ->assertNotFound();
    }

    public function test_valid_settlement_notification_marks_payment_paid_and_confirms_rental(): void
    {
        [, $rental, $payment] = $this->createRentalWithPendingPayment();
        $notification = $this->signedNotification([
            'transaction_status' => 'settlement',
            'transaction_id' => 'sandbox-transaction-001',
        ]);

        $response = $this->postJson(route('webhook.midtrans'), $notification);

        $response->assertOk()->assertExactJson([
            'status' => 'success',
            'payment_status' => 'paid',
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->getKey(),
            'status' => 'paid',
            'midtrans_status' => 'settlement',
            'midtrans_transaction_id' => 'sandbox-transaction-001',
        ]);
        $this->assertDatabaseHas('rentals', [
            'id' => $rental->getKey(),
            'status' => 'confirmed',
        ]);
    }

    public function test_notification_with_an_invalid_signature_does_not_change_payment(): void
    {
        [, $rental, $payment] = $this->createRentalWithPendingPayment();
        $notification = $this->signedNotification([
            'transaction_status' => 'settlement',
        ]);
        $notification['signature_key'] = str_repeat('0', 128);

        $this->postJson(route('webhook.midtrans'), $notification)
            ->assertForbidden();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->getKey(),
            'status' => 'pending',
            'midtrans_status' => null,
        ]);
        $this->assertDatabaseHas('rentals', [
            'id' => $rental->getKey(),
            'status' => 'pending',
        ]);
    }

    public function test_notification_with_a_mismatched_amount_does_not_change_payment(): void
    {
        [, $rental, $payment] = $this->createRentalWithPendingPayment();
        $notification = $this->signedNotification([
            'transaction_status' => 'settlement',
            'gross_amount' => '9000.00',
        ]);

        $this->postJson(route('webhook.midtrans'), $notification)
            ->assertUnprocessable();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->getKey(),
            'status' => 'pending',
            'midtrans_status' => null,
        ]);
        $this->assertDatabaseHas('rentals', [
            'id' => $rental->getKey(),
            'status' => 'pending',
        ]);
    }

    public function test_repeated_settlement_notification_is_idempotent(): void
    {
        [, $rental, $payment] = $this->createRentalWithPendingPayment();
        $notification = $this->signedNotification(['transaction_status' => 'settlement']);

        $this->postJson(route('webhook.midtrans'), $notification)->assertOk();
        $paidAt = $payment->refresh()->paid_at;
        $this->postJson(route('webhook.midtrans'), $notification)->assertOk();

        $this->assertSame($paidAt->toDateTimeString(), $payment->refresh()->paid_at->toDateTimeString());
        $this->assertDatabaseHas('rentals', [
            'id' => $rental->getKey(),
            'status' => 'confirmed',
        ]);
    }

    public function test_failed_notification_marks_payment_failed_without_confirming_rental(): void
    {
        [, $rental, $payment] = $this->createRentalWithPendingPayment();
        $notification = $this->signedNotification(['transaction_status' => 'expire']);

        $this->postJson(route('webhook.midtrans'), $notification)
            ->assertOk()
            ->assertJsonPath('payment_status', 'failed');

        $this->assertDatabaseHas('payments', [
            'id' => $payment->getKey(),
            'status' => 'failed',
            'midtrans_status' => 'expire',
        ]);
        $this->assertDatabaseHas('rentals', [
            'id' => $rental->getKey(),
            'status' => 'pending',
        ]);
    }

}
