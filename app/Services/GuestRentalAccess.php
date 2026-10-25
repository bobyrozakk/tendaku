<?php

namespace App\Services;

use App\Models\Rental;
use Illuminate\Http\Request;

class GuestRentalAccess
{
    private const LOCAL_PREFIX = '0';

    private const COUNTRY_CODE = '62';

    /**
     * Beri akses ke rental guest untuk session saat ini.
     */
    public function grant(Rental $rental): void
    {
        session([$this->sessionKey($rental) => true]);
    }

    /**
     * Cek apakah request ini boleh mengakses rental guest.
     * Jika lolos lewat signature atau nomor telepon, akses disimpan ke session
     * supaya request lanjutan (AJAX pay/sync, receipt, dll) tidak ditolak.
     */
    public function allows(Request $request, Rental $rental): bool
    {
        if ($this->hasSessionAccess($rental)) {
            return true;
        }

        if (! $request->hasValidSignature() && ! $this->phoneMatches($request, $rental)) {
            return false;
        }

        $this->grant($rental);

        return true;
    }

    private function hasSessionAccess(Rental $rental): bool
    {
        return session()->has($this->sessionKey($rental));
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
