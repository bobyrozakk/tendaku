<?php

namespace Tests\Feature;

use App\Livewire\Kasir\ReturnFlow;
use App\Models\ItemCategory;
use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalDetail;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Payment\MidtransService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class KasirFineQrisPaymentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_cashier_creates_midtrans_qris_fine_payment_and_keeps_it_pending(): void
    {
        ['cashier' => $cashier, 'rental' => $rental, 'detail' => $detail] = $this->createOverdueRental(3);
        $qrisUrl = 'https://api.sandbox.midtrans.com/v2/test-transaction/qr-code';

        $this->mock(MidtransService::class, function (MockInterface $mock) use ($qrisUrl): void {
            $mock->shouldReceive('chargeQris')
                ->once()
                ->andReturnUsing(function (Payment $payment) use ($qrisUrl): array {
                    $payment->update([
                        'midtrans_status' => 'pending',
                        'raw_notification' => [
                            'actions' => [
                                ['name' => 'generate-qr-code', 'method' => 'GET', 'url' => $qrisUrl],
                            ],
                        ],
                    ]);

                    return ['qr_code_url' => $qrisUrl];
                });
        });

        $this->actingAs($cashier);

        Livewire::test(ReturnFlow::class)
            ->set('activeOrderId', $rental->id)
            ->set("returnedItems.{$detail->id}", true)
            ->call('selectFinePaymentMethod', 'qris')
            ->assertHasNoErrors()
            ->assertSee('api.sandbox.midtrans.com')
            ->assertSee('https://simulator.sandbox.midtrans.com/v2/qris/index')
            ->assertSee('Bayar')
            ->assertDontSee('Buat QRIS Midtrans')
            ->assertDontSee('Batalkan tagihan QRIS');

        $this->assertDatabaseHas('payments', [
            'rental_id' => $rental->id,
            'payment_type' => 'fine',
            'payment_channel' => 'qris',
            'amount' => 75000,
            'status' => 'pending',
        ]);
    }

    public function test_cashier_cancels_pending_qris_automatically_when_switching_to_cash(): void
    {
        ['cashier' => $cashier, 'rental' => $rental] = $this->createOverdueRental(1);
        $payment = $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => 'fine',
            'channel' => 'midtrans',
            'payment_channel' => 'qris',
            'method' => 'qris',
            'order_id' => 'TDKFINEQRISTEST62',
            'amount' => 25000,
            'status' => 'pending',
        ]);

        $this->mock(MidtransService::class, function (MockInterface $mock) use ($payment): void {
            $mock->shouldReceive('cancelTransaction')
                ->once()
                ->with(Mockery::on(fn (Payment $requestedPayment): bool => $requestedPayment->is($payment)))
                ->andReturn(true);
        });

        $this->actingAs($cashier);

        Livewire::test(ReturnFlow::class)
            ->set('activeOrderId', $rental->id)
            ->set('finePaymentMethod', 'qris')
            ->set('finePaymentId', $payment->id)
            ->call('selectFinePaymentMethod', 'cash')
            ->assertSet('finePaymentMethod', 'cash')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'cancelled',
            'midtrans_status' => 'cancel',
        ]);
    }

    public function test_cashier_cancels_pending_qris_automatically_when_closing_the_modal(): void
    {
        ['cashier' => $cashier, 'rental' => $rental] = $this->createOverdueRental(1);
        $payment = $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => 'fine',
            'channel' => 'midtrans',
            'payment_channel' => 'qris',
            'method' => 'qris',
            'order_id' => 'TDKFINEQRISTEST62',
            'amount' => 25000,
            'status' => 'pending',
        ]);

        $this->mock(MidtransService::class, function (MockInterface $mock) use ($payment): void {
            $mock->shouldReceive('cancelTransaction')
                ->once()
                ->with(Mockery::on(fn (Payment $requestedPayment): bool => $requestedPayment->is($payment)))
                ->andReturn(true);
        });

        $this->actingAs($cashier);

        Livewire::test(ReturnFlow::class)
            ->set('activeOrderId', $rental->id)
            ->set('finePaymentMethod', 'qris')
            ->set('finePaymentId', $payment->id)
            ->set('showFineModal', true)
            ->call('closeFineModal')
            ->assertSet('showFineModal', false)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'cancelled',
            'midtrans_status' => 'cancel',
        ]);
    }

    public function test_cashier_retries_with_a_new_order_id_after_midtrans_reports_a_conflict(): void
    {
        ['cashier' => $cashier, 'rental' => $rental, 'detail' => $detail] = $this->createOverdueRental(1);
        $conflictedPayment = $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => 'fine',
            'channel' => 'midtrans',
            'payment_channel' => 'qris',
            'method' => 'qris',
            'order_id' => 'TDKFINEQRISTEST62',
            'amount' => 25000,
            'status' => 'pending',
        ]);
        $qrisUrl = 'https://api.sandbox.midtrans.com/v2/test-transaction/qr-code';
        $chargeAttempts = 0;

        $this->mock(MidtransService::class, function (MockInterface $mock) use (
            $conflictedPayment,
            $qrisUrl,
            &$chargeAttempts,
        ): void {
            $mock->shouldReceive('chargeQris')
                ->twice()
                ->andReturnUsing(function (Payment $payment) use ($qrisUrl, &$chargeAttempts): array {
                    $chargeAttempts++;

                    if ($chargeAttempts === 1) {
                        throw new \Exception('Order ID already exists.', 406);
                    }

                    $payment->update([
                        'midtrans_status' => 'pending',
                        'raw_notification' => [
                            'actions' => [
                                ['name' => 'generate-qr-code', 'method' => 'GET', 'url' => $qrisUrl],
                            ],
                        ],
                    ]);

                    return ['qr_code_url' => $qrisUrl];
                });
            $mock->shouldReceive('syncStatus')
                ->once()
                ->with(Mockery::on(fn (Payment $payment): bool => $payment->is($conflictedPayment)))
                ->andReturn($conflictedPayment);
            $mock->shouldReceive('cancelTransaction')
                ->once()
                ->with(Mockery::on(fn (Payment $payment): bool => $payment->is($conflictedPayment)))
                ->andReturn(true);
        });

        $this->actingAs($cashier);

        Livewire::test(ReturnFlow::class)
            ->set('activeOrderId', $rental->id)
            ->set("returnedItems.{$detail->id}", true)
            ->set('finePaymentMethod', 'qris')
            ->call('createFineQrisPayment')
            ->assertHasNoErrors();

        $replacementPayment = $rental->payments()
            ->where('payment_type', 'fine')
            ->where('status', 'pending')
            ->firstOrFail();

        $this->assertDatabaseHas('payments', [
            'id' => $conflictedPayment->id,
            'status' => 'cancelled',
        ]);
        $this->assertNotSame($conflictedPayment->order_id, $replacementPayment->order_id);
        $this->assertDatabaseHas('payments', [
            'id' => $replacementPayment->id,
            'payment_type' => 'fine',
            'payment_channel' => 'qris',
            'amount' => 25000,
            'status' => 'pending',
        ]);
    }

    public function test_cashier_does_not_create_another_invoice_when_conflicted_order_is_already_paid(): void
    {
        ['cashier' => $cashier, 'rental' => $rental, 'detail' => $detail] = $this->createOverdueRental(1);
        $payment = $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => 'fine',
            'channel' => 'midtrans',
            'payment_channel' => 'qris',
            'method' => 'qris',
            'order_id' => 'TDKFINEQRISTEST62',
            'amount' => 25000,
            'status' => 'pending',
        ]);

        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('chargeQris')
                ->once()
                ->andThrow(new \Exception('Order ID already exists.', 406));
            $mock->shouldReceive('syncStatus')
                ->once()
                ->andReturnUsing(function (Payment $requestedPayment): Payment {
                    $requestedPayment->update([
                        'status' => 'paid',
                        'midtrans_status' => 'settlement',
                        'paid_at' => now(),
                    ]);

                    return $requestedPayment->fresh();
                });
            $mock->shouldNotReceive('cancelTransaction');
        });

        $this->actingAs($cashier);

        Livewire::test(ReturnFlow::class)
            ->set('activeOrderId', $rental->id)
            ->set("returnedItems.{$detail->id}", true)
            ->set('finePaymentMethod', 'qris')
            ->call('createFineQrisPayment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'payment_type' => 'fine',
            'status' => 'paid',
        ]);
        $this->assertSame(1, $rental->payments()->where('payment_type', 'fine')->count());
    }

    public function test_cashier_cannot_complete_return_until_midtrans_confirms_qris_payment(): void
    {
        ['cashier' => $cashier, 'rental' => $rental, 'detail' => $detail] = $this->createOverdueRental(3);
        $payment = $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => 'fine',
            'channel' => 'midtrans',
            'payment_channel' => 'qris',
            'method' => 'qris',
            'order_id' => 'TDK-FINE-QRIS-PENDING-1',
            'amount' => 75000,
            'status' => 'pending',
        ]);

        $this->mock(MidtransService::class, function (MockInterface $mock) use ($payment): void {
            $mock->shouldReceive('syncStatus')
                ->once()
                ->with(Mockery::on(fn (Payment $requestedPayment): bool => $requestedPayment->is($payment)))
                ->andReturn($payment);
        });

        $this->actingAs($cashier);

        Livewire::test(ReturnFlow::class)
            ->set('activeOrderId', $rental->id)
            ->set("returnedItems.{$detail->id}", true)
            ->set('finePaymentMethod', 'qris')
            ->set('finePaymentId', $payment->id)
            ->call('processFinePayment')
            ->assertHasErrors('finePayment');

        $this->assertDatabaseHas('rentals', [
            'id' => $rental->id,
            'status' => 'picked_up',
            'total_late_fee' => 0,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);
    }

    public function test_cashier_completes_return_after_verified_qris_payment_without_duplicate_payment(): void
    {
        ['cashier' => $cashier, 'rental' => $rental, 'detail' => $detail] = $this->createOverdueRental(1);
        $payment = $rental->payments()->create([
            'vendor_id' => $rental->vendor_id,
            'payment_type' => 'fine',
            'channel' => 'midtrans',
            'payment_channel' => 'qris',
            'method' => 'qris',
            'order_id' => 'TDK-FINE-QRIS-PAID-1',
            'amount' => 25000,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('syncStatus');
        });

        $this->actingAs($cashier);

        Livewire::test(ReturnFlow::class)
            ->set('activeOrderId', $rental->id)
            ->set("returnedItems.{$detail->id}", true)
            ->set('finePaymentMethod', 'qris')
            ->set('finePaymentId', $payment->id)
            ->call('processFinePayment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('rentals', [
            'id' => $rental->id,
            'status' => 'completed',
            'total_late_fee' => 25000,
        ]);
        $this->assertSame(
            1,
            $rental->payments()->where('payment_type', 'fine')->count(),
        );
    }

    public function test_signed_qris_image_link_is_shareable_and_rejects_unsigned_requests(): void
    {
        ['vendor' => $vendor, 'rental' => $rental] = $this->createOverdueRental(1);
        $payment = $rental->payments()->create([
            'vendor_id' => $vendor->id,
            'payment_type' => 'fine',
            'channel' => 'midtrans',
            'payment_channel' => 'qris',
            'order_id' => 'TDK-FINE-QRIS-IMAGE-1',
            'amount' => 25000,
            'status' => 'pending',
            'raw_notification' => [
                'actions' => [
                    ['name' => 'generate-qr-code', 'url' => 'https://api.sandbox.midtrans.com/v2/test/qr-code'],
                ],
            ],
        ]);
        $signedUrl = URL::temporarySignedRoute(
            'kasir.fine-qris-image',
            now()->addMinutes(10),
            ['payment' => $payment->id],
        );

        $this->mock(MidtransService::class, function (MockInterface $mock) use ($payment): void {
            $mock->shouldReceive('getQrisImage')
                ->once()
                ->with(Mockery::on(fn (Payment $requestedPayment): bool => $requestedPayment->is($payment)))
                ->andReturn('test-png-content');
        });

        $this->get($signedUrl)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertContent('test-png-content');

        $this->get(route('kasir.fine-qris-image', $payment))
            ->assertStatus(403);
    }

    /**
     * @return array{vendor: Vendor, cashier: User, rental: Rental, detail: RentalDetail}
     */
    private function createOverdueRental(int $lateDays): array
    {
        $this->travelTo(Carbon::parse('2026-11-20 12:00:00'));

        $vendor = Vendor::create([
            'name' => 'Toko Outdoor Test',
            'slug' => 'toko-outdoor-test',
            'phone' => '081234567890',
            'address' => 'Jl. Ijen No. 10 Malang',
            'city' => 'Malang',
            'status' => 'approved',
        ]);
        $cashier = User::factory()->create([
            'vendor_id' => $vendor->id,
            'role' => 'vendor_admin',
        ]);
        $customer = User::factory()->create(['role' => 'customer']);
        $category = ItemCategory::create([
            'vendor_id' => $vendor->id,
            'name' => 'Tenda',
            'slug' => 'tenda',
        ]);
        $item = MasterItem::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'TEST UNIT',
            'slug' => 'test-unit',
            'daily_rate' => 50000,
            'late_fee_per_day' => 25000,
        ]);
        $unit = ItemUnit::create([
            'vendor_id' => $vendor->id,
            'master_item_id' => $item->id,
            'unit_code' => 'TEST-001',
            'barcode' => 'TEST-BARCODE-001',
            'status' => 'rented',
            'condition' => 'good',
        ]);
        $rental = Rental::create([
            'vendor_id' => $vendor->id,
            'user_id' => $customer->id,
            'booking_code' => 'TDK-FINE-QRIS-TEST',
            'pickup_date' => Carbon::today()->subDays(5),
            'return_date' => Carbon::today()->subDays($lateDays),
            'rental_days' => 3,
            'subtotal' => 150000,
            'grand_total' => 150000,
            'final_total' => 150000,
            'status' => 'picked_up',
        ]);
        $detail = RentalDetail::create([
            'rental_id' => $rental->id,
            'master_item_id' => $item->id,
            'item_unit_id' => $unit->id,
            'item_name_snapshot' => $item->name,
            'daily_rate_snapshot' => 50000,
            'late_fee_per_day_snapshot' => 25000,
            'quantity' => 1,
            'subtotal' => 150000,
            'is_returned' => false,
        ]);

        return compact('vendor', 'cashier', 'rental', 'detail');
    }
}
