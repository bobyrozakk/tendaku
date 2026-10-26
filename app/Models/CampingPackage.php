<?php

namespace App\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampingPackage extends Model
{
    use BelongsToVendor, SoftDeletes;

    protected $fillable = [
        'vendor_id', 'name', 'slug', 'description', 'price',
        'duration_days', 'max_people', 'image_url', 'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'max_people' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Scope katalog publik: paket aktif dan vendornya aktif/approved.
     */
    public function scopePublicCatalog(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereHas('vendor', function (Builder $vendorQuery) {
                $vendorQuery->whereIn('status', ['active', 'approved']);
            });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function packageItems(): HasMany
    {
        return $this->hasMany(CampingPackageItem::class);
    }

    public function masterItems(): BelongsToMany
    {
        return $this->belongsToMany(MasterItem::class, 'camping_package_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /**
     * Total tarif harian seluruh isi paket bila disewa satuan.
     * Dipakai untuk menampilkan nilai hemat paket.
     */
    protected function itemDailyRateTotal(): Attribute
    {
        return Attribute::make(
            get: fn (): float => (float) $this->masterItems->sum(
                fn (MasterItem $item) => (float) $item->daily_rate * (int) $item->pivot->quantity
            )
        );
    }

    /**
     * Harga normal bila isi paket disewa terpisah selama durasi paket.
     */
    protected function normalPrice(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->item_daily_rate_total * max(1, (int) $this->duration_days)
        );
    }

    /**
     * Nominal penghematan dibanding menyewa alat satu per satu.
     */
    protected function savings(): Attribute
    {
        return Attribute::make(
            get: fn (): float => max(0, $this->normal_price - (float) $this->price)
        );
    }

    /**
     * Jumlah unit alat di dalam paket (menjumlahkan kuantitas tiap produk).
     */
    protected function totalItemsCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => (int) $this->packageItems->sum('quantity')
        );
    }
}
