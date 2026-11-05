<?php

namespace App\Services;

final class PhoneNumberNormalizer
{
    /**
     * Kode negara Indonesia dalam format Fonnte.
     */
    public const COUNTRY_CODE = '62';

    /**
     * Panjang nomor yang dianggap valid setelah normalisasi (62 + 8..13 digit).
     */
    public const MIN_LENGTH = 10;

    public const MAX_LENGTH = 15;

    /**
     * Normalisasi nomor telepon Indonesia ke format Fonnte (`62xxx`).
     *
     * Accepted: `081234567890`, `+6281234567890`, `6281234567890`, `81234567890`.
     * Return null bila nomor kosong, tidak mengandung digit, atau panjangnya
     * di luar batas wajar.
     */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, self::COUNTRY_CODE)) {
            $normalized = $digits;
        } elseif (str_starts_with($digits, '0')) {
            $normalized = self::COUNTRY_CODE.ltrim($digits, '0');
        } elseif (str_starts_with($digits, '8')) {
            $normalized = self::COUNTRY_CODE.$digits;
        } else {
            return null;
        }

        $length = strlen($normalized);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            return null;
        }

        return $normalized;
    }
}
