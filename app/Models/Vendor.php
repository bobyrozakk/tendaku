<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'phone', 'email', 'address',
        'city', 'logo_url', 'status', 'approved_at', 'approved_by',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(User::class, 'vendor_id');
    }

    public function paymentSetting(): HasOne
    {
        return $this->hasOne(VendorPaymentSetting::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(ItemCategory::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MasterItem::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(ItemUnit::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
