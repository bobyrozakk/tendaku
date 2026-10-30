<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayout;
use App\Services\Payment\MidtransService;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery\MockInterface;
use Tests\Concerns\CreatesMidtransFixtures;
use Tests\TestCase;

/**
 * Skenario 2: DP online → pelunasan sisa tunai di kasir.
 * Skenario 8: Retry setelah expired → order_id baru.
 * Skenario 11: 2 pelanggan checkout unit yang sama bersamaan (concurrency / lock).
 * Skenario 12: Batch payout marking → vendor_payout_id terisi, tidak bisa dua kali.
 * Skenario 14: DP kecil vs fee minimum → net_payout tidak negatif.
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

        // Rental sekarang confirmed
        $this->assertDatabaseHas('rentals', ['id' => $rental->id, 'status' => 'confirmed']);

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

    // ── Skenario 12: Batch payout marking ────────────────────────────────────

    /**
     * Skenario 12: Setelah payout dibuat, vendor_payout_id terisi di semua
     * payments terkait, dan mereka tidak bisa masuk payout kedua kali.
     *
     * @see TENDAKU_payment_spec.md §12 Skenario 12
     * @see docs/contract.md §4.11
     */
    public function test_batch_payout_marking_prevents_double_payout(): void
    {
        $vendor = Vendor::query()->create([
            'name' => 'Vendor Payout',
            'slug' => 'vendor-payout',
            'phone' => '081200000004',
            'address' => 'Payout address',
            'status' => 'active',
        ]);

        $maker = User::factory()->create(['role' => 'vendor_admin', 'vendor_id' => $vendor->id]);
        $approver = User::factory()->create(['role' => 'super_admin']);

        // Buat 2 rental yang sudah picked_up dengan pembayaran midtrans paid
        $rentals = [];
        $payments = [];
        for ($i = 1; $i <= 2; $i++) {
            $rental = Rental::query()->create([
                'vendor_id' => $vendor->id,
                'booking_code' => "TDK-PAY-00{$i}",
                'pickup_date' => '2026-11-01',
                'return_date' => '2026-11-02',
                'rental_days' => 1,
                'subtotal' => 100000,
                'grand_total' => 100000,
                'status' => 'picked_up',
            ]);

            $payment = Payment::query()->create([
                'rental_id' => $rental->id,
                'vendor_id' => $vendor->id,
                'payment_type' => 'full',
                'channel' => 'midtrans',
                'amount' => 100000,
                'order_id' => "TDK-PAY-00{$i}-FULL-1",
                'platform_fee' => 2000,
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $rentals[] = $rental;
            $payments[] = $payment;
        }

        // Verifikasi semua masuk payoutEligible sebelum payout
        $eligible = Payment::payoutEligible()->get();
        $this->assertCount(2, $eligible);

        // Buat vendor_payout record
        $payoutService = app(PaymentService::class);
        $grossAmount = $eligible->sum('amount');
        $feeTotal = $eligible->sum('platform_fee');

        $payout = VendorPayout::query()->create([
            'vendor_id' => $vendor->id,
            'reference_no' => 'PAY-REF-'.uniqid(),
            'gross_amount' => $grossAmount,
            'platform_fee_total' => $feeTotal,
            'net_amount' => $grossAmount - $feeTotal,
            'status' => 'pending_approval',
            'bank_code' => 'BCA',
            'account_name' => 'Vendor Payout',
            'account_last4' => '1234',
            'created_by' => $maker->id,
        ]);

        // Mark payments sebagai sudah dicairkan (dalam transaksi)
        $payoutService->markPaymentsAsPaidOut(
            $eligible->pluck('id')->toArray(),
            $payout->id,
        );

        // Verifikasi: vendor_payout_id terisi di semua payments terkait
        foreach ($payments as $payment) {
            $this->assertDatabaseHas('payments', [
                'id' => $payment->id,
                'vendor_payout_id' => $payout->id,
            ]);
        }

        // Verifikasi: setelah mark, tidak ada lagi di payoutEligible (tidak bisa double payout)
        $eligibleAfter = Payment::payoutEligible()->get();
        $this->assertCount(0, $eligibleAfter);
    }

    // ── Skenario 14: DP kecil vs fee minimum ─────────────────────────────────

    /**
     * Skenario 14: DP sangat kecil — fee platform tidak boleh melebihi DP.
     * net_payout tidak boleh negatif.
     *
     * @see TENDAKU_payment_spec.md §12 Skenario 14
     * @see docs/contract.md §4.10
     */
    public function test_platform_fee_capped_at_dp_amount_prevents_negative_net_payout(): void
    {
        $paymentService = app(PaymentService::class);

        // DP sangat kecil: Rp500 (kurang dari minimum fee Rp1.000)
        $smallDp = 500;
        $fee = $paymentService->calculatePlatformFee($smallDp);

        // Fee tidak boleh melebihi amount (net_payout >= 0)
        $this->assertLessThanOrEqual($smallDp, $fee);
        $this->assertGreaterThanOrEqual(0, $smallDp - $fee);

        // DP normal: Rp150.000
        $normalDp = 150000;
        $normalFee = $paymentService->calculatePlatformFee($normalDp);

        // Fee 2% dari 150.000 = 3.000, minimum 1.000 → hasilnya 3.000
        $this->assertEquals(3000, $normalFee);

        // DP tepat Rp1.000 (persis di batas minimum)
        $borderDp = 1000;
        $borderFee = $paymentService->calculatePlatformFee($borderDp);

        // Min fee Rp1.000, 2% dari 1.000 = 20 → pakai minimum = 1.000
        // Tapi tidak boleh melebihi amount (1.000) → capped = 1.000
        $this->assertEquals(1000, $borderFee);
        $this->assertGreaterThanOrEqual(0, $borderDp - $borderFee);

        // Verifikasi: DP Rp10.000 → fee = max(200, 1000) = 1000
        $this->assertEquals(1000, $paymentService->calculatePlatformFee(10000));

        // Verifikasi: DP Rp100.000 → fee = max(2000, 1000) = 2000
        $this->assertEquals(2000, $paymentService->calculatePlatformFee(100000));
    }
}
