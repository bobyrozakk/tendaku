<?php

namespace App\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use BelongsToVendor, HasFactory;

    protected $fillable = [
        'rental_id', 'vendor_id', 'received_by', 'payment_type', 'payment_method',
        'payment_channel', 'amount', 'gateway_fee_amount', 'midtrans_order_id',
        'midtrans_transaction_id', 'midtrans_status', 'payment_proof_url', 'status',
        'escrow_status', 'settlement_id', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_fee_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function commission(): HasOne
    {
        return $this->hasOne(PlatformCommission::class);
    }
}
