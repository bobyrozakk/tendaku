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
            ->set('name', 'Test User')
            ->set('email', 'test@gmail.com')
            ->set('phone', '081234567890')
            ->set('nik', '1234567890123456')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

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
            ->set('name', 'Test User')
            ->set('email', 'test@gmail.com')
            ->set('phone', '081234567890')
            ->set('nik', '1234567890123456')
            ->set('address', 'Jl. Merdeka No. 1, Jakarta')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component->assertRedirect(route('verification.notice', absolute: false));

        $user = User::where('email', 'test@gmail.com')->firstOrFail();
        $this->assertSame('Jl. Merdeka No. 1, Jakarta', $user->address);
    }

    public function test_phone_is_required_for_registration(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@gmail.com')
            ->set('nik', '1234567890123456')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component->assertHasErrors(['phone']);
        $this->assertGuest();
    }

    public function test_nik_is_required_for_registration(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@gmail.com')
            ->set('phone', '081234567890')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component->assertHasErrors(['nik']);
        $this->assertGuest();
    }

    public function test_nik_must_be_16_digits(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@gmail.com')
            ->set('phone', '081234567890')
            ->set('nik', '12345') // terlalu pendek
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component->assertHasErrors(['nik']);
        $this->assertGuest();
    }

    public function test_email_must_use_gmail_domain(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@yahoo.com')
            ->set('phone', '081234567890')
            ->set('nik', '1234567890123456')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component->assertHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_name_is_required_for_registration(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('email', 'test@gmail.com')
            ->set('phone', '081234567890')
            ->set('nik', '1234567890123456')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component->assertHasErrors(['name']);
        $this->assertGuest();
    }

    public function test_password_is_required_for_registration(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@gmail.com')
            ->set('phone', '081234567890')
            ->set('nik', '1234567890123456');

        $component->call('register');

        $component->assertHasErrors(['password']);
        $this->assertGuest();
    }
}
