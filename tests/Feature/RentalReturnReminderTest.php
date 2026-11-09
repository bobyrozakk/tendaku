<?php

namespace Tests\Feature;

use App\Mail\RentalReturnReminderMail;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RentalReturnReminderTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = Vendor::factory()->create(['name' => 'Tendaku Outdoor Malang']);
        $this->customer = User::factory()->create([
            'name' => 'Boby Rozak',
            'email' => 'boby.customer@example.com',
        ]);
    }

    public function test_reminder_email_is_sent_to_customer_5_hours_before_due_time(): void
    {
        Mail::fake();

        Carbon::setTestNow('2026-11-10 12:00:00');

        // Waktu pengembalian: 4.5 jam lagi (16:30)
        $rental = Rental::factory()->create([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'booking_code' => 'TND-REMIND-01',
            'status' => 'picked_up',
            'return_date' => '2026-11-10',
            'return_time' => '16:30:00',
            'return_reminder_sent_at' => null,
        ]);

        $this->artisan('rentals:send-email-reminders')
            ->expectsOutputToContain('Total terkirim: 1 email.')
            ->assertExitCode(0);

        Mail::assertSent(RentalReturnReminderMail::class, function (RentalReturnReminderMail $mail) use ($rental) {
            return $mail->hasTo('boby.customer@example.com') &&
                   $mail->rental->id === $rental->id;
        });

        $this->assertNotNull($rental->fresh()->return_reminder_sent_at);
    }

    public function test_reminder_is_not_sent_twice_for_the_same_rental(): void
    {
        Mail::fake();

        Carbon::setTestNow('2026-11-10 12:00:00');

        $rental = Rental::factory()->create([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'booking_code' => 'TND-REMIND-02',
            'status' => 'picked_up',
            'return_date' => '2026-11-10',
            'return_time' => '16:00:00',
            'return_reminder_sent_at' => Carbon::now()->subHour(),
        ]);

        $this->artisan('rentals:send-email-reminders')
            ->expectsOutputToContain('Total terkirim: 0 email.')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_rental_not_due_within_5_hours_is_not_reminded(): void
    {
        Mail::fake();

        Carbon::setTestNow('2026-11-10 08:00:00');

        // Waktu pengembalian 10 jam lagi (18:00)
        Rental::factory()->create([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'booking_code' => 'TND-REMIND-03',
            'status' => 'picked_up',
            'return_date' => '2026-11-10',
            'return_time' => '18:00:00',
            'return_reminder_sent_at' => null,
        ]);

        $this->artisan('rentals:send-email-reminders')
            ->expectsOutputToContain('Total terkirim: 0 email.')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_non_picked_up_rental_is_not_reminded(): void
    {
        Mail::fake();

        Carbon::setTestNow('2026-11-10 12:00:00');

        // Rental masih pending atau confirmed (belum serah terima unit)
        Rental::factory()->create([
            'vendor_id' => $this->vendor->id,
            'user_id' => $this->customer->id,
            'booking_code' => 'TND-REMIND-04',
            'status' => 'confirmed',
            'return_date' => '2026-11-10',
            'return_time' => '16:00:00',
            'return_reminder_sent_at' => null,
        ]);

        $this->artisan('rentals:send-email-reminders')
            ->expectsOutputToContain('Total terkirim: 0 email.')
            ->assertExitCode(0);

        Mail::assertNothingSent();
    }
}
