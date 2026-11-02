<?php

namespace App\Enums;

/**
 * Tipe pembayaran yang dikenal aplikasi.
 *
 * `full` dan `dp` adalah pembayaran pertama sebuah rental, `remaining` adalah
 * pelunasan sisa tagihan, dan `fine` adalah denda pengembalian.
 */
enum PaymentType: string
{
    case Full = 'full';
    case Dp = 'dp';
    case Remaining = 'remaining';
    case Fine = 'fine';

    /**
     * Tipe yang membentuk nilai sewa dan menjadi basis komisi platform.
     *
     * @return list<self>
     */
    public static function rentalPayments(): array
    {
        return [self::Full, self::Dp, self::Remaining];
    }

    /**
     * Tipe pembayaran yang boleh dibuat lewat Midtrans.
     *
     * @return list<self>
     */
    public static function onlinePayments(): array
    {
        return [self::Full, self::Dp, self::Remaining];
    }

    /**
     * @return list<string>
     */
    public static function rentalValues(): array
    {
        return array_map(fn (self $type): string => $type->value, self::rentalPayments());
    }

    /**
     * @return list<string>
     */
    public static function onlinePaymentValues(): array
    {
        return array_map(fn (self $type): string => $type->value, self::onlinePayments());
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }

    public function isRentalPayment(): bool
    {
        return in_array($this, self::rentalPayments(), true);
    }

    public function isCommissionable(): bool
    {
        return $this->isRentalPayment();
    }

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Bayar Penuh',
            self::Dp => 'DP',
            self::Remaining => 'Pelunasan',
            self::Fine => 'Denda',
        };
    }
}
