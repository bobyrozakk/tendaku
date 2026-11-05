<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MasterItemImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'master_item_id',
        'image_path',
        'image_url',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Hapus file fisik gambar dari storage public saat record foto dihapus.
     */
    protected static function booted(): void
    {
        static::deleting(function (MasterItemImage $image) {
            if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
                Storage::disk('public')->delete($image->image_path);
            }
        });
    }

    public function masterItem(): BelongsTo
    {
        return $this->belongsTo(MasterItem::class, 'master_item_id');
    }
}
