<?php

namespace Tests\Feature\Notification;

use App\Jobs\SendReturnReminder;
use App\Models\ItemCategory;
use App\Models\MasterItem;
use App\Models\NotificationLog;
use App\Models\Rental;
use App\Models\RentalDetail;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ReturnReminderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReturnReminderTest extends TestCase
{
    use RefreshDatabase;

    protected Vendor $vendor;

    protected User $customer;

    protected MasterItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.fonnte.token' => 'token-uji-123',
            'services.fonnte.retry_times' => 1,
            'services.fonnte.retry_sleep_ms' => 1,
            'notifications.channel' => 'whatsapp',
            'notifications.reminder.timezone' => 'Asia/Jakarta',
            'notifications.reminder.days' => [1, 0],
        ]);

        $this->vendor = Vendor::create([
            'name' => 'Tendaku Vendor Store',
            'slug' => 'tendaku-vendor-store',
            'phone' => '081234567890',
            'address' => 'Jl. Outdoor No. 1',
            'status' => 'active',
        ]);

        $this->customer = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi.santoso@example.com',
            'phone' => '081234567890',
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
            'name' => 'Tenda Consina Magnum 4',
            'slug' => 'tenda-consina-magnum-4',
            'daily_rate' => 45000,
            'late_fee_per_day' => 20000,
        ]);
    }

    public function test_it_sends_h_minus_one_reminder_and_records_the_notification(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $rental = $this->createPickedUpRental(Carbon::now('Asia/Jakarta')->addDay());

        $service = app(ReturnReminderService::class);
        $pending = $service->pendingReminders();

        $this->assertCount(1, $pending);
        $this->assertSame(ReturnReminderService::TYPE_H_MINUS_ONE, $pending->first()['type']);
        $this->assertSame($rental->id, $pending->first()['rental_id']);

        $this->assertTrue($service->sendReminderForRental($rental->id, ReturnReminderService::TYPE_H_MINUS_ONE));

        Http::assertSent(function (Request $request) {
            return $request['target'] === '6281234567890'
                && $request['countryCode'] === '62'
                && str_contains($request['message'], 'BESOK');
        });

        $notification = NotificationLog::withoutGlobalScope('vendor')->sole();

        $this->assertSame('sent', $notification->status);
        $this->assertSame('whatsapp', $notification->channel);
        $this->assertSame(ReturnReminderService::TYPE_H_MINUS_ONE, $notification->type);
        $this->assertSame('6281234567890', $notification->recipient_phone);
        $this->assertSame($rental->id, $notification->rental_id);
        $this->assertNotNull($notification->sent_at);
    }

    public function test_it_sends_due_today_reminder_for_rental_returning_today(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $rental = $this->createPickedUpRental(Carbon::now('Asia/Jakarta'));

        $service = app(ReturnReminderService::class);
        $pending = $service->pendingReminders();

        $this->assertCount(1, $pending);
        $this->assertSame(ReturnReminderService::TYPE_DUE_TODAY, $pending->first()['type']);

        $service->sendReminderForRental($rental->id, ReturnReminderService::TYPE_DUE_TODAY);

        Http::assertSent(fn (Request $request) => str_contains($request['message'], 'HARI INI'));
    }

    public function test_it_does_not_send_the_same_reminder_twice(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $rental = $this->createPickedUpRental(Carbon::now('Asia/Jakarta')->addDay());

        $service = app(ReturnReminderService::class);

        $this->assertTrue($service->sendReminderForRental($rental->id, ReturnReminderService::TYPE_H_MINUS_ONE));
        $this->assertFalse($service->sendReminderForRental($rental->id, ReturnReminderService::TYPE_H_MINUS_ONE));

        Http::assertSentCount(1);
        $this->assertSame(1, NotificationLog::withoutGlobalScope('vendor')->count());
    }

    public function test_it_does_not_send_reminder_for_rental_that_is_already_returned(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $rental = $this->createPickedUpRental(Carbon::now('Asia/Jakarta')->addDay());
        $rental->update(['status' => 'completed']);

        $service = app(ReturnReminderService::class);

        $this->assertCount(0, $service->pendingReminders());
        $this->assertFalse($service->sendReminderForRental($rental->id, ReturnReminderService::TYPE_H_MINUS_ONE));

        Http::assertNothingSent();
        $this->assertSame(0, NotificationLog::withoutGlobalScope('vendor')->count());
    }

    public function test_it_records_failed_status_when_delivery_is_rejected(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => false, 'reason' => 'token invalid'], 200)]);

        $rental = $this->createPickedUpRental(Carbon::now('Asia/Jakarta')->addDay());

        $service = app(ReturnReminderService::class);

        $this->assertFalse($service->sendReminderForRental($rental->id, ReturnReminderService::TYPE_H_MINUS_ONE));

        $notification = NotificationLog::withoutGlobalScope('vendor')->sole();

        $this->assertSame('failed', $notification->status);
        $this->assertNull($notification->sent_at);
    }

    public function test_it_skips_rental_without_reachable_phone_number(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $rental = $this->createPickedUpRental(Carbon::now('Asia/Jakarta')->addDay());
        $this->customer->update(['phone' => null]);

        $service = app(ReturnReminderService::class);

        $this->assertFalse($service->sendReminderForRental($rental->id, ReturnReminderService::TYPE_H_MINUS_ONE));

        Http::assertNothingSent();
        $this->assertSame(0, NotificationLog::withoutGlobalScope('vendor')->count());
    }

    public function test_it_uses_guest_phone_number_for_guest_booking(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $rental = $this->createPickedUpRental(Carbon::now('Asia/Jakarta')->addDay(), [
            'user_id' => null,
            'guest_name' => 'Tamu Tanpa Akun',
            'guest_phone' => '081299999999',
        ]);

        $service = app(ReturnReminderService::class);
        $service->sendReminderForRental($rental->id, ReturnReminderService::TYPE_H_MINUS_ONE);

        Http::assertSent(fn (Request $request) => $request['target'] === '6281299999999');
    }

    public function test_message_contains_booking_details_in_indonesian(): void
    {
        $rental = $this->createPickedUpRental(Carbon::now('Asia/Jakarta')->addDay());

        $message = app(ReturnReminderService::class)->buildMessage($rental, ReturnReminderService::TYPE_H_MINUS_ONE);

        $this->assertStringContainsString('Budi Santoso', $message);
        $this->assertStringContainsString($rental->booking_code, $message);
        $this->assertStringContainsString('Tenda Consina Magnum 4', $message);
        $this->assertStringContainsString('BESOK', $message);
        $this->assertStringContainsString('denda telat Rp 20.000/hari', $message);
        $this->assertStringContainsString('90.000', $message);
    }

    public function test_job_does_not_send_reminder_for_rental_of_another_vendor(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $rental = $this->createPickedUpRental(Carbon::now('Asia/Jakarta')->addDay());

        $otherVendor = Vendor::create([
            'name' => 'Vendor Lain',
            'slug' => 'vendor-lain',
            'phone' => '081200000000',
            'address' => 'Jl. Lain No. 2',
            'status' => 'active',
        ]);

        SendReturnReminder::dispatch($rental->id, $otherVendor->id, ReturnReminderService::TYPE_H_MINUS_ONE);

        Http::assertNothingSent();
        $this->assertSame(0, NotificationLog::withoutGlobalScope('vendor')->count());
    }

    public function test_command_sends_reminders_for_rentals_due_today_and_tomorrow(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $this->createPickedUpRental(Carbon::now('Asia/Jakarta')->addDay(), ['booking_code' => 'TND-A-001']);
        $this->createPickedUpRental(Carbon::now('Asia/Jakarta'), ['booking_code' => 'TND-A-002']);

        $this->artisan('rentals:send-return-reminders')->assertSuccessful();

        Http::assertSentCount(2);
        $this->assertSame(2, NotificationLog::withoutGlobalScope('vendor')->count());
    }

    public function test_command_dry_run_does_not_send_any_message(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $this->createPickedUpRental(Carbon::now('Asia/Jakarta')->addDay());

        $this->artisan('rentals:send-return-reminders', ['--dry-run' => true])->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(0, NotificationLog::withoutGlobalScope('vendor')->count());
    }

    public function test_command_dry_run_shows_target_number_and_message_preview(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $this->createPickedUpRental(Carbon::now('Asia/Jakarta')->addDay(), [
            'booking_code' => 'TND-DRY-001',
        ]);

        $this->artisan('rentals:send-return-reminders', ['--dry-run' => true])
            ->expectsOutputToContain('6281234567890')
            ->expectsOutputToContain('TND-DRY-001')
            ->expectsOutputToContain('Pengingat')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_command_dry_run_marks_rental_without_reachable_number(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $this->customer->update(['phone' => null]);

        $rental = $this->createPickedUpRental(Carbon::now('Asia/Jakarta')->addDay());
        $rental->update(['user_id' => null, 'guest_phone' => null]);

        $this->artisan('rentals:send-return-reminders', ['--dry-run' => true])
            ->expectsOutputToContain('TIDAK ADA NOMOR')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_command_does_not_resend_reminder_that_was_already_sent(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $this->createPickedUpRental(Carbon::now('Asia/Jakarta')->addDay());

        $this->artisan('rentals:send-return-reminders')->assertSuccessful();
        $this->artisan('rentals:send-return-reminders')->assertSuccessful();

        Http::assertSentCount(1);
        $this->assertSame(1, NotificationLog::withoutGlobalScope('vendor')->count());
    }

    /**
     * Buat rental berstatus `picked_up` beserta rincian barangnya.
     */
    protected function createPickedUpRental(Carbon $returnDate, array $attributes = []): Rental
    {
        $rental = Rental::create(array_merge([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'booking_code' => 'TND-REM-001',
            'pickup_date' => $returnDate->copy()->subDay()->toDateString(),
            'return_date' => $returnDate->toDateString(),
            'rental_days' => 2,
            'subtotal' => 90000,
            'total_deposit' => 0,
            'dp_amount' => 0,
            'grand_total' => 90000,
            'status' => 'picked_up',
        ], $attributes));

        RentalDetail::create([
            'rental_id' => $rental->id,
            'master_item_id' => $this->item->id,
            'daily_rate_snapshot' => 45000,
            'deposit_snapshot' => 0,
            'late_fee_per_day_snapshot' => 20000,
        ]);

        return $rental;
    }
}
