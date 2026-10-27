<?php

namespace App\Models;

use App\Traits\BelongsToVendor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Rental extends Model
{
    use BelongsToVendor, HasFactory;

    protected $fillable = [
        'vendor_id',
        'user_id',
        'created_by',
        'picked_up_by',
        'returned_by',
        'booking_code',
        'guest_name',
        'guest_phone',
        'guest_id_number',
        'pickup_date',
        'pickup_time',
        'return_date',
        'return_time',
        'actual_return_date',
        'picked_up_at',
        'returned_at',
        'pickup_method',
        'delivery_address',
        'rental_days',
        'subtotal',
        'delivery_fee',
        'discount_amount',
        'total_deposit',
        'dp_amount',
        'harga_dp',
        'grand_total',
        'ktp_collateral_photo_url',
        'ktp_collateral_status',
        'ktp_received_at',
        'ktp_returned_at',
        'ktp_collateral_notes',
        'total_late_fee',
        'total_damage_fee',
        'final_total',
        'status',
        'expires_at',
        'notes',
    ];

    protected $hidden = [
        'guest_id_number',
    ];

    protected function casts(): array
    {
        return [
            'guest_id_number' => 'encrypted',

            'pickup_date' => 'date',
            'return_date' => 'date',
            'actual_return_date' => 'date',

            'picked_up_at' => 'datetime',
            'returned_at' => 'datetime',
            'ktp_received_at' => 'datetime',
            'ktp_returned_at' => 'datetime',
            'expires_at' => 'datetime',

            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_deposit' => 'decimal:2',
            'dp_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'total_late_fee' => 'decimal:2',
            'total_damage_fee' => 'decimal:2',
            'final_total' => 'decimal:2',
        ];
    }

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

    public function commissions(): HasMany
    {
        return $this->hasMany(PlatformCommission::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }

    //  Alias kompatibilitas untuk dp_amount.
    public function getHargaDpAttribute(): mixed
    {
        return $this->dp_amount ?? '0.00';
    }

    public function setHargaDpAttribute($value): void
    {
        $this->attributes['dp_amount'] = $value;
    }

    /**
     * Periksa dan batalkan rental pending jika telah melewati batas waktu pembayaran (5 menit).
     * Idempoten dan aman dari race condition.
     */
    public function expireIfPastDue(): bool
    {
        if ($this->status !== 'pending' || ! $this->expires_at || ! $this->expires_at->isPast()) {
            return false;
        }

        return DB::transaction(function () {
            /** @var Rental|null $locked */
            $locked = self::query()
                ->whereKey($this->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->status !== 'pending') {
                return false;
            }

            // Jika ada pembayaran yang sudah lunas (paid), jangan batalkan
            $hasPaid = $locked->payments()->where('status', 'paid')->exists();
            if ($hasPaid) {
                return false;
            }

            $now = Carbon::now();
            $locked->update([
                'status' => 'expired',
                'updated_at' => $now,
            ]);

            $locked->payments()
                ->where('status', 'pending')
                ->update([
                    'status' => 'failed',
                    'updated_at' => $now,
                ]);

            $this->status = 'expired';

            return true;
        });
    }
}
