<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\AuthRedirectService;
use App\Services\Booking\CartService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $supabaseUrl = rtrim((string) config('services.supabase.url'), '/');

        if (! filled($supabaseUrl) || ! filled(config('services.supabase.anon_key'))) {
            return redirect()->route('login')->with('status', 'Login Google belum dikonfigurasi.');
        }

        $verifier = Str::random(64);
        $state = Str::random(40);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $callbackPath = route('google.callback', [], false);
        $requestCallbackUrl = $request->getSchemeAndHttpHost().$callbackPath;
        $allowedCallbackUrls = config('services.supabase.redirect_urls', []);
        $callbackBaseUrl = in_array($requestCallbackUrl, $allowedCallbackUrls, true)
            ? $requestCallbackUrl
            : rtrim((string) config('app.url'), '/').$callbackPath;
        $callbackUrl = $callbackBaseUrl
            .'?'.http_build_query(['state' => $state], '', '&', PHP_QUERY_RFC3986);

        $request->session()->put('supabase_google_oauth', [
            'state' => $state,
            'verifier' => $verifier,
        ]);

        $authorizationUrl = $supabaseUrl.'/auth/v1/authorize?'.http_build_query([
            'provider' => 'google',
            'redirect_to' => $callbackUrl,
            'code_challenge' => $challenge,
            'code_challenge_method' => 's256',
        ], '', '&', PHP_QUERY_RFC3986);

        return redirect()->away($authorizationUrl);
    }

    public function callback(Request $request): RedirectResponse
    {
        $supabaseUrl = rtrim((string) config('services.supabase.url'), '/');
        $supabaseKey = (string) config('services.supabase.anon_key');

        if (! filled($supabaseUrl) || ! filled($supabaseKey)) {
            return redirect()->route('login')->with('status', 'Login Google belum dikonfigurasi.');
        }

        $oauthSession = $request->session()->pull('supabase_google_oauth', []);

        if (
            ! is_array($oauthSession)
            || ! is_string($oauthSession['state'] ?? null)
            || ! is_string($oauthSession['verifier'] ?? null)
            || ! is_string($request->query('state'))
            || ! hash_equals($oauthSession['state'], $request->query('state'))
        ) {
            return redirect()->route('login')->with('status', 'Sesi login Google tidak valid. Silakan coba lagi.');
        }

        if ($request->filled('error')) {
            return redirect()->route('login')->with('status', 'Login Google dibatalkan.');
        }

        $authorizationCode = $request->query('code');

        if (! is_string($authorizationCode) || ! filled($authorizationCode)) {
            return redirect()->route('login')->with('status', 'Login Google gagal. Silakan coba lagi.');
        }

        try {
            $tokenResponse = Http::acceptJson()
                ->asJson()
                ->withHeaders(['apikey' => $supabaseKey])
                ->connectTimeout(3)
                ->timeout(10)
                ->post($supabaseUrl.'/auth/v1/token?grant_type=pkce', [
                    'auth_code' => $authorizationCode,
                    'code_verifier' => $oauthSession['verifier'],
                ]);

            if (! $tokenResponse->successful()) {
                return redirect()->route('login')->with('status', 'Login Google gagal. Silakan coba lagi.');
            }

            $accessToken = $tokenResponse->json('access_token');

            if (! is_string($accessToken) || ! filled($accessToken)) {
                return redirect()->route('login')->with('status', 'Login Google gagal. Silakan coba lagi.');
            }

            $userResponse = Http::acceptJson()
                ->withHeaders(['apikey' => $supabaseKey])
                ->withToken($accessToken)
                ->connectTimeout(3)
                ->timeout(10)
                ->get($supabaseUrl.'/auth/v1/user');
        } catch (ConnectionException) {
            return redirect()->route('login')->with('status', 'Tidak dapat menghubungi Supabase. Silakan coba lagi.');
        }

        if (! $userResponse->successful()) {
            return redirect()->route('login')->with('status', 'Login Google gagal. Silakan coba lagi.');
        }

        $supabaseUser = $userResponse->json();

        if (! is_array($supabaseUser)) {
            return redirect()->route('login')->with('status', 'Login Google gagal. Silakan coba lagi.');
        }

        $googleIdentity = collect($supabaseUser['identities'] ?? [])
            ->first(fn (mixed $identity): bool => is_array($identity) && ($identity['provider'] ?? null) === 'google');
        $supabaseUserId = $supabaseUser['id'] ?? null;
        $googleId = data_get($googleIdentity, 'identity_data.sub');
        $email = Str::lower(trim((string) ($supabaseUser['email'] ?? '')));
        $isEmailVerified = filled($supabaseUser['email_confirmed_at'] ?? null);

        if (
            ! is_string($supabaseUserId)
            || ! Str::isUuid($supabaseUserId)
            || ! is_string($googleId)
            || ! filled($googleId)
            || ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ! $isEmailVerified
        ) {
            return redirect()->route('login')->with('status', 'Akun Google harus memiliki email terverifikasi.');
        }

        $user = User::withTrashed()->where('supabase_user_id', $supabaseUserId)->first();

        if ($user === null) {
            $user = User::withTrashed()->where('google_id', $googleId)->first();
        }

        if ($user === null) {
            $user = User::withTrashed()->where('email', $email)->first();
        }

        if ($user?->trashed()) {
            return redirect()->route('login')->with('status', 'Akun ini tidak dapat masuk melalui Google.');
        }

        if ($user?->isSuperAdmin()) {
            return redirect()->route('login')->with('status', 'Akun ini tidak dapat masuk melalui Google.');
        }

        if ($user !== null && filled($user->google_id) && $user->google_id !== $googleId) {
            return redirect()->route('login')->with('status', 'Email ini sudah terhubung ke akun Google lain.');
        }

        if ($user !== null && filled($user->supabase_user_id) && $user->supabase_user_id !== $supabaseUserId) {
            return redirect()->route('login')->with('status', 'Akun ini sudah terhubung ke akun Supabase lain.');
        }

        if ($user === null) {
            $name = trim((string) data_get($googleIdentity, 'identity_data.full_name'))
                ?: trim((string) data_get($googleIdentity, 'identity_data.name'))
                ?: Str::before($email, '@');

            $user = User::forceCreate([
                'name' => Str::limit($name, 150, ''),
                'email' => $email,
                'password' => Str::random(64),
                'google_id' => $googleId,
                'supabase_user_id' => $supabaseUserId,
                'role' => 'customer',
            ]);

            $user->markEmailAsVerified();
        } else {
            $user->forceFill([
                'google_id' => $googleId,
                'supabase_user_id' => $supabaseUserId,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();
        app(CartService::class)->syncOnLogin($user);

        $redirectService = app(AuthRedirectService::class);
        $destination = $redirectService->getDestination($user, absolute: false);
        $welcomeMessage = $redirectService->getWelcomeMessage($user);

        $request->session()->flash('success', $welcomeMessage);

        return redirect()->intended($destination);
    }
}
