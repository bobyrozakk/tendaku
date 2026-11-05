<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class CustomerAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'recipient_name',
        'phone_number',
        'full_address',
        'province',
        'city',
        'district',
        'village',
        'postal_code',
        'notes',
        'is_default',
    ];

    protected $appends = [
        'formatted_address',
        'delivery_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class, 'customer_address_id');
    }

    /**
     * Jadikan alamat ini sebagai alamat default/utama user.
     * Alamat lain milik user yang sama otomatis diubah menjadi false.
     */
    public function setAsDefault(): void
    {
        DB::transaction(function () {
            self::query()
                ->where('user_id', $this->user_id)
                ->where('id', '!=', $this->id)
                ->update(['is_default' => false]);

            $this->update(['is_default' => true]);
        });
    }

    /**
     * Dapatkan format teks alamat lengkap untuk snapshot transaksi atau tampilan ringkas.
     */
    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->full_address,
            $this->village ? 'Desa/Kel. '.$this->village : null,
            $this->district ? 'Kec. '.$this->district : null,
            $this->city,
            $this->province,
            $this->postal_code,
        ], fn ($p) => filled($p));

        $addressText = implode(', ', $parts);

        if (filled($this->notes)) {
            $addressText .= ' (Patokan: '.trim($this->notes).')';
        }

        return $addressText;
    }

    /**
     * Snapshot lengkap untuk data pengantaran rental, mencakup penerima & telepon.
     */
    public function toDeliverySnapshot(): string
    {
        $header = "{$this->recipient_name} ({$this->phone_number})";

        return "{$header}\n{$this->formatted_address}";
    }

    public function getDeliverySnapshotAttribute(): string
    {
        return $this->toDeliverySnapshot();
    }
}
