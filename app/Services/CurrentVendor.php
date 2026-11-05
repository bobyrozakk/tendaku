<?php

namespace App\Services;

use App\Models\Vendor;

class CurrentVendor
{
    protected static ?int $vendorId = null;

    protected static bool $isBypassed = false;

    /**
     * Set ID vendor aktif saat ini.
     */
    public static function set(int|Vendor|null $vendor): void
    {
        if ($vendor instanceof Vendor) {
            static::$vendorId = $vendor->id;
        } else {
            static::$vendorId = $vendor;
        }
    }

    /**
     * Ambil ID vendor aktif saat ini.
     */
    public static function id(): ?int
    {
        return static::$vendorId;
    }

    /**
     * Cek apakah ada vendor aktif yang sedang diset.
     */
    public static function has(): bool
    {
        return static::$vendorId !== null;
    }

    /**
     * Ambil instance model Vendor aktif saat ini (bila ada).
     */
    public static function get(): ?Vendor
    {
        if (static::$vendorId === null) {
            return null;
        }

        return Vendor::find(static::$vendorId);
    }

    /**
     * Reset vendor aktif dan status bypass ke kondisi awal.
     */
    public static function clear(): void
    {
        static::$vendorId = null;
        static::$isBypassed = false;
    }

    /**
     * Aktifkan atau nonaktifkan bypass scoping secara eksplisit.
     */
    public static function bypass(bool $state = true): void
    {
        static::$isBypassed = $state;
    }

    /**
     * Cek apakah scoping multi-tenant sedang di-bypass.
     */
    public static function isBypassed(): bool
    {
        return static::$isBypassed;
    }

    /**
     * Eksekusi callback tanpa scoping vendor (bypass sementara).
     */
    public static function withoutScoping(callable $callback): mixed
    {
        $previousBypass = static::$isBypassed;
        static::$isBypassed = true;

        try {
            return $callback();
        } finally {
            static::$isBypassed = $previousBypass;
        }
    }

    /**
     * Eksekusi callback dengan konteks vendor tertentu.
     */
    public static function runAs(int|Vendor|null $vendor, callable $callback): mixed
    {
        $previousVendorId = static::$vendorId;
        static::set($vendor);

        try {
            return $callback();
        } finally {
            static::$vendorId = $previousVendorId;
        }
    }
}
