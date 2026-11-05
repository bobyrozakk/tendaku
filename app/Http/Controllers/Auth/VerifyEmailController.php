<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\AuthRedirectService;
use App\Services\Booking\CartService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class VerifyEmailController extends Controller
{
    /**
     * Mark the user's email address as verified.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $userId = (int) $request->route('id');
        $hash = (string) $request->route('hash');

        /** @var User|null $user */
        $user = $request->user() ?: User::find($userId);

        if (! $user || ! hash_equals((string) $user->getKey(), (string) $userId) ||
            ! hash_equals(sha1($user->getEmailForVerification()), $hash)
        ) {
            throw new AuthorizationException('Tautan verifikasi tidak valid atau kedaluwarsa.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        // Login otomatis setelah email berhasil diverifikasi
        Auth::login($user);
        $request->session()->regenerate();
        app(CartService::class)->syncOnLogin($user);

        $redirectService = app(AuthRedirectService::class);
        Session::flash('success', 'Email berhasil diverifikasi! Selamat datang, '.$user->name.'.');

        $target = $redirectService->getDestination($user, absolute: false);

        return redirect($target);
    }
}
