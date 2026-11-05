<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Traits\BelongsToVendor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $rental_id
 * @property int $vendor_id
 * @property int|null $received_by
 * @property string $payment_type
 * @property string $channel
 * @property string|null $method
 * @property string|null $order_id
 * @property string|null $midtrans_order_id
 * @property string|null $midtrans_transaction_id
 * @property string|null $midtrans_status
 * @property string|null $snap_token
 * @property int $amount
 * @property int $platform_fee
 * @property string $status
 * @property int|null $vendor_payout_id
 * @property Carbon|null $paid_at
 * @property Carbon|null $expires_at
 * @property array|null $raw_notification
 * @property string|null $payment_proof_url
 */
class Payment extends Model
{
    use BelongsToVendor, HasFactory;

    protected $attributes = [
        'payment_method' => 'midtrans_snap',
        'channel' => 'midtrans',
        'status' => 'pending',
    ];

    protected $fillable = [
        // Relasi dan penerimaan pembayaran
        'rental_id',
        'vendor_id',
        'received_by',
        'payment_type',

        // Metode dan kanal pembayaran dari kedua versi
        'payment_method',
        'payment_channel',
        'channel',
        'method',

        // Identitas transaksi Midtrans
        'order_id',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'midtrans_status',
        'snap_token',

        // Nominal dan biaya
        'amount',
        'platform_fee',
        'gateway_fee_amount',

        // Status dan pencairan
        'status',
        'escrow_status',
        'vendor_payout_id',
        'settlement_id',

        // Waktu pembayaran
        'paid_at',
        'expires_at',

        // Data pendukung
        'raw_notification',
        'payment_proof_url',
    ];

    protected $casts = [
        // Nominal transaksi utama disimpan sebagai integer.
        'amount' => 'integer',
        'platform_fee' => 'integer',

        // Pertahankan tipe ini jika migration menggunakan decimal.
        'gateway_fee_amount' => 'decimal:2',

        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'raw_notification' => 'array',
    ];

    /**
     * Ekstrak informasi Virtual Account dari notifikasi Midtrans.
     *
     * @return array{
     *     bank: string,
     *     va_number: string,
     *     biller_code: ?string,
     *     qr_code_url: ?string
     * }|null
     */
    public function getVaDetailsAttribute(): ?array
    {
        $raw = $this->raw_notification;

        if (empty($raw) || ! is_array($raw)) {
            return null;
        }

        if (
            ! empty($raw['va_numbers'])
            && is_array($raw['va_numbers'])
        ) {
            $first = $raw['va_numbers'][0] ?? null;

            if (! is_array($first)) {
                return null;
            }

            return [
                'bank' => strtoupper(
                    $first['bank'] ?? 'Bank Transfer'
                ),
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

    /**
     * Pindah ke status baru jika transisinya valid.
     * Tidak menyimpan ke database; caller harus memanggil save().
     */
    public function transitionTo(PaymentStatus $next): bool
    {
        $current = PaymentStatus::tryFrom($this->status);

        if (
            $current === null
            || ! $current->canTransitionTo($next)
        ) {
            return false;
        }

        if ($current === $next) {
            return false;
        }

        $this->status = $next->value;

        if ($next === PaymentStatus::Paid) {
            $this->paid_at ??= now();
        }

        return true;
    }

    /**
     * Pembayaran yang masuk ke platform dan belum dicairkan,
     * untuk rental yang sudah picked_up.
     */
    public function scopePayoutEligible(Builder $query): Builder
    {
        return $query
            ->where('payments.status', 'paid')
            ->where('payments.channel', 'midtrans')
            ->whereNull('payments.vendor_payout_id')
            ->whereHas(
                'rental',
                fn (Builder $q) => $q->where(
                    'status',
                    'picked_up'
                )
            );
    }

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

    public function commission(): HasOne
    {
        return $this->hasOne(PlatformCommission::class);
    }

    public function settlementItems(): HasMany
    {
        return $this->hasMany(SettlementItem::class);
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }
}
