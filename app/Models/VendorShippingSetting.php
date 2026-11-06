<?php

namespace App\Models;

use App\Traits\BelongsToVendor;
use Database\Factories\VendorShippingSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class VendorShippingSetting extends Model
{
    /** @use HasFactory<VendorShippingSettingFactory> */
    use BelongsToVendor, HasFactory;

    protected $fillable = [
        'vendor_id',
        'rate_per_km',
        'weight_threshold_kg',
        'extra_weight_fee_per_kg',
    ];

    protected $casts = [
        'rate_per_km' => 'decimal:2',
        'weight_threshold_kg' => 'decimal:2',
        'extra_weight_fee_per_kg' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (VendorShippingSetting $setting): void {
            foreach (['rate_per_km', 'weight_threshold_kg', 'extra_weight_fee_per_kg'] as $field) {
                $value = $setting->{$field};

                if ($value === null || ! is_numeric($value) || (float) $value < 0) {
                    throw new InvalidArgumentException("Kolom {$field} tidak boleh kosong atau bernilai negatif.");
                }
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
