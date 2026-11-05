<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();

        $component = Volt::test('pages.auth.register')
            ->set('form.name', 'Test User')
            ->set('form.email', 'test@gmail.com')
            ->set('form.phone', '081234567890')
            ->set('form.nik', '1234567890123456')
            ->set('form.password', 'password')
            ->set('form.password_confirmation', 'password');

        $component->call('register');

        // Setelah register, customer diarahkan ke halaman verifikasi email
        $component->assertRedirect(route('verification.notice', absolute: false));

        $this->assertAuthenticated();
        $user = User::where('email', 'test@gmail.com')->firstOrFail();

        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertSame('customer', $user->role);
        $this->assertSame('081234567890', $user->phone);
        $this->assertSame('1234567890123456', $user->nik);
        $this->assertNull($user->address);
        $this->get(route('vendor.dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_new_users_can_register_with_address(): void
    {
        Notification::fake();

        $component = Volt::test('pages.auth.register')
            ->set('form.name', 'Test User')
            ->set('form.email', 'test@gmail.com')
            ->set('form.phone', '081234567890')
            ->set('form.nik', '1234567890123456')
            ->set('form.address', 'Jl. Merdeka No. 1, Jakarta')
            ->set('form.password', 'password')
            ->set('form.password_confirmation', 'password');

        $component->call('register');

        $component->assertRedirect(route('verification.notice', absolute: false));

        $user = User::where('email', 'test@gmail.com')->firstOrFail();
        $this->assertSame('Jl. Merdeka No. 1, Jakarta', $user->address);
    }

    public function test_phone_is_required_for_registration(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('form.name', 'Test User')
            ->set('form.email', 'test@gmail.com')
            ->set('form.nik', '1234567890123456')
            ->set('form.password', 'password')
            ->set('form.password_confirmation', 'password');

        $component->call('register');

        $component->assertHasErrors(['form.phone']);
        $this->assertGuest();
    }

    public function test_nik_is_required_for_registration(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('form.name', 'Test User')
            ->set('form.email', 'test@gmail.com')
            ->set('form.phone', '081234567890')
            ->set('form.password', 'password')
            ->set('form.password_confirmation', 'password');

        $component->call('register');

        $component->assertHasErrors(['form.nik']);
        $this->assertGuest();
    }

    public function test_nik_must_be_16_digits(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('form.name', 'Test User')
            ->set('form.email', 'test@gmail.com')
            ->set('form.phone', '081234567890')
            ->set('form.nik', '12345') // terlalu pendek
            ->set('form.password', 'password')
            ->set('form.password_confirmation', 'password');

        $component->call('register');

        $component->assertHasErrors(['form.nik']);
        $this->assertGuest();
    }

    public function test_email_must_use_gmail_domain(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('form.name', 'Test User')
            ->set('form.email', 'test@yahoo.com')
            ->set('form.phone', '081234567890')
            ->set('form.nik', '1234567890123456')
            ->set('form.password', 'password')
            ->set('form.password_confirmation', 'password');

        $component->call('register');

        $component->assertHasErrors(['form.email']);
        $this->assertGuest();
    }

    public function test_name_is_required_for_registration(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('form.email', 'test@gmail.com')
            ->set('form.phone', '081234567890')
            ->set('form.nik', '1234567890123456')
            ->set('form.password', 'password')
            ->set('form.password_confirmation', 'password');

        $component->call('register');

        $component->assertHasErrors(['form.name']);
        $this->assertGuest();
    }

    public function test_password_is_required_for_registration(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('form.name', 'Test User')
            ->set('form.email', 'test@gmail.com')
            ->set('form.phone', '081234567890')
            ->set('form.nik', '1234567890123456');

        $component->call('register');

        $component->assertHasErrors(['form.password']);
        $this->assertGuest();
    }

    public function test_vendor_owners_can_register(): void
    {
        Notification::fake();

        $component = Volt::test('pages.auth.register')
            ->call('setAccountType', 'vendor')
            ->set('form.store_name', 'Tendaku Puncak Rental')
            ->set('form.city', 'Bandung')
            ->set('form.store_address', 'Jl. Raya Tangkuban Perahu No. 45')
            ->set('form.description', 'Spesialis rental tenda camping dan alat gunung.')
            ->set('form.name', 'Owner Vendor')
            ->set('form.email', 'vendorowner@gmail.com')
            ->set('form.phone', '081234567899')
            ->set('form.nik', '3201234567890001')
            ->set('form.password', 'password')
            ->set('form.password_confirmation', 'password');

        $component->call('register');

        $component->assertRedirect(route('verification.notice', absolute: false));
        $this->assertAuthenticated();

        $user = User::where('email', 'vendorowner@gmail.com')->firstOrFail();
        $this->assertSame('vendor_owner', $user->role);
        $this->assertNotNull($user->vendor_id);
        $this->assertFalse($user->hasVerifiedEmail());

        $vendor = $user->vendor;
        $this->assertNotNull($vendor);
        $this->assertSame('Tendaku Puncak Rental', $vendor->name);
        $this->assertSame('tendaku-puncak-rental', $vendor->slug);
        $this->assertSame('pending', $vendor->status);
        $this->assertSame('Bandung', $vendor->city);

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
