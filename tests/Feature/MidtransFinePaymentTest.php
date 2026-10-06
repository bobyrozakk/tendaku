<?php

namespace Tests\Feature;

use App\Models\Rental;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery\MockInterface;
use Tests\Concerns\CreatesMidtransFixtures;
use Tests\TestCase;

class MidtransFinePaymentTest extends TestCase
{
    use CreatesMidtransFixtures;
    use LazilyRefreshDatabase;

    private const SERVER_KEY = 'test-midtrans-server-key';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.midtrans.server_key' => self::SERVER_KEY]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Buat rental yang sudah dalam status 'returned' dengan denda dari DB.
     *
     * @return array{User, Rental}
     */
    private function createReturnedRentalWithFine(float $lateFee = 50000, float $damageFee = 30000): array
    {
        [$owner, $rental] = $this->createRentalWithPendingPayment();

        $rental->update([
            'status' => 'returned',
            'total_late_fee' => $lateFee,
            'total_damage_fee' => $damageFee,
        ]);

        return [$owner, $rental->fresh()];
    }

    // ── Tests: Akses & Otorisasi ─────────────────────────────────────────────

    public function test_customer_can_generate_snap_token_for_fine_payment(): void
    {
        [$owner, $rental] = $this->createReturnedRentalWithFine(50000, 30000);

        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createTransaction')
                ->once()
                ->andReturn('sandbox-fine-snap-token');
        });

        $response = $this->actingAs($owner)
            ->postJson(route('rentals.pay-fine', $rental));

        $response->assertOk()
            ->assertJsonPath('data.snap_token', 'sandbox-fine-snap-token')
            ->assertJson([
                'data' => [
                    'fine_total' => 80000,
                    'late_fee' => 50000,
                    'damage_fee' => 30000,
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_access_fine_payment_endpoint(): void
    {
        [$owner, $rental] = $this->createReturnedRentalWithFine();

        $this->postJson(route('rentals.pay-fine', $rental))
            ->assertUnauthorized();
    }

    public function test_customer_cannot_pay_fine_for_another_customers_rental(): void
    {
        [$owner, $rental] = $this->createReturnedRentalWithFine();
        $otherCustomer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($otherCustomer)
            ->postJson(route('rentals.pay-fine', $rental))
            ->assertNotFound();
    }

    // ── Tests: Validasi Bisnis ────────────────────────────────────────────────

    public function test_fine_payment_is_rejected_if_rental_status_is_still_pending(): void
    {
        [$owner, $rental] = $this->createRentalWithPendingPayment();
        // status masih 'pending' — belum ada pengembalian

        $this->actingAs($owner)
            ->postJson(route('rentals.pay-fine', $rental))
            ->assertUnprocessable();
    }

    public function test_fine_payment_is_rejected_if_rental_status_is_confirmed(): void
    {
        [$owner, $rental] = $this->createRentalWithPendingPayment();
        $rental->update(['status' => 'confirmed']);

        $this->actingAs($owner)
            ->postJson(route('rentals.pay-fine', $rental))
            ->assertUnprocessable();
    }

    public function test_fine_payment_is_rejected_when_no_fine_exists(): void
    {
        [$owner, $rental] = $this->createReturnedRentalWithFine(0, 0);

        $this->actingAs($owner)
            ->postJson(route('rentals.pay-fine', $rental))
            ->assertUnprocessable();
    }

    // ── Tests: Idempotency ────────────────────────────────────────────────────

    public function test_repeated_fine_payment_request_reuses_existing_pending_payment(): void
    {
        [$owner, $rental] = $this->createReturnedRentalWithFine(50000, 0);

        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createTransaction')
                ->twice() // dipanggil dua kali tapi Payment dibuat hanya sekali
                ->andReturn('token-1', 'token-2');
        });

        $this->actingAs($owner)->postJson(route('rentals.pay-fine', $rental))->assertOk();
        $this->actingAs($owner)->postJson(route('rentals.pay-fine', $rental))->assertOk();

        // Hanya boleh ada 1 payment dengan type=fine
        $this->assertDatabaseCount('payments', 2); // 1 settlement + 1 fine
        $this->assertSame(
            1,
            $rental->payments()->where('payment_type', 'fine')->count(),
        );
    }

    public function test_fine_payment_returns_success_message_when_already_paid(): void
    {
        [$owner, $rental] = $this->createReturnedRentalWithFine(50000, 0);

        // Buat payment denda yang sudah lunas langsung
        $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => 'fine',
            'payment_method' => 'midtrans_snap',
            'amount' => 50000,
            'status' => 'paid',
        ]);

        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('createTransaction'); // tidak boleh buat token baru
        });

        $response = $this->actingAs($owner)
            ->postJson(route('rentals.pay-fine', $rental));

        $response->assertOk()
            ->assertJsonPath('message', 'Denda pada rental ini sudah lunas.');
    }

    // ── Tests: Payment Record ─────────────────────────────────────────────────

    public function test_fine_payment_creates_correct_payment_record_in_database(): void
    {
        [$owner, $rental] = $this->createReturnedRentalWithFine(40000, 20000);

        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createTransaction')->once()->andReturn('token-abc');
        });

        $this->actingAs($owner)
            ->postJson(route('rentals.pay-fine', $rental))
            ->assertOk();

        $this->assertDatabaseHas('payments', [
            'rental_id' => $rental->id,
            'payment_type' => 'fine',
            'payment_method' => 'midtrans_snap',
            'amount' => 60000, // 40.000 + 20.000
            'status' => 'pending',
        ]);
    }

    // ── Tests: Webhook settlement untuk denda ────────────────────────────────

    public function test_webhook_settlement_marks_fine_payment_as_paid(): void
    {
        [$owner, $rental] = $this->createReturnedRentalWithFine(50000, 0);

        // Buat payment denda pending dulu
        $finePayment = $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => 'fine',
            'payment_method' => 'midtrans_snap',
            'amount' => 50000,
            'midtrans_order_id' => 'TENDA-INV-FINE-001',
            'status' => 'pending',
        ]);

        $notification = $this->signedNotificationForOrder('TENDA-INV-FINE-001', '50000.00', 'settlement');

        $this->postJson(route('webhook.midtrans'), $notification)
            ->assertOk()
            ->assertJsonPath('payment_status', 'paid');

        $this->assertDatabaseHas('payments', [
            'id' => $finePayment->id,
            'status' => 'paid',
        ]);
    }

    // ── Helper: signed notification dengan order id custom ───────────────────

    /**
     * Buat signed notification Midtrans untuk order ID dan amount tertentu.
     *
     * @return array<string, string>
     */
    private function signedNotificationForOrder(string $orderId, string $grossAmount, string $status): array
    {
        $statusCode = '200';
        $signature = hash('sha512', $orderId.$statusCode.$grossAmount.self::SERVER_KEY);

        return [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => $status,
            'transaction_id' => 'sandbox-fine-tx-001',
            'fraud_status' => 'accept',
            'signature_key' => $signature,
        ];
    }
}
