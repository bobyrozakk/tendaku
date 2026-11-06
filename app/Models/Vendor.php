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
        'city', 'latitude', 'longitude', 'logo_url', 'status', 'commission_rate',
        'approved_at', 'approved_by',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'commission_rate' => 'decimal:4',
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

    public function shippingSetting(): HasOne
    {
        return $this->hasOne(VendorShippingSetting::class);
    }

    public function hasShippingSetting(): bool
    {
        return $this->shippingSetting()->exists();
    }

    public function isShippingConfigured(): bool
    {
        return $this->hasShippingSetting();
    }

    /**
     * Periksa apakah toko vendor memiliki titik koordinat lokasi yang tersedia dan valid.
     */
    public function hasCoordinates(): bool
    {
        $rawLatitude = $this->getAttributes()['latitude'] ?? null;
        $rawLongitude = $this->getAttributes()['longitude'] ?? null;

        if ($rawLatitude === null || $rawLongitude === null || ! is_numeric($rawLatitude) || ! is_numeric($rawLongitude)) {
            return false;
        }

        $latitude = (float) $rawLatitude;
        $longitude = (float) $rawLongitude;

        if (! is_finite($latitude) || ! is_finite($longitude)) {
            return false;
        }

        return $latitude >= -90.0
            && $latitude <= 90.0
            && $longitude >= -180.0
            && $longitude <= 180.0;
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

    public function campingPackages(): HasMany
    {
        return $this->hasMany(CampingPackage::class);
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(PlatformCommission::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(VendorInvoice::class);
    }
}
