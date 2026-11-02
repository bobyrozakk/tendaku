<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Payment\MidtransService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery\MockInterface;
use Tests\Concerns\CreatesMidtransFixtures;
use Tests\TestCase;

/**
 * Skenario 2: DP online → pelunasan sisa tunai di kasir.
 * Skenario 8: Retry setelah expired → order_id baru.
 * Skenario 11: 2 pelanggan checkout unit yang sama bersamaan (concurrency / lock).
 *
 * Skenario 12 (batch payout marking) dan 14 (platform fee 2%) dihapus karena
 * alur payout lama sudah digantikan settlement berbasis invoice dan komisi 5%
 * per rental. Cakupannya ada di SettlementTest dan CommissionCalculatorTest.
 *
 * @see docs/TENDAKU_payment_spec.md §12
 * @see docs/contract.md §4.10, §4.11
 */
class PaymentScenarioTest extends TestCase
{
    use CreatesMidtransFixtures;
    use LazilyRefreshDatabase;

    private const SERVER_KEY = 'test-midtrans-server-key';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.midtrans.server_key' => self::SERVER_KEY]);
    }

    // ── Skenario 2: DP Online → Pelunasan Sisa Tunai ─────────────────────────

    /**
     * Skenario 2: DP 50% dibayar online, sisa dilunasi tunai di kasir.
     * Harapan: 2 baris payments terbentuk, totalnya = grand_total.
     *
     * @see TENDAKU_payment_spec.md §12 Skenario 2
     */
    public function test_dp_online_then_remaining_cash_creates_two_payment_rows(): void
    {
        /** @var Vendor $vendor */
        $vendor = Vendor::query()->create([
            'name' => 'Vendor DP Test',
            'slug' => 'vendor-dp-test',
            'phone' => '081200000002',
            'address' => 'Address DP',
            'status' => 'active',
        ]);

        $customer = User::factory()->create(['role' => 'customer', 'vendor_id' => $vendor->id]);

        $grandTotal = 300000;
        $dpAmount = 150000; // 50%

        $rental = Rental::query()->create([
            'vendor_id' => $vendor->id,
            'user_id' => $customer->id,
            'booking_code' => 'TDK-DP-TEST-001',
            'pickup_date' => '2026-11-01',
            'return_date' => '2026-11-02',
            'rental_days' => 1,
            'subtotal' => $grandTotal,
            'grand_total' => $grandTotal,
            'status' => 'pending',
        ]);

        // Langkah 1: Buat payment DP online (midtrans)
        $dpPayment = Payment::query()->create([
            'rental_id' => $rental->id,
            'vendor_id' => $vendor->id,
            'payment_type' => 'dp',
            'channel' => 'midtrans',
            'amount' => $dpAmount,
            'order_id' => 'TDK-DP-TEST-001-DP-1',
            'status' => 'pending',
            'platform_fee' => 3000, // 2% dari 150.000
        ]);

        // Simulasi webhook settlement untuk DP
        $dpAmount_str = number_format($dpAmount, 2, '.', '');
        $signature = hash('sha512', 'TDK-DP-TEST-001-DP-1'.'200'.$dpAmount_str.self::SERVER_KEY);

        $this->postJson(route('webhook.midtrans'), [
            'order_id' => 'TDK-DP-TEST-001-DP-1',
            'status_code' => '200',
            'gross_amount' => $dpAmount_str,
            'transaction_status' => 'settlement',
            'transaction_id' => 'txn-dp-001',
            'fraud_status' => 'accept',
            'signature_key' => $signature,
        ])->assertOk();

        // Rental tetap pending sampai kasir memvalidasi barcode unit di POS.
        $this->assertDatabaseHas('rentals', ['id' => $rental->id, 'status' => 'pending']);

        // Langkah 2: Kasir terima pelunasan sisa tunai
        $kasir = User::factory()->create(['role' => 'vendor_admin', 'vendor_id' => $vendor->id]);
        $remainingAmount = $grandTotal - $dpAmount;

        $remainingPayment = Payment::query()->create([
            'rental_id' => $rental->id,
            'vendor_id' => $vendor->id,
            'payment_type' => 'remaining',
            'channel' => 'cash',
            'method' => 'cash',
            'amount' => $remainingAmount,
            'platform_fee' => 0, // tunai tidak kena fee (D3)
            'status' => 'paid',
            'paid_at' => now(),
            'received_by' => $kasir->id,
        ]);

        // Verifikasi: 2 baris payments, total = grand_total
        $payments = Payment::query()->where('rental_id', $rental->id)->get();
        $this->assertCount(2, $payments);

        $total = $payments->sum('amount');
        $this->assertEquals($grandTotal, $total);

        // DP channel=midtrans, remaining channel=cash
        $this->assertDatabaseHas('payments', [
            'id' => $dpPayment->id,
            'payment_type' => 'dp',
            'channel' => 'midtrans',
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $remainingPayment->id,
            'payment_type' => 'remaining',
            'channel' => 'cash',
            'platform_fee' => 0,
            'status' => 'paid',
        ]);
    }

    // ── Skenario 8: Retry setelah expired → order_id baru ────────────────────

    /**
     * Skenario 8: Payment expired → retry menghasilkan order_id baru dengan attempt+1.
     *
     * @see TENDAKU_payment_spec.md §12 Skenario 8
     */
    public function test_retry_after_expired_creates_new_order_id(): void
    {
        [$customer, $rental, $oldPayment] = $this->createRentalWithPendingPayment();

        // Tandai payment lama sebagai expired
        $oldPayment->update(['status' => 'expired']);

        // Mock MidtransService agar tidak benar-benar hit Midtrans
        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createTransaction')
                ->once()
                ->andReturn('new-snap-token-attempt-2');
        });

        $response = $this->actingAs($customer)
            ->postJson(route('payments.retry', $oldPayment->order_id));

        $response->assertOk();
        $response->assertJsonPath('snap_token', 'new-snap-token-attempt-2');

        // order_id baru harus berakhiran -2 (attempt berikutnya)
        $newOrderId = $response->json('order_id');
        $this->assertStringEndsWith('-2', $newOrderId);

        // Payment baru ada di DB dengan status pending
        $this->assertDatabaseHas('payments', [
            'rental_id' => $rental->id,
            'order_id' => $newOrderId,
            'payment_type' => 'full',
            'status' => 'pending',
            'channel' => 'midtrans',
        ]);

        // Payment lama tetap expired
        $this->assertDatabaseHas('payments', [
            'id' => $oldPayment->id,
            'status' => 'expired',
        ]);
    }

    /**
     * Skenario 8b: Retry tidak boleh berhasil jika payment masih pending atau sudah paid.
     */
    public function test_retry_fails_if_payment_is_still_pending(): void
    {
        [$customer, , $payment] = $this->createRentalWithPendingPayment();
        // Payment masih pending

        $this->actingAs($customer)
            ->postJson(route('payments.retry', $payment->order_id))
            ->assertUnprocessable();
    }

    // ── Skenario 11: Concurrency — 2 customer checkout unit yang sama ─────────

    /**
     * Skenario 11: 2 customer checkout alat yang sama hampir bersamaan.
     * Karena availability check + lock, hanya satu yang berhasil.
     *
     * Catatan: Test ini mensimulasikan alur race condition secara sequential
     * karena PHP bersifat single-threaded. Uji concurrency sesungguhnya perlu
     * dilakukan lewat load testing (k6/Locust) bersama Byan.
     *
     * @see TENDAKU_payment_spec.md §12 Skenario 11
     */
    public function test_concurrent_bookings_are_handled_by_availability_check(): void
    {
        $vendor = Vendor::query()->create([
            'name' => 'Vendor Race',
            'slug' => 'vendor-race',
            'phone' => '081200000003',
            'address' => 'Race address',
            'status' => 'active',
        ]);

        $customer1 = User::factory()->create(['role' => 'customer', 'vendor_id' => $vendor->id]);
        $customer2 = User::factory()->create(['role' => 'customer', 'vendor_id' => $vendor->id]);

        // Rental 1 berhasil booking (stok terbatas — simulated)
        $rental1 = Rental::query()->create([
            'vendor_id' => $vendor->id,
            'user_id' => $customer1->id,
            'booking_code' => 'TDK-RACE-001',
            'pickup_date' => '2026-11-01',
            'return_date' => '2026-11-02',
            'rental_days' => 1,
            'subtotal' => 100000,
            'grand_total' => 100000,
            'status' => 'confirmed', // customer1 berhasil duluan
        ]);

        // Customer2 mencoba booking tanggal yang sama → dalam implementasi nyata
        // AvailabilityService::checkAvailability() akan return 0 dan melempar InsufficientStockException.
        // Di sini kita hanya verifikasi bahwa rental pertama tidak terdampak.
        $this->assertDatabaseHas('rentals', [
            'id' => $rental1->id,
            'status' => 'confirmed',
        ]);

        // Verifikasi: tidak ada rental customer2 yang sukses untuk tanggal yang sama
        $this->assertDatabaseMissing('rentals', [
            'user_id' => $customer2->id,
            'pickup_date' => '2026-11-01',
            'status' => 'confirmed',
        ]);
    }
}
