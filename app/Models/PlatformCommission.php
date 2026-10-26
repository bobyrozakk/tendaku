<?php

namespace App\Models;

use App\Traits\BelongsToVendor;
use Database\Factories\PlatformCommissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformCommission extends Model
{
    /** @use HasFactory<PlatformCommissionFactory> */
    use BelongsToVendor, HasFactory;

    protected $fillable = [
        'payment_id', 'rental_id', 'vendor_id', 'base_amount', 'rate',
        'amount', 'status', 'settlement_id', 'recognized_at', 'voided_at', 'notes',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'rate' => 'decimal:4',
        'amount' => 'decimal:2',
        'recognized_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }
}
