<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampingPackageItem extends Model
{
    protected $fillable = [
        'camping_package_id', 'master_item_id', 'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function campingPackage(): BelongsTo
    {
        return $this->belongsTo(CampingPackage::class);
    }

    public function masterItem(): BelongsTo
    {
        return $this->belongsTo(MasterItem::class);
    }
}
