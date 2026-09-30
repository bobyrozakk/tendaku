<?php

namespace App\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterItem extends Model
{
    use BelongsToVendor, SoftDeletes;

    protected $fillable = [
        'vendor_id', 'category_id', 'name', 'slug', 'description',
        'daily_rate', 'deposit_amount', 'late_fee_per_day', 'image_url', 'is_active',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'late_fee_per_day' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(ItemUnit::class);
    }

    public function rentalDetails(): HasMany
    {
        return $this->hasMany(RentalDetail::class);
    }
}
