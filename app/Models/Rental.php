<?php

namespace App\Models;

use App\Services\Booking\RentalService;
use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'completed_at',
        'cancelled_at',
        'cancel_reason',
        'cancel_note',
        'pickup_method',
        'delivery_address',
        'customer_address_id',
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
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
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

    public function customerAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'customer_address_id');
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
     * Expire rental ini jika pending dan lewat batas bayar.
     * Logikanya ada di RentalService::expire().
     */
    public function expireIfPastDue(): bool
    {
        return app(RentalService::class)->expire($this);
    }

    /**
     * Sudah ada pembayaran berstatus paid untuk rental ini.
     * Memakai relasi yang sudah di-load bila ada (hindari N+1 di daftar).
     */
    public function hasPaidPayment(): bool
    {
        return $this->relationLoaded('payments')
            ? $this->payments->contains(fn ($payment) => $payment->status === 'paid')
            : $this->payments()->where('status', 'paid')->exists();
    }

    /**
     * Label status untuk customer. Rental tetap 'pending' sampai kasir
     * memvalidasi serah terima, jadi yang sudah dibayar tidak boleh
     * ditampilkan sebagai "Menunggu Pembayaran".
     */
    public function displayStatusLabel(): string
    {
        if ($this->status === 'pending' && $this->hasPaidPayment()) {
            return 'Menunggu Validasi Kasir';
        }

        return (string) config(
            "booking.statuses.{$this->status}.label",
            ucfirst(str_replace('_', ' ', (string) $this->status))
        );
    }

    /**
     * Biaya admin yang dibayar customer, diturunkan dari angka yang tersimpan
     * (grand_total - subtotal - ongkir + diskon) agar tetap benar untuk
     * booking lama yang dibuat dengan tarif berbeda.
     */
    public function adminFee(): float
    {
        return max(0.0, round(
            (float) $this->grand_total
            - (float) $this->subtotal
            - (float) $this->delivery_fee
            + (float) $this->discount_amount
        ));
    }
}
