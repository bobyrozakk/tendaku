<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalDetail extends Model
{
    // Tidak pakai BelongsToVendor: tabel ini tidak punya kolom vendor_id,
    // scoping dilakukan lewat relasi ke `rental`.

    protected $fillable = [
        'rental_id', 'master_item_id', 'item_unit_id',
        'daily_rate_snapshot', 'late_fee_per_day_snapshot',
        'condition_before', 'condition_after', 'checklist_notes',
        'returned_at', 'late_days', 'late_fee', 'damage_fee',
    ];

    protected $casts = [
        'daily_rate_snapshot' => 'decimal:2',
        'late_fee_per_day_snapshot' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'damage_fee' => 'decimal:2',
        'returned_at' => 'datetime',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function masterItem(): BelongsTo
    {
        return $this->belongsTo(MasterItem::class);
    }

    public function itemUnit(): BelongsTo
    {
        return $this->belongsTo(ItemUnit::class);
    }

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }
}
