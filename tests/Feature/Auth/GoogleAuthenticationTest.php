<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.supabase.url' => 'https://tendaku-test.supabase.co',
            'services.supabase.anon_key' => 'supabase-test-anon-key',
        ]);
    }

    public function test_google_login_redirects_to_supabase_with_a_pkce_challenge(): void
    {
        $response = $this->get(route('google.redirect'));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $verifier = session('supabase_google_oauth.verifier');
        $expectedChallenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $this->assertSame('google', $query['provider'] ?? null);
        $this->assertSame($expectedChallenge, $query['code_challenge'] ?? null);
        $this->assertSame('s256', $query['code_challenge_method'] ?? null);
        $this->assertStringContainsString('/auth/google/callback?state=', $query['redirect_to'] ?? '');
    }

    public function test_google_redirect_uses_the_configured_app_host(): void
    {
        $response = $this->get('https://untrusted.example/auth/google/redirect');
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->assertSame(
            'http://localhost/auth/google/callback?state='.session('supabase_google_oauth.state'),
            $query['redirect_to'] ?? null,
        );
    }

    public function test_google_redirect_uses_an_allowlisted_docker_port(): void
    {
        config([
            'services.supabase.redirect_urls' => [
                'http://localhost:8002/auth/google/callback',
            ],
        ]);

        $response = $this->get('http://localhost:8002/auth/google/redirect');
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->assertSame(
            'http://localhost:8002/auth/google/callback?state='.session('supabase_google_oauth.state'),
            $query['redirect_to'] ?? null,
        );
    }

    public function test_google_redirect_uses_an_allowlisted_tunnel_host(): void
    {
        config([
            'services.supabase.redirect_urls' => [
                'https://tendaku-example.ngrok-free.dev/auth/google/callback',
            ],
        ]);

        $response = $this->get('https://tendaku-example.ngrok-free.dev/auth/google/redirect');
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->assertSame(
            'https://tendaku-example.ngrok-free.dev/auth/google/callback?state='.session('supabase_google_oauth.state'),
            $query['redirect_to'] ?? null,
        );
    }

    public function test_google_callback_rejects_an_invalid_state(): void
    {
        Http::preventStrayRequests();

        $this->get(route('google.redirect'));
        $response = $this->get(route('google.callback', [
            'state' => 'unexpected-state',
            'code' => 'supabase-auth-code',
        ]));

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Sesi login Google tidak valid. Silakan coba lagi.');

        $this->assertGuest();
    }

    public function test_google_user_with_verified_email_can_register(): void
    {
        Notification::fake();
        $this->fakeSupabaseAuth();

        $response = $this->completeGoogleLogin();

        $response->assertRedirect(route('home'));
        $user = User::where('email', 'google-customer@example.com')->firstOrFail();

        $this->assertSame('customer', $user->role);
        $this->assertSame('google-user-123', $user->google_id);
        $this->assertSame('123e4567-e89b-42d3-a456-426614174000', $user->supabase_user_id);
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
        $this->fakeSupabaseAuth();

        $response = $this->completeGoogleLogin();

        $response->assertRedirect(route('kasir.dashboard'));
        $this->assertDatabaseCount('users', 1);
        $this->assertSame('google-user-123', $vendorAdmin->fresh()->google_id);
        $this->assertSame('123e4567-e89b-42d3-a456-426614174000', $vendorAdmin->fresh()->supabase_user_id);
        $this->assertSame('vendor_admin', $vendorAdmin->fresh()->role);
        $this->assertTrue($vendorAdmin->fresh()->hasVerifiedEmail());
        $this->assertAuthenticatedAs($vendorAdmin);
    }

    public function test_google_login_links_a_verified_email_to_an_existing_customer_account(): void
    {
        $customer = User::factory()->create([
            'email' => 'google-customer@example.com',
            'role' => 'customer',
            'google_id' => null,
        ]);
        $this->fakeSupabaseAuth();

        $response = $this->completeGoogleLogin();

        $response->assertRedirect(route('home'));
        $this->assertDatabaseCount('users', 1);
        $this->assertSame('google-user-123', $customer->fresh()->google_id);
        $this->assertSame('123e4567-e89b-42d3-a456-426614174000', $customer->fresh()->supabase_user_id);
        $this->assertAuthenticatedAs($customer);
    }

    public function test_google_login_does_not_link_an_existing_account_to_a_different_supabase_user(): void
    {
        $customer = User::factory()->create([
            'email' => 'google-customer@example.com',
            'role' => 'customer',
            'google_id' => null,
            'supabase_user_id' => '123e4567-e89b-42d3-a456-426614174001',
        ]);
        $this->fakeSupabaseAuth();

        $response = $this->completeGoogleLogin();

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Akun ini sudah terhubung ke akun Supabase lain.');

        $this->assertNull($customer->fresh()->google_id);
        $this->assertSame('123e4567-e89b-42d3-a456-426614174001', $customer->fresh()->supabase_user_id);
        $this->assertGuest();
    }

    public function test_google_registration_requires_a_verified_provider_email(): void
    {
        $this->fakeSupabaseAuth(['email_confirmed_at' => null]);

        $response = $this->completeGoogleLogin();

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Akun Google harus memiliki email terverifikasi.');

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_google_login_does_not_create_an_account_when_supabase_rejects_the_code(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://tendaku-test.supabase.co/auth/v1/token*' => Http::response(['message' => 'Invalid code'], 400),
        ]);
        $this->get(route('google.redirect'));

        $response = $this->get(route('google.callback', [
            'state' => session('supabase_google_oauth.state'),
            'code' => 'invalid-auth-code',
        ]));

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Login Google gagal. Silakan coba lagi.');

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
        $this->fakeSupabaseAuth();

        $response = $this->completeGoogleLogin();

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

    public function test_google_auth_redirect_and_buttons_are_unavailable_without_supabase_config(): void
    {
        config([
            'services.supabase.url' => null,
            'services.supabase.anon_key' => null,
        ]);

        $this->get(route('google.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Login Google belum dikonfigurasi.');

        $this->get(route('login'))
            ->assertDontSeeText('Masuk dengan Google');

        $this->get(route('register'))
            ->assertDontSeeText('Daftar dengan Google');
    }

    private function completeGoogleLogin(): TestResponse
    {
        $this->get(route('google.redirect'));
        $oauthSession = session('supabase_google_oauth');

        $response = $this->get(route('google.callback', [
            'state' => session('supabase_google_oauth.state'),
            'code' => 'supabase-auth-code',
        ]));

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://tendaku-test.supabase.co/auth/v1/token?grant_type=pkce'
            && $request->data()['auth_code'] === 'supabase-auth-code'
            && $request->data()['code_verifier'] === $oauthSession['verifier']);

        return $response;
    }

    /**
     * @param  array<string, mixed>  $userOverrides
     */
    private function fakeSupabaseAuth(array $userOverrides = []): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://tendaku-test.supabase.co/auth/v1/token*' => Http::response([
                'access_token' => 'supabase-access-token',
                'token_type' => 'bearer',
            ]),
            'https://tendaku-test.supabase.co/auth/v1/user' => Http::response(array_replace_recursive([
                'id' => '123e4567-e89b-42d3-a456-426614174000',
                'email' => 'google-customer@example.com',
                'email_confirmed_at' => now()->toISOString(),
                'identities' => [
                    [
                        'provider' => 'google',
                        'identity_data' => [
                            'sub' => 'google-user-123',
                            'full_name' => 'Google Customer',
                        ],
                    ],
                ],
            ], $userOverrides)),
        ]);

    }
}
