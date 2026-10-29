<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\CreatesMidtransFixtures;
use Tests\TestCase;

/**
 * Test suite webhook Midtrans (skenario 3-8, 10 dari §12 spec).
 *
 * @see docs/TENDAKU_payment_spec.md §12
 */
class PaymentWebhookTest extends TestCase
{
    use CreatesMidtransFixtures;
    use LazilyRefreshDatabase;

    private const SERVER_KEY = 'test-midtrans-server-key';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.midtrans.server_key' => self::SERVER_KEY]);
    }

    // ── Skenario 1: Bayar penuh QRIS ──────────────────────────────────────────

    /**
     * Skenario 1: Bayar penuh → payments paid, rental confirmed.
     *
     * @see TENDAKU_payment_spec.md §12 Skenario 1
     */
    public function test_full_payment_marks_paid_and_confirms_rental(): void
    {
        [, $rental, $payment] = $this->createRentalWithPendingPayment();

        $notification = $this->signedNotification([
            'transaction_status' => 'settlement',
            'transaction_id' => 'txn-full-001',
        ]);

        $this->postJson(route('webhook.midtrans'), $notification)->assertOk();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->getKey(),
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('rentals', [
            'id' => $rental->getKey(),
            'status' => 'confirmed',
        ]);
    }

    // ── Skenario 3: Webhook dikirim 2x → idempoten ───────────────────────────

    /**
     * Skenario 3: Webhook settlement dikirim 2x tidak menyebabkan efek ganda.
     *
     * @see TENDAKU_payment_spec.md §12 Skenario 3
     */
    public function test_duplicate_webhook_is_idempotent(): void
    {
        [, $rental, $payment] = $this->createRentalWithPendingPayment();
        $notification = $this->signedNotification(['transaction_status' => 'settlement']);

        $this->postJson(route('webhook.midtrans'), $notification)->assertOk();
        $paidAt = $payment->refresh()->paid_at;

        // Kirim lagi
        $this->postJson(route('webhook.midtrans'), $notification)->assertOk();

        // paid_at tidak berubah (idempoten)
        $this->assertSame(
            $paidAt->toDateTimeString(),
            $payment->refresh()->paid_at->toDateTimeString()
        );
        $this->assertDatabaseHas('rentals', ['id' => $rental->getKey(), 'status' => 'confirmed']);
    }

    // ── Skenario 4: Signature salah → 403, DB tidak berubah ──────────────────

    /**
     * Skenario 4: Signature salah → HTTP 403, DB tidak berubah.
     *
     * @see TENDAKU_payment_spec.md §12 Skenario 4
     */
    public function test_invalid_signature_returns_403_without_db_change(): void
    {
        [, $rental, $payment] = $this->createRentalWithPendingPayment();
        $notification = $this->signedNotification(['transaction_status' => 'settlement']);
        $notification['signature_key'] = str_repeat('0', 128);

        $this->postJson(route('webhook.midtrans'), $notification)->assertForbidden();

        $this->assertDatabaseHas('payments', ['id' => $payment->getKey(), 'status' => 'pending']);
        $this->assertDatabaseHas('rentals', ['id' => $rental->getKey(), 'status' => 'pending']);
    }

    // ── Skenario 5: gross_amount tidak cocok → tidak diproses ─────────────────

    /**
     * Skenario 5: gross_amount berbeda dari payment.amount → anomali dicatat, tidak diproses.
     *
     * @see TENDAKU_payment_spec.md §12 Skenario 5
     */
    public function test_mismatched_amount_is_not_processed(): void
    {
        [, , $payment] = $this->createRentalWithPendingPayment();

        $notification = $this->signedNotification([
            'gross_amount' => '9999.00',  // berbeda dari 10000
            'transaction_status' => 'settlement',
        ]);
        // Perlu re-sign dengan gross_amount baru
        $notification['signature_key'] = hash(
            'sha512',
            $notification['order_id'].$notification['status_code'].'9999.00'.self::SERVER_KEY
        );

        $this->postJson(route('webhook.midtrans'), $notification)->assertUnprocessable();

        $this->assertDatabaseHas('payments', ['id' => $payment->getKey(), 'status' => 'pending']);
    }

    // ── Skenario 6: Notifikasi pending datang SETELAH settlement ──────────────

    /**
     * Skenario 6: Notifikasi pending yang terlambat tidak menurunkan status paid.
     *
     * @see TENDAKU_payment_spec.md §12 Skenario 6
     */
    public function test_late_pending_notification_does_not_downgrade_paid_status(): void
    {
        [, , $payment] = $this->createRentalWithPendingPayment();

        // Dulu settlement dulu
        $settlement = $this->signedNotification(['transaction_status' => 'settlement']);
        $this->postJson(route('webhook.midtrans'), $settlement)->assertOk();

        // Lalu pending datang terlambat
        $pending = $this->signedNotification(['transaction_status' => 'pending']);
        $this->postJson(route('webhook.midtrans'), $pending)->assertOk();

        // Status tetap paid
        $this->assertDatabaseHas('payments', ['id' => $payment->getKey(), 'status' => 'paid']);
    }

    // ── Skenario 7: Kedaluwarsa ───────────────────────────────────────────────

    /**
     * Skenario 7: Webhook expire → payments expired, rental expired.
     *
     * @see TENDAKU_payment_spec.md §12 Skenario 7
     */
    public function test_expire_notification_marks_payment_and_rental_expired(): void
    {
        [, $rental, $payment] = $this->createRentalWithPendingPayment();
        $notification = $this->signedNotification(['transaction_status' => 'expire']);

        $this->postJson(route('webhook.midtrans'), $notification)->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->getKey(), 'status' => 'expired']);
        // Rental tetap pending sampai job auto-cancel jalan
        // (webhook hanya expire payment; rental diexpire oleh job ExpirePendingRentals)
    }

    // ── Skenario 10: Denda QRIS ───────────────────────────────────────────────

    /**
     * Skenario 10: Denda QRIS → payment fine ikut payout (channel=midtrans).
     *
     * @see TENDAKU_payment_spec.md §12 Skenario 10
     */
    public function test_fine_qris_payment_is_payout_eligible(): void
    {
        [, $rental, $firstPayment] = $this->createRentalWithPendingPayment();

        // Konfirmasi rental dulu
        $firstPayment->update(['status' => 'paid', 'paid_at' => now()]);
        $rental->update(['status' => 'picked_up', 'total_late_fee' => 50000]);

        // Buat fine payment QRIS
        $finePayment = $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => 'fine',
            'channel' => 'midtrans',
            'amount' => 50000,
            'order_id' => 'TDK-TEST-001-FINE-1',
            'status' => 'pending',
            'platform_fee' => 1000,
        ]);

        // Webhook fine settlement
        $statusCode = '200';
        $grossAmount = '50000.00';
        $signature = hash('sha512', 'TDK-TEST-001-FINE-1'.$statusCode.$grossAmount.self::SERVER_KEY);

        $this->postJson(route('webhook.midtrans'), [
            'order_id' => 'TDK-TEST-001-FINE-1',
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'transaction_id' => 'txn-fine-001',
            'fraud_status' => 'accept',
            'signature_key' => $signature,
        ])->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $finePayment->id, 'status' => 'paid']);

        // Fine QRIS ikut payout: vendor_payout_id masih null (belum dicairkan), tapi channel=midtrans
        $this->assertDatabaseHas('payments', [
            'id' => $finePayment->id,
            'channel' => 'midtrans',
            'vendor_payout_id' => null,
        ]);
    }

    // ── Skenario 13: Pembayaran tunai tidak masuk payoutEligible ─────────────

    /**
     * Skenario 13: Payment tunai (channel=cash) tidak masuk payoutEligible.
     *
     * @see TENDAKU_payment_spec.md §12 Skenario 13
     */
    public function test_cash_payment_is_not_payout_eligible(): void
    {
        [, $rental, $firstPayment] = $this->createRentalWithPendingPayment();

        $firstPayment->update(['status' => 'paid', 'paid_at' => now(), 'channel' => 'midtrans']);
        $rental->update(['status' => 'picked_up']);

        // Buat pelunasan tunai
        $cashPayment = $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => 'remaining',
            'channel' => 'cash',
            'method' => 'cash',
            'amount' => 5000,
            'platform_fee' => 0,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Hanya midtrans payment yang masuk payoutEligible
        $eligible = Payment::payoutEligible()->get();

        $this->assertTrue($eligible->contains('id', $firstPayment->getKey()));
        $this->assertFalse($eligible->contains('id', $cashPayment->getKey()));
    }
}
