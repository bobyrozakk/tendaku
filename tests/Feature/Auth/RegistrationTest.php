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
        $this->get(route('vendor.dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_email_must_use_gmail_domain(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@yahoo.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component->assertHasErrors(['email']);
        $this->assertGuest();
    }
}
