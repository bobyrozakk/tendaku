<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
    }

    public function test_google_login_redirects_to_the_provider(): void
    {
        Socialite::fake('google');

        $response = $this->get(route('google.redirect'));

        $response->assertRedirect();
    }

    public function test_google_user_with_verified_email_can_register(): void
    {
        Notification::fake();
        $this->fakeGoogleUser();

        $response = $this->get(route('google.callback'));

        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'google-customer@example.com')->firstOrFail();

        $this->assertSame('customer', $user->role);
        $this->assertSame('google-user-123', $user->google_id);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertAuthenticatedAs($user);
        Notification::assertNothingSent();
    }

    public function test_google_login_links_a_verified_email_to_an_existing_vendor_account(): void
    {
        $vendorAdmin = User::factory()->unverified()->create([
            'email' => 'google-customer@example.com',
            'role' => 'vendor_admin',
            'google_id' => null,
        ]);
        $this->fakeGoogleUser();

        $response = $this->get(route('google.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseCount('users', 1);
        $this->assertSame('google-user-123', $vendorAdmin->fresh()->google_id);
        $this->assertSame('vendor_admin', $vendorAdmin->fresh()->role);
        $this->assertTrue($vendorAdmin->fresh()->hasVerifiedEmail());
        $this->assertAuthenticatedAs($vendorAdmin);
    }

    public function test_google_registration_requires_a_verified_provider_email(): void
    {
        $this->fakeGoogleUser(verifiedEmail: false);

        $response = $this->get(route('google.callback'));

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Akun Google harus memiliki email terverifikasi.');

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_google_id_cannot_be_set_through_regular_mass_assignment(): void
    {
        $user = User::create([
            'name' => 'Regular account',
            'email' => 'regular-account@example.com',
            'password' => 'password',
            'role' => 'customer',
            'google_id' => 'untrusted-google-id',
        ]);

        $this->assertNull($user->fresh()->google_id);
    }

    public function test_google_login_cannot_authenticate_a_superadmin(): void
    {
        $superAdmin = User::factory()->create([
            'email' => 'google-customer@example.com',
            'role' => 'super_admin',
            'google_id' => null,
        ]);
        $this->fakeGoogleUser();

        $response = $this->get(route('google.callback'));

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Akun ini tidak dapat masuk melalui Google.');

        $this->assertNull($superAdmin->fresh()->google_id);
        $this->assertGuest();
    }

    public function test_google_buttons_are_shown_on_customer_auth_screens_when_configured(): void
    {
        $this->get(route('login'))
            ->assertSeeText('Masuk dengan Google');

        $this->get(route('register'))
            ->assertSeeText('Daftar dengan Google');

        $this->get(route('superadmin.login'))
            ->assertDontSeeText('Google');
    }

    public function test_google_auth_redirect_and_buttons_are_unavailable_without_credentials(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $this->get(route('google.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Login Google belum dikonfigurasi.');

        $this->get(route('login'))
            ->assertDontSeeText('Masuk dengan Google');

        $this->get(route('register'))
            ->assertDontSeeText('Daftar dengan Google');
    }

    private function fakeGoogleUser(bool $verifiedEmail = true): void
    {
        Socialite::fake('google', GoogleUser::fake([
            'id' => 'google-user-123',
            'name' => 'Google Customer',
            'email' => 'Google-Customer@example.com',
            'verified_email' => $verifiedEmail,
        ]));
    }
}
