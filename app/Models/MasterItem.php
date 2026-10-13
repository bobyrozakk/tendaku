<?php

namespace App\Models;

use App\Traits\BelongsToVendor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /**
     * Scope untuk katalog publik customer:
     * Item harus aktif dan vendor terkait harus aktif (approved/active, not suspended).
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

    /**
     * Indikator umur barang dan kondisi rata-rata/tertua dari unit fisik (ItemUnit).
     */
    protected function itemAge(): Attribute
    {
        return Attribute::make(
            get: function () {
                $oldestPurchase = $this->units()
                    ->whereNotNull('purchase_date')
                    ->orderBy('purchase_date', 'asc')
                    ->value('purchase_date');

                if (! $oldestPurchase) {
                    return 'Baru tersedia';
                }

                $purchaseDate = Carbon::parse($oldestPurchase);
                $diffInMonths = (int) $purchaseDate->diffInMonths(Carbon::now());

                if ($diffInMonths < 1) {
                    $diffInDays = (int) $purchaseDate->diffInDays(Carbon::now());

                    return $diffInDays <= 0 ? 'Dibeli hari ini' : "Dibeli {$diffInDays} hari lalu";
                }

                if ($diffInMonths >= 12) {
                    $years = (int) floor($diffInMonths / 12);
                    $remMonths = $diffInMonths % 12;

                    return $remMonths > 0 ? "Dibeli {$years} tahun {$remMonths} bulan lalu" : "Dibeli {$years} tahun lalu";
                }

                return "Dibeli {$diffInMonths} bulan lalu";
            }
        );
    }

    /**
     * Kondisi dominan unit (badge kondisi)
     */
    protected function dominantCondition(): Attribute
    {
        return Attribute::make(
            get: function () {
                $condition = $this->units()
                    ->selectRaw('condition, count(*) as count')
                    ->groupBy('condition')
                    ->orderByDesc('count')
                    ->value('condition');

                return match ($condition) {
                    'excellent' => 'Sangat Baik (Prima)',
                    'good' => 'Baik & Siap Pakai',
                    'fair' => 'Cukup',
                    'damaged' => 'Perlu Perbaikan',
                    default => 'Terawat Prima',
                };
            }
        );
    }
}
