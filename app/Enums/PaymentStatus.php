<?php

namespace App\Enums;

/**
 * Status pembayaran. Transisi hanya maju dan idempotent (§5.1):
 *
 *   pending → paid | expired | cancelled | failed
 *   paid    → refunded
 *   lainnya → final
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending => $next !== self::Pending,
            self::Paid => $next === self::Refunded,
            default => false,
        };
    }
}
