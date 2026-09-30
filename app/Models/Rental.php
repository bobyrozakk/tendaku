<?php

namespace App\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rental extends Model
{
    use BelongsToVendor;

    protected $fillable = [
        'vendor_id', 'user_id', 'created_by', 'picked_up_by', 'returned_by',
        'booking_code', 'guest_name', 'guest_phone', 'guest_id_number',
        'pickup_date', 'return_date', 'picked_up_at', 'returned_at',
        'pickup_method', 'delivery_address', 'rental_days',
        'subtotal', 'delivery_fee', 'discount_amount', 'grand_total',
        'ktp_collateral_photo_url', 'ktp_collateral_status', 'ktp_received_at', 'ktp_returned_at', 'ktp_collateral_notes',
        'total_late_fee', 'total_damage_fee', 'final_total',
        'status', 'expires_at', 'notes',
    ];

    protected $hidden = [
        'guest_id_number',
    ];

    protected $casts = [
        'guest_id_number' => 'encrypted',
        'pickup_date' => 'date',
        'return_date' => 'date',
        'picked_up_at' => 'datetime',
        'returned_at' => 'datetime',
        'ktp_received_at' => 'datetime',
        'ktp_returned_at' => 'datetime',
        'expires_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'total_late_fee' => 'decimal:2',
        'total_damage_fee' => 'decimal:2',
        'final_total' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pickedUpBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_up_by');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(RentalDetail::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }
}
