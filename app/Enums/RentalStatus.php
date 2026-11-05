<?php

namespace App\Enums;

/**
 * Status rental. Nilai sama persis dengan kolom rentals.status dan
 * kunci config('booking.statuses').
 *
 *   pending -> confirmed -> picked_up -> returned -> completed
 *   pending -> expired | cancelled
 */
enum RentalStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case PickedUp = 'picked_up';
    case Returned = 'returned';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return config("booking.statuses.{$this->value}.label", $this->value);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::Expired], true);
    }
}
