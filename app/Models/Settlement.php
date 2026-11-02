<?php

namespace App\Models;

use App\Traits\BelongsToVendor;
use Database\Factories\SettlementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Settlement extends Model
{
    /** @use HasFactory<SettlementFactory> */
    use BelongsToVendor, HasFactory;

    protected $fillable = [
        'vendor_id', 'period_start', 'period_end', 'status',
        'gross_qris', 'gross_fine_qris', 'commission_total', 'gateway_fee_total',
        'fine_fee_total', 'cash_commission_offset', 'transfer_fee',
        'adjustment_amount', 'net_payout',
        'bank_name', 'bank_account_number', 'bank_account_name',
        'reference_number', 'proof_url', 'approved_by', 'approved_at', 'paid_at', 'notes',
        'iris_reference_no', 'iris_status', 'iris_idempotency_key', 'iris_bank_code',
        'iris_error', 'iris_requested_at', 'iris_approved_at', 'iris_completed_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'gross_qris' => 'decimal:2',
        'gross_fine_qris' => 'decimal:2',
        'commission_total' => 'decimal:2',
        'gateway_fee_total' => 'decimal:2',
        'fine_fee_total' => 'decimal:2',
        'cash_commission_offset' => 'decimal:2',
        'transfer_fee' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'net_payout' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'iris_requested_at' => 'datetime',
        'iris_approved_at' => 'datetime',
        'iris_completed_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SettlementItem::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(VendorInvoice::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(PlatformCommission::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Payout Iris yang masih berjalan tidak boleh ditandai lunas secara manual.
     *
     * Status `requested`/`unknown` tanpa reference tetap dianggap aktif karena
     * permintaan bisa saja sudah diterima Midtrans sebelum koneksi terputus.
     */
    public function hasActiveIrisPayout(): bool
    {
        return ! $this->isPaid()
            && $this->iris_status !== null
            && ! in_array($this->iris_status, ['failed', 'rejected'], true);
    }
}
