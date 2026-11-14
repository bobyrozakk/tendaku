<?php

namespace Tests\Feature;

use App\Models\ItemCategory;
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
use Mockery\MockInterface;
use Tests\TestCase;

class BookingStatusAndHistoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Vendor $vendor;

    private User $customer1;

    private User $customer2;

    private MasterItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-10 10:00:00');

        config(['services.midtrans.server_key' => 'test-midtrans-server-key']);

        $this->vendor = Vendor::create([
            'name' => 'Tendaku Vendor Store',
            'slug' => 'tendaku-vendor-store',
            'phone' => '081234567890',
            'address' => 'Jl. Ijen No. 45',
            'city' => 'Malang',
            'status' => 'active',
        ]);

        $this->customer1 = User::factory()->create([
            'name' => 'Budi Penyewa',
            'email' => 'budi@example.com',
            'phone' => '085712345678',
            'role' => 'customer',
        ]);

        $this->customer2 = User::factory()->create([
            'name' => 'Siti Penyewa Lain',
            'email' => 'siti@example.com',
            'phone' => '085787654321',
            'role' => 'customer',
        ]);

        $category = ItemCategory::create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Tenda Dome',
            'slug' => 'tenda-dome',
        ]);

        $this->item = MasterItem::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $category->id,
            'name' => 'Tenda Consina 4P',
            'slug' => 'tenda-consina-4p',
            'daily_rate' => 50000,
            'late_fee_per_day' => 25000,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_customer_can_see_booking_history_with_status_filter_and_pagination(): void
    {
        // Buat 3 rental untuk customer 1
        $r1 = $this->createRental($this->customer1->id, 'TDK-2026-BUDI01', 'pending');
        $r2 = $this->createRental($this->customer1->id, 'TDK-2026-BUDI02', 'confirmed');
        $r3 = $this->createRental($this->customer1->id, 'TDK-2026-BUDI03', 'completed');

        // Buat 1 rental untuk customer 2 (tidak boleh terlihat oleh customer 1)
        $rOther = $this->createRental($this->customer2->id, 'TDK-2026-SITI01', 'confirmed');

        $response = $this->actingAs($this->customer1)
            ->get(route('customer.booking.index'));

        $response->assertStatus(200);
        $response->assertSee('TDK-2026-BUDI01');
        $response->assertSee('TDK-2026-BUDI02');
        $response->assertSee('TDK-2026-BUDI03');
        $response->assertDontSee('TDK-2026-SITI01');

        // Filter status 'confirmed'
        $responseFilter = $this->actingAs($this->customer1)
            ->get(route('customer.booking.index', ['status' => 'confirmed']));

        $responseFilter->assertStatus(200);
        $responseFilter->assertSee('TDK-2026-BUDI02');
        $responseFilter->assertDontSee('TDK-2026-BUDI01');
        $responseFilter->assertDontSee('TDK-2026-BUDI03');
    }

    public function test_booking_history_marks_past_due_unpaid_orders_as_expired(): void
    {
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-EXPIRED01', 'pending');
        $rental->update(['expires_at' => Carbon::now()->subMinute()]);
        $payment = $rental->payments()->create([
            'vendor_id' => $this->vendor->id,
            'payment_type' => 'full',
            'payment_method' => 'midtrans_snap',
            'channel' => 'midtrans',
            'order_id' => 'TDK-2026-EXPIRED01-FULL-1',
            'amount' => 150000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->customer1)
            ->get(route('customer.booking.index'));

        $response->assertSee('TDK-2026-EXPIRED01')
            ->assertSee('Kedaluwarsa');

        $this->assertDatabaseHas('rentals', [
            'id' => $rental->id,
            'status' => 'expired',
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'expired',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_booking_history(): void
    {
        $response = $this->get(route('customer.booking.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_owner_can_view_booking_status_with_real_data(): void
    {
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-STATUS1', 'confirmed');

        $response = $this->actingAs($this->customer1)
            ->get(route('booking.status', $rental->booking_code));

        $response->assertStatus(200);
        $response->assertSee('#TDK-2026-STATUS1');
        $response->assertSee('Tendaku Vendor Store');
        $response->assertSee('Tenda Consina 4P');
        $response->assertDontSee('TDK-2026-B8921'); // Bukan data dummy
    }

    public function test_non_owner_is_forbidden_to_view_customer_booking_status(): void
    {
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-STATUS1', 'confirmed');

        // Customer 2 mencoba membuka rental milik customer 1
        $response = $this->actingAs($this->customer2)
            ->get(route('booking.status', $rental->booking_code));

        $response->assertStatus(403);

        // Tamu tanpa login mencoba membuka rental terdaftar
        $responseGuest = $this->get(route('booking.status', $rental->booking_code));
        $responseGuest->assertStatus(403);
    }

    public function test_booking_status_returns_404_when_code_not_found(): void
    {
        $response = $this->actingAs($this->customer1)
            ->get(route('booking.status', 'TDK-NON-EXISTENT'));

        $response->assertStatus(404);
    }

    public function test_guest_can_access_booking_status_with_matching_phone_or_signed_url(): void
    {
        $guestRental = Rental::create([
            'vendor_id' => $this->vendor->id,
            'user_id' => null,
            'guest_name' => 'Anto Tamu',
            'guest_phone' => '081298765432',
            'guest_id_number' => '3507111122223333',
            'booking_code' => 'TDK-2026-GUEST1',
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-12',
            'rental_days' => 3,
            'subtotal' => 150000,
            'grand_total' => 150000,
            'status' => 'pending',
            'expires_at' => Carbon::now()->addHours(2),
        ]);

        RentalDetail::create([
            'rental_id' => $guestRental->id,
            'master_item_id' => $this->item->id,
            'daily_rate_snapshot' => 50000,
        ]);

        // 1. Tanpa nomor telepon -> 403 Forbidden
        $responseWithoutPhone = $this->get(route('booking.status', $guestRental->booking_code));
        $responseWithoutPhone->assertStatus(403);

        // 2. Dengan nomor telepon yang salah -> 403 Forbidden
        $responseWrongPhone = $this->get(route('booking.status', [
            'code' => $guestRental->booking_code,
            'phone' => '089999999999',
        ]));
        $responseWrongPhone->assertStatus(403);

        // 3. Dengan nomor telepon yang cocok -> 200 OK
        $responseValidPhone = $this->get(route('booking.status', [
            'code' => $guestRental->booking_code,
            'phone' => '081298765432',
        ]));
        $responseValidPhone->assertStatus(200);
        $responseValidPhone->assertSee('#TDK-2026-GUEST1');
        $responseValidPhone->assertSee('Tenda Consina 4P');

        // 4. Dengan signed URL -> 200 OK
        $signedUrl = URL::signedRoute('booking.status', ['code' => $guestRental->booking_code]);
        $responseSigned = $this->get($signedUrl);
        $responseSigned->assertStatus(200);
    }

    public function test_booking_status_displays_timeline_countdown_and_fines_correctly(): void
    {
        $rental = Rental::create([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer1->id,
            'booking_code' => 'TDK-2026-FINES1',
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-12',
            'rental_days' => 3,
            'subtotal' => 150000,
            'grand_total' => 150000,
            'total_late_fee' => 50000,
            'total_damage_fee' => 20000,
            'final_total' => 220000,
            'status' => 'returned',
            'ktp_collateral_status' => 'held',
            'ktp_received_at' => Carbon::now()->subDays(2),
        ]);

        RentalDetail::create([
            'rental_id' => $rental->id,
            'master_item_id' => $this->item->id,
            'daily_rate_snapshot' => 50000,
        ]);

        $response = $this->actingAs($this->customer1)
            ->get(route('booking.status', $rental->booking_code));

        $response->assertStatus(200);
        $response->assertSee('Denda keterlambatan');
        $response->assertSee('Rp 50.000');
        $response->assertSee('Denda kerusakan unit');
        $response->assertSee('Rp 20.000');
        $response->assertSee('Rp 220.000');
        $response->assertSee('Bayar denda');
    }

    public function test_customer_downloads_order_receipt_as_pdf(): void
    {
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-RCPT01', 'confirmed');

        $response = $this->actingAs($this->customer1)
            ->get(route('booking.receipt', $rental->booking_code));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'receipt-TDK-2026-RCPT01.pdf',
            (string) $response->headers->get('content-disposition')
        );
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_order_receipt_html_view_still_available_with_format_html(): void
    {
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-RCPT02', 'confirmed');

        $response = $this->actingAs($this->customer1)
            ->get(route('booking.receipt', ['code' => $rental->booking_code, 'format' => 'html']));

        $response->assertStatus(200);
        $response->assertSee('BUKTI TRANSAKSI PENYEWAAN');
        $response->assertSee('TDK-2026-RCPT02');
        $response->assertSee('Budi Penyewa');
        $response->assertSee('Tendaku Vendor Store');
    }

    public function test_pay_returns_virtual_account_info_for_pending_rental(): void
    {
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-SNAP01', 'pending');
        $rental->payments()->create([
            'vendor_id' => $this->vendor->id,
            'payment_type' => 'full',
            'channel' => 'midtrans',
            'order_id' => 'TDK-2026-SNAP01-FULL-1',
            'amount' => 150000,
            'status' => 'pending',
        ]);

        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('chargeBankTransfer')
                ->once()
                ->with(\Mockery::type(Payment::class), 'bca')
                ->andReturnUsing(function (Payment $p, string $bank): array {
                    $p->update([
                        'raw_notification' => [
                            'va_numbers' => [
                                ['bank' => $bank, 'va_number' => '1234567890'],
                            ],
                        ],
                    ]);

                    return [
                        'bank' => $bank,
                        'va_number' => '1234567890',
                        'biller_code' => null,
                        'qr_code_url' => null,
                    ];
                });
        });

        $response = $this->actingAs($this->customer1)
            ->postJson(route('booking.pay', $rental->booking_code));

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'order_id' => 'TDK-2026-SNAP01-FULL-1',
                'amount' => 150000,
                'is_qris' => false,
                'va_info' => [
                    'bank' => 'bca',
                    'va_number' => '1234567890',
                ],
            ]);
    }

    public function test_pay_returns_qris_details_and_persists_the_midtrans_response(): void
    {
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-QRIS01', 'pending');
        $payment = $rental->payments()->create([
            'vendor_id' => $this->vendor->id,
            'payment_type' => 'full',
            'payment_channel' => 'qris',
            'channel' => 'midtrans',
            'order_id' => 'TDK-2026-QRIS01-FULL-1',
            'amount' => 150000,
            'status' => 'pending',
        ]);
        $qrisUrl = 'https://api.sandbox.midtrans.com/v2/qris/test-transaction/qr-code';

        $this->mock(MidtransService::class, function (MockInterface $mock) use ($payment, $qrisUrl): void {
            $mock->shouldReceive('chargeQris')
                ->once()
                ->with(\Mockery::on(fn (Payment $requestedPayment): bool => $requestedPayment->is($payment)))
                ->andReturnUsing(function (Payment $requestedPayment) use ($qrisUrl): array {
                    $requestedPayment->update([
                        'method' => 'qris',
                        'raw_notification' => [
                            'actions' => [
                                ['name' => 'generate-qr-code', 'method' => 'GET', 'url' => $qrisUrl],
                            ],
                        ],
                    ]);

                    return ['qr_code_url' => $qrisUrl];
                });
        });

        $response = $this->actingAs($this->customer1)
            ->postJson(route('booking.pay', $rental->booking_code));

        $response->assertOk()->assertJson([
            'status' => 'success',
            'order_id' => 'TDK-2026-QRIS01-FULL-1',
            'is_qris' => true,
            'qris_info' => ['qr_code_url' => $qrisUrl],
            'amount' => 150000,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->getKey(),
            'payment_channel' => 'qris',
            'method' => 'qris',
        ]);
        $this->assertSame($qrisUrl, $payment->fresh()->qris_details['qr_code_url']);
    }

    public function test_status_page_generates_and_opens_qris_popup_for_pending_qris_payment(): void
    {
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-QRIS02', 'pending');
        $payment = $rental->payments()->create([
            'vendor_id' => $this->vendor->id,
            'payment_type' => 'full',
            'payment_channel' => 'qris',
            'channel' => 'midtrans',
            'order_id' => 'TDK-2026-QRIS02-FULL-1',
            'amount' => 150000,
            'status' => 'pending',
        ]);
        $qrisUrl = 'https://api.sandbox.midtrans.com/v2/qris/test-transaction/qr-code';

        $this->mock(MidtransService::class, function (MockInterface $mock) use ($qrisUrl): void {
            $mock->shouldReceive('chargeQris')
                ->once()
                ->andReturnUsing(function (Payment $requestedPayment) use ($qrisUrl): array {
                    $requestedPayment->update([
                        'method' => 'qris',
                        'raw_notification' => [
                            'actions' => [
                                ['name' => 'generate-qr-code-v2', 'method' => 'GET', 'url' => $qrisUrl],
                            ],
                        ],
                    ]);

                    return ['qr_code_url' => $qrisUrl];
                });
        });

        $this->actingAs($this->customer1)
            ->get(route('booking.status', $rental->booking_code))
            ->assertOk()
            ->assertSee('Bayar dengan QRIS')
            ->assertSee('x-bind:src', false);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->getKey(),
            'payment_channel' => 'qris',
            'method' => 'qris',
        ]);
        $this->assertSame($qrisUrl, $payment->fresh()->qris_details['qr_code_url']);
    }

    public function test_only_the_booking_owner_can_load_its_qris_image(): void
    {
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-QRIS03', 'pending');
        $rental->payments()->create([
            'vendor_id' => $this->vendor->id,
            'payment_type' => 'full',
            'payment_channel' => 'qris',
            'channel' => 'midtrans',
            'order_id' => 'TDK-2026-QRIS03-FULL-1',
            'amount' => 150000,
            'status' => 'pending',
            'raw_notification' => [
                'actions' => [
                    [
                        'name' => 'generate-qr-code',
                        'method' => 'GET',
                        'url' => 'https://api.sandbox.midtrans.com/v2/qris/test-transaction/qr-code',
                    ],
                ],
            ],
        ]);
        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getQrisImage')
                ->once()
                ->andReturn('png-image-data');
        });

        $this->actingAs($this->customer1)
            ->get(route('booking.qris-image', $rental->booking_code))
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertContent('png-image-data');

        $this->actingAs($this->customer2)
            ->get(route('booking.qris-image', $rental->booking_code))
            ->assertForbidden();
    }

    public function test_status_page_auto_generates_va_for_pending_rental(): void
    {
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-VA01', 'pending');
        $rental->payments()->create([
            'vendor_id' => $this->vendor->id,
            'payment_type' => 'full',
            'channel' => 'midtrans',
            'order_id' => 'TDK-2026-VA01-FULL-1',
            'amount' => 150000,
            'status' => 'pending',
        ]);

        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('chargeBankTransfer')
                ->once()
                ->with(\Mockery::type(Payment::class), 'bca')
                ->andReturnUsing(function (Payment $p, string $bank): array {
                    $p->update([
                        'raw_notification' => [
                            'va_numbers' => [
                                ['bank' => $bank, 'va_number' => '1234567890'],
                            ],
                        ],
                    ]);

                    return [
                        'bank' => $bank,
                        'va_number' => '1234567890',
                        'biller_code' => null,
                        'qr_code_url' => null,
                    ];
                });
        });

        $this->actingAs($this->customer1)
            ->get(route('booking.status', $rental->booking_code))
            ->assertOk()
            ->assertSee('1234567890');
    }

    public function test_sync_payment_status_endpoint_returns_payment_status(): void
    {
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-SYNC01', 'pending');
        $rental->payments()->create([
            'vendor_id' => $this->vendor->id,
            'payment_type' => 'full',
            'channel' => 'midtrans',
            'order_id' => 'TDK-2026-SYNC01-FULL-1',
            'amount' => 150000,
            'status' => 'pending',
        ]);

        // Jangan menembak API Midtrans asli: status dikembalikan apa adanya.
        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('syncStatus')
                ->once()
                ->andReturnUsing(fn ($payment) => $payment);
        });

        $response = $this->actingAs($this->customer1)
            ->postJson(route('booking.sync', $rental->booking_code));

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'order_id' => 'TDK-2026-SYNC01-FULL-1',
                'payment_status' => 'pending',
                'is_paid' => false,
            ]);
    }

    public function test_customer_can_cancel_confirmed_order_before_handover(): void
    {
        $this->mock(MidtransService::class);
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-CNCL01', 'confirmed');

        $response = $this->actingAs($this->customer1)
            ->post(route('booking.cancel', $rental->booking_code), [
                'cancel_reason' => 'schedule_changed',
                'cancel_note' => 'Jadwal kantor bentrok',
                'confirm_cancel' => '1',
            ]);

        $response->assertRedirect(route('booking.status', $rental->booking_code));
        $response->assertSessionHas('success');

        $rental->refresh();
        $this->assertSame('cancelled', $rental->status);
        $this->assertSame('schedule_changed', $rental->cancel_reason);
        $this->assertSame('Jadwal kantor bentrok', $rental->cancel_note);
        $this->assertNotNull($rental->cancelled_at);
    }

    public function test_cancel_requires_note_when_reason_is_other(): void
    {
        $this->mock(MidtransService::class);
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-CNCL02', 'confirmed');

        $response = $this->actingAs($this->customer1)
            ->post(route('booking.cancel', $rental->booking_code), [
                'cancel_reason' => 'other',
                'confirm_cancel' => '1',
            ]);

        $response->assertSessionHasErrors('cancel_note');
        $this->assertSame('confirmed', $rental->fresh()->status);
    }

    public function test_cannot_cancel_order_that_was_already_picked_up(): void
    {
        $this->mock(MidtransService::class);
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-CNCL03', 'picked_up');

        $response = $this->actingAs($this->customer1)
            ->post(route('booking.cancel', $rental->booking_code), [
                'cancel_reason' => 'wrong_order',
                'confirm_cancel' => '1',
            ]);

        $response->assertSessionHas('error');
        $this->assertSame('picked_up', $rental->fresh()->status);
    }

    public function test_other_customer_cannot_cancel_someone_elses_order(): void
    {
        $this->mock(MidtransService::class);
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-CNCL04', 'confirmed');

        $this->actingAs($this->customer2)
            ->post(route('booking.cancel', $rental->booking_code), [
                'cancel_reason' => 'wrong_order',
                'confirm_cancel' => '1',
            ])
            ->assertForbidden();

        $this->assertSame('confirmed', $rental->fresh()->status);
    }

    public function test_customer_can_switch_virtual_account_bank(): void
    {
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-SWVA01', 'pending');
        $rental->payments()->create([
            'vendor_id' => $this->vendor->id,
            'payment_type' => 'full',
            'payment_channel' => 'bca_va',
            'channel' => 'midtrans',
            'order_id' => 'TDK2026SWVA0111',
            'amount' => 150000,
            'status' => 'pending',
        ]);

        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('chargeBankTransfer')
                ->once()
                ->with(\Mockery::type(Payment::class), 'bni')
                ->andReturn([
                    'bank' => 'bni',
                    'va_number' => '9876543210',
                    'biller_code' => null,
                ]);
        });

        $response = $this->actingAs($this->customer1)
            ->postJson(route('booking.va', $rental->booking_code), ['bank' => 'bni']);

        $response->assertOk()
            ->assertJson([
                'bank' => 'bni',
                'va_number' => '9876543210',
                'order_id' => 'TDK2026SWVA0121',
            ]);
    }

    public function test_customer_can_switch_to_qris_and_open_its_popup(): void
    {
        $rental = $this->createRental($this->customer1->id, 'TDK-2026-SWQR01', 'pending');
        $rental->payments()->create([
            'vendor_id' => $this->vendor->id,
            'payment_type' => 'full',
            'payment_channel' => 'bca_va',
            'channel' => 'midtrans',
            'order_id' => 'TDK-2026-SWQR01-FULL-1',
            'amount' => 150000,
            'status' => 'pending',
        ]);
        $qrisUrl = 'https://api.sandbox.midtrans.com/v2/qris/test-transaction/qr-code';

        $this->mock(MidtransService::class, function (MockInterface $mock) use ($qrisUrl): void {
            $mock->shouldReceive('chargeQris')
                ->once()
                ->andReturnUsing(function (Payment $payment) use ($qrisUrl): array {
                    $payment->update([
                        'method' => 'qris',
                        'raw_notification' => [
                            'actions' => [
                                ['name' => 'generate-qr-code-v2', 'method' => 'GET', 'url' => $qrisUrl],
                            ],
                        ],
                    ]);

                    return ['qr_code_url' => $qrisUrl];
                });
        });

        $this->actingAs($this->customer1)
            ->postJson(route('booking.va', $rental->booking_code), ['bank' => 'qris'])
            ->assertOk()
            ->assertJsonPath('is_qris', true)
            ->assertJsonPath('qris_info.qr_code_url', $qrisUrl);

        $this->assertDatabaseHas('payments', [
            'rental_id' => $rental->getKey(),
            'payment_channel' => 'qris',
            'method' => 'qris',
            'status' => 'pending',
        ]);

        $this->actingAs($this->customer1)
            ->get(route('booking.status', $rental->booking_code))
            ->assertOk()
            ->assertSee('Bayar dengan QRIS')
            ->assertSee('QRIS')
            ->assertSee('x-bind:src', false);
    }

    public function test_guest_can_load_qris_image_with_booking_phone(): void
    {
        $rental = Rental::create([
            'vendor_id' => $this->vendor->id,
            'user_id' => null,
            'guest_name' => 'Anto Tamu',
            'guest_phone' => '081298765432',
            'guest_id_number' => '3507111122223333',
            'booking_code' => 'TDK-2026-GQRIS1',
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-12',
            'rental_days' => 3,
            'subtotal' => 150000,
            'grand_total' => 150000,
            'status' => 'pending',
            'expires_at' => Carbon::now()->addHours(2),
        ]);
        $rental->payments()->create([
            'vendor_id' => $this->vendor->id,
            'payment_type' => 'full',
            'payment_channel' => 'qris',
            'channel' => 'midtrans',
            'order_id' => 'TDK-2026-GQRIS1-FULL-1',
            'amount' => 150000,
            'status' => 'pending',
            'raw_notification' => [
                'actions' => [
                    [
                        'name' => 'generate-qr-code',
                        'method' => 'GET',
                        'url' => 'https://api.sandbox.midtrans.com/v2/qris/test-transaction/qr-code',
                    ],
                ],
            ],
        ]);

        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getQrisImage')
                ->once()
                ->andReturn('png-image-data');
        });

        $this->get(route('booking.status', [
            'code' => $rental->booking_code,
            'phone' => $rental->guest_phone,
        ]))
            ->assertOk()
            ->assertSee('phone=081298765432');

        $this->get(route('booking.qris-image', [
            'code' => $rental->booking_code,
            'phone' => $rental->guest_phone,
        ]))
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertContent('png-image-data');
    }

    private function createRental(int $userId, string $bookingCode, string $status): Rental
    {
        $rental = Rental::create([
            'vendor_id' => $this->vendor->id,
            'user_id' => $userId,
            'booking_code' => $bookingCode,
            'pickup_date' => '2026-10-10',
            'return_date' => '2026-10-12',
            'rental_days' => 3,
            'subtotal' => 150000,
            'grand_total' => 150000,
            'status' => $status,
            'expires_at' => Carbon::now()->addHours(2),
        ]);

        RentalDetail::create([
            'rental_id' => $rental->id,
            'master_item_id' => $this->item->id,
            'daily_rate_snapshot' => 50000,
        ]);

        return $rental;
    }
}
