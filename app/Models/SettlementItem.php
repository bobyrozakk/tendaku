<?php

namespace App\Models;

use App\Traits\BelongsToVendor;
use Database\Factories\SettlementItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementItem extends Model
{
    /** @use HasFactory<SettlementItemFactory> */
    use BelongsToVendor, HasFactory;

    protected $fillable = [
        'settlement_id', 'payment_id', 'platform_commission_id', 'vendor_id',
        'type', 'gross_amount', 'commission_amount', 'fee_amount', 'net_amount', 'notes',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function commission(): BelongsTo
    {
        return $this->belongsTo(PlatformCommission::class, 'platform_commission_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
