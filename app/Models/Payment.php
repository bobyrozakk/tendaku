<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Traits\BelongsToVendor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $rental_id
 * @property int $vendor_id
 * @property int|null $received_by
 * @property string $payment_type full|dp|remaining|fine
 * @property string $channel midtrans|cash
 * @property string|null $method qris|va_bca|va_bni|...|cash
 * @property string|null $order_id unik; null untuk tunai
 * @property string|null $midtrans_transaction_id
 * @property string|null $midtrans_status
 * @property string|null $snap_token
 * @property int $amount
 * @property string $status pending|paid|expired|cancelled|failed|refunded
 * @property int $platform_fee
 * @property int|null $vendor_payout_id
 * @property Carbon|null $paid_at
 * @property Carbon|null $expires_at
 * @property array|null $raw_notification
 * @property string|null $payment_proof_url
 */
class Payment extends Model
{
    use BelongsToVendor;

    protected $attributes = [
        'payment_method' => 'midtrans_snap',
        'channel' => 'midtrans',
        'status' => 'pending',
    ];

    protected $fillable = [
        'rental_id', 'vendor_id', 'received_by',
        'payment_type', 'payment_method', 'channel', 'method',
        'order_id', 'midtrans_transaction_id', 'midtrans_status', 'snap_token',
        'amount', 'status', 'platform_fee', 'vendor_payout_id',
        'paid_at', 'expires_at',
        'payment_proof_url', 'raw_notification',
    ];

    protected $casts = [
        'amount' => 'integer',
        'platform_fee' => 'integer',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'raw_notification' => 'array',
    ];

    /**
     * Ekstrak informasi Virtual Account / Cara Bayar dari notifikasi Midtrans.
     *
     * @return array{bank: string, va_number: string, biller_code: ?string, qr_code_url: ?string}|null
     */
    public function getVaDetailsAttribute(): ?array
    {
        $raw = $this->raw_notification;
        if (empty($raw) || ! is_array($raw)) {
            return null;
        }

        // Bank
        if (! empty($raw['va_numbers']) && is_array($raw['va_numbers'])) {
            $first = $raw['va_numbers'][0];

            return [
                'bank' => strtoupper($first['bank'] ?? 'Bank Transfer'),
                'va_number' => $first['va_number'] ?? '',
                'biller_code' => null,
                'qr_code_url' => null,
            ];
        }

        if (! empty($raw['permata_va_number'])) {
            return [
                'bank' => 'PERMATA',
                'va_number' => $raw['permata_va_number'],
                'biller_code' => null,
                'qr_code_url' => null,
            ];
        }

        return null;
    }

    // ── Status ────────────────────────────────────────────────────────────────

    /**
     * Pindah ke status baru jika transisinya valid (lihat PaymentStatus).
     * Tidak menyimpan ke DB; caller yang memanggil save().
     * @return bool true jika status berubah, false jika diabaikan (idempotent)
     */
    public function transitionTo(PaymentStatus $next): bool
    {
        if (! PaymentStatus::from($this->status)->canTransitionTo($next)) {
            return false;
        }

        $this->status = $next->value;

        if ($next === PaymentStatus::Paid) {
            $this->paid_at ??= now();
        }

        return true;
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Scope: pembayaran yang masuk platform dan belum dicairkan, untuk rental yang sudah picked_up.
     */
    public function scopePayoutEligible(Builder $query): Builder
    {
        return $query
            ->where('payments.status', 'paid')
            ->where('payments.channel', 'midtrans')
            ->whereNull('payments.vendor_payout_id')
            ->whereHas('rental', fn (Builder $q) => $q->where('status', 'picked_up'));
    }

    // ── Relations ─────────────────────────────────────────────────────────────

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

    public function vendorPayout(): BelongsTo
    {
        return $this->belongsTo(VendorPayout::class);
    }
}
