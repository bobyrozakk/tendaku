<?php

namespace App\Services\Booking;

use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Satu aturan akses ke transaksi rental (login maupun guest).
 */
class RentalAccessGuard
{
    private const LOCAL_PREFIX = '0';

    private const COUNTRY_CODE = '62';

    /**
     * Memastikan request berhak mengakses transaksi rental.
     * - Jika user login: harus pemilik rental (user_id cocok).
     * - Jika guest: diverifikasi via session, atau token.
     */
    public function authorize(Request $request, Rental $rental): void
    {
        if ($rental->isGuest()) {
            abort_unless(
                $this->guestAllows($request, $rental),
                403,
                'FORBIDDEN'
            );

            return;
        }

        abort_unless(
            Auth::check() && $rental->user_id === Auth::id(),
            403,
            'FORBIDDEN'
        );
    }

    /**
     * Beri akses ke rental guest untuk session saat ini.
     */
    public function grantGuest(Rental $rental): void
    {
        session([$this->sessionKey($rental) => true]);
    }

    /**
     * Jika lolos lewat signature, token, atau nomor telepon lama, akses disimpan ke session
     * supaya request lanjutan (AJAX pay/sync, receipt, dll) tidak ditolak.
     */
    private function guestAllows(Request $request, Rental $rental): bool
    {
        if (session()->has($this->sessionKey($rental))) {
            return true;
        }

        if (
            ! $request->hasValidSignature()
            && ! $this->tokenMatches($request, $rental)
            && ! $this->phoneMatches($request, $rental)
        ) {
            return false;
        }

        $this->grantGuest($rental);

        return true;
    }

    private function tokenMatches(Request $request, Rental $rental): bool
    {
        $provided = $request->query('token');
        $storedHash = $rental->guest_access_token_hash;

        return is_string($provided)
            && $provided !== ''
            && is_string($storedHash)
            && hash_equals($storedHash, hash('sha256', $provided));
    }

    private function phoneMatches(Request $request, Rental $rental): bool
    {
        $provided = $this->normalizePhone($request->query('phone'));
        $actual = $this->normalizePhone($rental->guest_phone);

        return $provided !== '' && hash_equals($actual, $provided);
    }

    /**
     * Samakan format: 0812..., +62812..., 62812... => 0812...
     */
    private function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return str_starts_with($digits, self::COUNTRY_CODE)
            ? self::LOCAL_PREFIX.substr($digits, strlen(self::COUNTRY_CODE))
            : $digits;
    }

    private function sessionKey(Rental $rental): string
    {
        return "guest_rental_access.{$rental->booking_code}";
    }
}
