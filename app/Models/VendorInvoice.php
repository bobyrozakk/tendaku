<?php

namespace App\Models;

use App\Traits\BelongsToVendor;
use Database\Factories\VendorInvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorInvoice extends Model
{
    /** @use HasFactory<VendorInvoiceFactory> */
    use BelongsToVendor, HasFactory;

    protected $fillable = [
        'vendor_id', 'settlement_id', 'number', 'type', 'period_start', 'period_end',
        'subtotal', 'commission_amount', 'fee_amount', 'total',
        'status', 'issued_at', 'paid_at', 'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'subtotal' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }
}
