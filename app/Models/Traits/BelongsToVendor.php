<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Scoping multi-tenant otomatis lewat kolom `vendor_id`.
 *
 * Pasang trait ini di model yang punya kolom `vendor_id`
 * (ItemCategory, MasterItem, ItemUnit, Rental, Payment, NotificationLog).
 *
 * - super_admin (vendor_id null di users) TIDAK kena scope ini,
 *   jadi bisa lihat semua vendor.
 * - vendor_owner / vendor_admin hanya lihat data vendor_id miliknya sendiri.
 * - Saat create(), vendor_id otomatis diisi dari user yang login
 *   kalau belum di-set manual.
 */
trait BelongsToVendor
{
    protected static function bootBelongsToVendor(): void
    {
        static::addGlobalScope('vendor', function (Builder $builder) {
            $user = Auth::user();

            if ($user && $user->vendor_id !== null) {
                $builder->where(
                    $builder->getModel()->getTable() . '.vendor_id',
                    $user->vendor_id
                );
            }
        });

        static::creating(function ($model) {
            $user = Auth::user();

            if (empty($model->vendor_id) && $user && $user->vendor_id !== null) {
                $model->vendor_id = $user->vendor_id;
            }
        });
    }

    /**
     * Lewati global scope untuk keperluan Super Admin
     * yang perlu lihat/filter data vendor tertentu secara eksplisit.
     *
     * Contoh: MasterItem::forVendor(3)->get();
     */
    public function scopeForVendor(Builder $query, int $vendorId): Builder
    {
        return $query->withoutGlobalScope('vendor')->where('vendor_id', $vendorId);
    }

    /**
     * Lewati global scope sepenuhnya (khusus Super Admin / job internal).
     *
     * Contoh: MasterItem::allVendors()->get();
     */
    public function scopeAllVendors(Builder $query): Builder
    {
        return $query->withoutGlobalScope('vendor');
    }
}
