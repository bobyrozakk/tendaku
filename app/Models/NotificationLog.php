<?php

namespace App\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use BelongsToVendor;

    protected $table = 'notifications';

    protected $fillable = [
        'vendor_id', 'user_id', 'rental_id', 'recipient_phone',
        'channel', 'type', 'message', 'status', 'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}
