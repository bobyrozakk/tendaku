<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! filled(config('services.google.client_id')) || ! filled(config('services.google.client_secret'))) {
            return redirect()->route('login')->with('status', 'Login Google belum dikonfigurasi.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! filled(config('services.google.client_id')) || ! filled(config('services.google.client_secret'))) {
            return redirect()->route('login')->with('status', 'Login Google belum dikonfigurasi.');
        }

        if ($request->filled('error')) {
            return redirect()->route('login')->with('status', 'Login Google dibatalkan.');
        }

        try {
            /** @var GoogleUser $googleUser */
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return redirect()->route('login')->with('status', 'Sesi login Google tidak valid. Silakan coba lagi.');
        }

        $googleId = $googleUser->getId();
        $email = Str::lower(trim((string) $googleUser->getEmail()));
        $isEmailVerified = ($googleUser->getRaw()['verified_email'] ?? false) === true;

        if (! filled($googleId) || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $isEmailVerified) {
            return redirect()->route('login')->with('status', 'Akun Google harus memiliki email terverifikasi.');
        }

        $user = User::withTrashed()->where('google_id', $googleId)->first();

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

        if ($user === null) {
            $name = trim((string) $googleUser->getName()) ?: Str::before($email, '@');

            $user = User::forceCreate([
                'name' => Str::limit($name, 150, ''),
                'email' => $email,
                'password' => Str::random(64),
                'google_id' => $googleId,
                'role' => 'customer',
            ]);

            $user->markEmailAsVerified();
        } else {
            $user->forceFill([
                'google_id' => $googleId,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
