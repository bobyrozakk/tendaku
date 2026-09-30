<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPaymentSetting extends Model
{
    protected $fillable = [
        'vendor_id', 'midtrans_merchant_id', 'midtrans_server_key', 'midtrans_client_key',
        'is_midtrans_active', 'bank_name', 'bank_account_number', 'bank_account_name',
        'qris_image_url', 'wa_api_token',
    ];

    protected $hidden = [
        'midtrans_server_key', 'wa_api_token',
    ];

    protected $casts = [
        'is_midtrans_active' => 'boolean',
        'midtrans_server_key' => 'encrypted',
        'wa_api_token' => 'encrypted',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
