<?php

namespace App\Services\Finance;

use App\Enums\PaymentType;
use App\Models\Payment;
use App\Models\PlatformCommission;
use App\Models\Rental;
use App\Models\Settlement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Membaca progres monetisasi dari sisi rental agar superadmin dapat melihat
 * satu alur utuh: booking, pembayaran, komisi, settlement, invoice, dan payout.
 */
class MonetizationMonitorService
{
    /**
     * Tahap operasional yang ditampilkan pada layar monitoring.
     *
     * @var array<string, string>
     */
    public const STAGE_LABELS = [
        'awaiting_payment' => 'Menunggu Pembayaran',
        'paid_in_progress' => 'Dibayar, Proses Rental',
        'ready_settlement' => 'Siap Settlement',
        'in_settlement' => 'Draft Settlement',
        'awaiting_transfer' => 'Menunggu Transfer',
        'paid_out' => 'Sudah Dicairkan',
        'cancelled' => 'Dibatalkan / Kedaluwarsa',
    ];

    /**
     * @var list<string>
     */
    private const ACTIVE_SETTLEMENT_STATUSES = ['draft', 'approved', 'paid'];

    /**
     * Query rental beserta relasi yang dibutuhkan tabel monitoring.
     *
     * @param  array{search?: string, vendor_id?: int|null, stage?: string}  $filters
     */
    public function query(array $filters = []): Builder
    {
        $query = Rental::withoutGlobalScope('vendor')
            ->with([
                'vendor:id,name,city',
                'customer:id,name,email',
                'payments' => fn ($paymentQuery) => $paymentQuery
                    ->withoutGlobalScope('vendor')
                    ->orderBy('payments.id')
                    ->with(['settlementItems.settlement.invoices']),
                'commissions' => fn ($commissionQuery) => $commissionQuery
                    ->withoutGlobalScope('vendor'),
            ]);

        $this->applyFilters($query, $filters);

        return $query;
    }

    /**
     * Ringkasan jumlah per tahap dan nilai komisi/payout.
     *
     * @param  array{search?: string, vendor_id?: int|null, stage?: string}  $filters
     * @return array{
     *     counts: array<string, int>,
     *     commission_accrued: float,
     *     awaiting_transfer_amount: float,
     *     awaiting_transfer_batches: int,
     *     paid_out_amount: float,
     *     paid_out_batches: int
     * }
     */
    public function summary(array $filters = []): array
    {
        $baseFilters = $filters;
        unset($baseFilters['stage']);

        $counts = [];

        foreach (array_keys(self::STAGE_LABELS) as $stage) {
            $counts[$stage] = $this->query(array_merge($baseFilters, [
                'stage' => $stage,
            ]))->count();
        }

        $vendorId = isset($filters['vendor_id']) && $filters['vendor_id'] !== null
            ? (int) $filters['vendor_id']
            : null;

        $commissionAccrued = (float) PlatformCommission::withoutGlobalScope('vendor')
            ->where('status', 'accrued')
            ->when($vendorId !== null, fn (Builder $query): Builder => $query->where('vendor_id', $vendorId))
            ->sum('amount');

        $approvedSettlements = Settlement::withoutGlobalScope('vendor')
            ->where('status', 'approved')
            ->when($vendorId !== null, fn (Builder $query): Builder => $query->where('vendor_id', $vendorId))
            ->get(['id', 'net_payout']);

        $paidSettlements = Settlement::withoutGlobalScope('vendor')
            ->where('status', 'paid')
            ->when($vendorId !== null, fn (Builder $query): Builder => $query->where('vendor_id', $vendorId))
            ->get(['id', 'net_payout']);

        return [
            'counts' => $counts,
            'commission_accrued' => $commissionAccrued,
            'awaiting_transfer_amount' => (float) $approvedSettlements->sum('net_payout'),
            'awaiting_transfer_batches' => $approvedSettlements->count(),
            'paid_out_amount' => (float) $paidSettlements->sum('net_payout'),
            'paid_out_batches' => $paidSettlements->count(),
        ];
    }

    /**
     * Ubah satu rental menjadi baris monitoring yang siap ditampilkan.
     *
     * @return array<string, mixed>
     */
    public function summarize(Rental $rental): array
    {
        $payments = $rental->payments->sortBy('id')->values();
        /** @var Collection<int, Payment> $paidPayments */
        $paidPayments = $payments->where('status', 'paid')->values();

        $latestPaidPayment = $paidPayments
            ->sortByDesc(fn (Payment $payment): string => (string) ($payment->paid_at ?? ''))
            ->first();

        $openPayment = $payments->first(
            fn (Payment $payment): bool => in_array($payment->status, ['pending', 'failed'], true)
        );

        $displayPayment = $openPayment ?? $payments->last();
        $paidTotal = (float) $paidPayments->sum('amount');
        $paidRentalTotal = (float) $paidPayments
            ->whereIn('payment_type', PaymentType::rentalValues())
            ->sum('amount');
        $remainingAmount = max(0.0, (float) $rental->grand_total - $paidRentalTotal);

        $commissions = $rental->commissions
            ->where('status', '!=', 'void')
            ->values();
        $commissionAmount = (float) $commissions->sum('amount');
        $commissionStatus = $this->commissionStatus($commissions);

        $settlements = $payments
            ->flatMap(fn (Payment $payment): Collection => $payment->settlementItems)
            ->pluck('settlement')
            ->filter(fn (mixed $settlement): bool => $settlement instanceof Settlement
                && in_array($settlement->status, self::ACTIVE_SETTLEMENT_STATUSES, true))
            ->unique('id')
            ->sortByDesc('id')
            ->values();

        /** @var Settlement|null $settlement */
        $settlement = $settlements->first();
        $invoice = $settlement?->invoices->firstWhere('status', '!=', 'void');
        $stage = $this->resolveStage($rental, $paidPayments, $settlements);
        $invoiceIssued = $invoice !== null;
        $payoutCompleted = $settlement?->status === 'paid';
        $requiresRefund = $stage === 'cancelled' && $paidPayments->isNotEmpty();
        $nextAction = $this->nextAction($stage);

        if ($stage === 'awaiting_payment' && $displayPayment?->status === 'expired') {
            $nextAction = 'Pembayaran terakhir kedaluwarsa; customer perlu membuat tagihan baru.';
        }

        if ($requiresRefund) {
            $nextAction = 'Booking dibatalkan setelah ada pembayaran. Refund ditangani terpisah.';
        }

        return [
            'rental_id' => $rental->id,
            'booking_code' => (string) $rental->booking_code,
            'booking_created_at' => $rental->created_at,
            'vendor_name' => $rental->vendor?->name ?? '—',
            'vendor_city' => $rental->vendor?->city,
            'customer_name' => $rental->guest_name
                ?: $rental->customer?->name
                ?: 'Pelanggan',
            'customer_contact' => $rental->guest_phone ?: $rental->customer?->email,
            'customer_type' => $rental->isGuest() ? 'Tamu' : 'Member',
            'rental_status' => (string) $rental->status,
            'rental_status_label' => $rental->displayStatusLabel(),
            'grand_total' => (float) $rental->grand_total,
            'paid_total' => $paidTotal,
            'remaining_amount' => $remainingAmount,
            'payment_label' => $this->paymentLabel($displayPayment, $paidPayments->isNotEmpty()),
            'payment_state' => (string) ($displayPayment?->status ?? 'none'),
            'payment_type_label' => $this->paymentTypeLabel($displayPayment),
            'payment_method_label' => $this->paymentMethodLabel($displayPayment),
            'payment_channel' => $displayPayment?->payment_channel,
            'payment_amount' => $displayPayment !== null ? (float) $displayPayment->amount : null,
            'payment_date' => $latestPaidPayment?->paid_at
                ?? $displayPayment?->paid_at
                ?? $displayPayment?->expires_at,
            'commission_amount' => $commissionAmount,
            'commission_status' => $commissionStatus,
            'commission_status_label' => $this->commissionStatusLabel($commissionStatus),
            'settlement_id' => $settlement?->id,
            'settlement_status' => $settlement?->status,
            'settlement_status_label' => $this->settlementStatusLabel($settlement?->status),
            'settlement_period_start' => $settlement?->period_start,
            'settlement_period_end' => $settlement?->period_end,
            'net_payout' => $settlement !== null ? (float) $settlement->net_payout : null,
            'invoice_id' => $invoice?->id,
            'invoice_number' => $invoice?->number,
            'invoice_status' => $invoice?->status,
            'invoice_status_label' => $this->invoiceStatusLabel($invoice?->status),
            'invoice_issued_at' => $invoice?->issued_at,
            'invoice_paid_at' => $invoice?->paid_at,
            'stage' => $stage,
            'stage_label' => self::STAGE_LABELS[$stage] ?? $stage,
            'next_action' => $nextAction,
            'requires_refund' => $requiresRefund,
            'action_url' => $this->actionUrl($settlement, $stage),
            'action_label' => $this->actionLabel($stage),
            'invoice_url' => $settlement !== null && $invoice !== null
                ? route('admin.settlements.invoice', $settlement)
                : null,
            'steps' => [
                [
                    'label' => 'Booking',
                    'done' => true,
                    'at' => $rental->created_at,
                ],
                [
                    'label' => 'Pembayaran',
                    'done' => $paidPayments->isNotEmpty(),
                    'at' => $latestPaidPayment?->paid_at,
                ],
                [
                    'label' => 'Rental selesai',
                    'done' => $rental->completed_at !== null,
                    'at' => $rental->completed_at,
                ],
                [
                    'label' => 'Invoice terbit',
                    'done' => $invoiceIssued,
                    'at' => $invoice?->issued_at,
                ],
                [
                    'label' => 'Payout',
                    'done' => $payoutCompleted,
                    'at' => $settlement?->paid_at,
                ],
            ],
        ];
    }

    /**
     * @param  array{search?: string, vendor_id?: int|null, stage?: string}  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $like = '%'.$search.'%';

            $query->where(function (Builder $searchQuery) use ($like): void {
                $searchQuery
                    ->where('rentals.booking_code', 'like', $like)
                    ->orWhere('rentals.guest_name', 'like', $like)
                    ->orWhere('rentals.guest_phone', 'like', $like)
                    ->orWhereHas('customer', fn (Builder $customerQuery): Builder => $customerQuery
                        ->where('users.name', 'like', $like)
                        ->orWhere('users.email', 'like', $like));
            });
        }

        if (isset($filters['vendor_id']) && $filters['vendor_id'] !== null) {
            $query->where('rentals.vendor_id', (int) $filters['vendor_id']);
        }

        $stage = (string) ($filters['stage'] ?? '');

        if (isset(self::STAGE_LABELS[$stage])) {
            $this->applyStage($query, $stage);
        }
    }

    private function applyStage(Builder $query, string $stage): void
    {
        match ($stage) {
            'awaiting_payment' => $query
                ->whereNotIn('rentals.status', ['cancelled', 'expired'])
                ->whereDoesntHave('payments', fn (Builder $paymentQuery): Builder => $paymentQuery
                    ->where('payments.status', 'paid')),
            'paid_in_progress' => $query
                ->whereNotIn('rentals.status', ['completed', 'cancelled', 'expired'])
                ->whereHas('payments', fn (Builder $paymentQuery): Builder => $paymentQuery
                    ->where('payments.status', 'paid'))
                ->whereDoesntHave('payments.settlementItems.settlement', fn (Builder $settlementQuery): Builder => $settlementQuery
                    ->whereIn('settlements.status', self::ACTIVE_SETTLEMENT_STATUSES)),
            'ready_settlement' => $query
                ->where('rentals.status', 'completed')
                ->whereHas('payments', fn (Builder $paymentQuery): Builder => $paymentQuery
                    ->where('payments.status', 'paid'))
                ->whereDoesntHave('payments.settlementItems.settlement', fn (Builder $settlementQuery): Builder => $settlementQuery
                    ->whereIn('settlements.status', self::ACTIVE_SETTLEMENT_STATUSES)),
            'in_settlement' => $query
                ->whereHas('payments.settlementItems.settlement', fn (Builder $settlementQuery): Builder => $settlementQuery
                    ->where('settlements.status', 'draft'))
                ->whereDoesntHave('payments.settlementItems.settlement', fn (Builder $settlementQuery): Builder => $settlementQuery
                    ->whereIn('settlements.status', ['approved', 'paid'])),
            'awaiting_transfer' => $query
                ->whereHas('payments.settlementItems.settlement', fn (Builder $settlementQuery): Builder => $settlementQuery
                    ->where('settlements.status', 'approved'))
                ->whereDoesntHave('payments.settlementItems.settlement', fn (Builder $settlementQuery): Builder => $settlementQuery
                    ->where('settlements.status', 'paid')),
            'paid_out' => $query
                ->whereHas('payments.settlementItems.settlement', fn (Builder $settlementQuery): Builder => $settlementQuery
                    ->where('settlements.status', 'paid')),
            'cancelled' => $query
                ->whereIn('rentals.status', ['cancelled', 'expired']),
            default => null,
        };
    }

    /**
     * @param  Collection<int, Payment>  $paidPayments
     * @param  Collection<int, Settlement>  $settlements
     */
    private function resolveStage(
        Rental $rental,
        Collection $paidPayments,
        Collection $settlements,
    ): string {
        if (in_array($rental->status, ['cancelled', 'expired'], true)) {
            return 'cancelled';
        }

        if ($paidPayments->isEmpty()) {
            return 'awaiting_payment';
        }

        $settlementStatuses = $settlements->pluck('status');

        if ($settlementStatuses->contains('paid')) {
            return 'paid_out';
        }

        if ($settlementStatuses->contains('approved')) {
            return 'awaiting_transfer';
        }

        if ($settlementStatuses->contains('draft')) {
            return 'in_settlement';
        }

        if ($rental->status === 'completed') {
            return 'ready_settlement';
        }

        return 'paid_in_progress';
    }

    /**
     * @param  Collection<int, PlatformCommission>  $commissions
     */
    private function commissionStatus(Collection $commissions): string
    {
        if ($commissions->isEmpty()) {
            return 'none';
        }

        $statuses = $commissions->pluck('status')->unique();

        return $statuses->count() > 1 ? 'mixed' : (string) $statuses->first();
    }

    private function paymentLabel(?Payment $payment, bool $hasPaidPayment): string
    {
        if ($payment === null) {
            return 'Belum ada tagihan';
        }

        return match ($payment->status) {
            'pending' => $hasPaidPayment ? 'Menunggu pelunasan' : 'Menunggu pembayaran',
            'paid' => $payment->payment_type === PaymentType::Dp->value ? 'DP dibayar' : 'Lunas',
            'expired' => 'Kedaluwarsa',
            'failed' => 'Gagal',
            'cancelled' => 'Dibatalkan',
            'refunded' => 'Refund',
            default => ucfirst((string) $payment->status),
        };
    }

    private function paymentTypeLabel(?Payment $payment): string
    {
        if ($payment === null) {
            return '—';
        }

        return PaymentType::tryFrom((string) $payment->payment_type)?->label()
            ?? ucfirst((string) $payment->payment_type);
    }

    private function paymentMethodLabel(?Payment $payment): string
    {
        if ($payment === null) {
            return '—';
        }

        return match ($payment->payment_method) {
            'cash' => 'Tunai',
            'midtrans_snap' => 'Midtrans',
            'transfer' => 'Transfer',
            default => $payment->payment_method !== null
                ? ucfirst(str_replace('_', ' ', (string) $payment->payment_method))
                : '—',
        };
    }

    private function commissionStatusLabel(string $status): string
    {
        return match ($status) {
            'accrued' => 'Accrued',
            'settled' => 'Masuk Batch',
            'mixed' => 'Sebagian Masuk Batch',
            'void' => 'Void',
            default => 'Belum Ada',
        };
    }

    private function settlementStatusLabel(?string $status): string
    {
        return match ($status) {
            'draft' => 'Draft',
            'approved' => 'Disetujui',
            'paid' => 'Dibayar',
            'cancelled' => 'Dibatalkan',
            default => '—',
        };
    }

    private function invoiceStatusLabel(?string $status): string
    {
        return match ($status) {
            'issued' => 'Menunggu Transfer',
            'paid' => 'Lunas',
            'void' => 'Void',
            default => '—',
        };
    }

    private function nextAction(string $stage): string
    {
        return match ($stage) {
            'awaiting_payment' => 'Customer belum menyelesaikan pembayaran.',
            'paid_in_progress' => 'Pembayaran diterima. Tunggu kasir memproses pickup dan pengembalian.',
            'ready_settlement' => 'Rental sudah selesai dan siap dimasukkan ke draft settlement.',
            'in_settlement' => 'Draft settlement menunggu persetujuan superadmin.',
            'awaiting_transfer' => 'Invoice sudah terbit. Transfer net payout lalu tandai batch lunas.',
            'paid_out' => 'Payout selesai. Invoice vendor sudah lunas.',
            default => 'Booking dibatalkan atau kedaluwarsa dan tidak masuk payout normal.',
        };
    }

    private function actionUrl(?Settlement $settlement, string $stage): ?string
    {
        if ($settlement !== null) {
            return route('admin.settlements', ['settlement' => $settlement->id]);
        }

        if ($stage === 'ready_settlement') {
            return route('admin.settlements');
        }

        return null;
    }

    private function actionLabel(string $stage): string
    {
        return match ($stage) {
            'ready_settlement', 'in_settlement', 'awaiting_transfer' => 'Kelola Settlement',
            'paid_out' => 'Buka Settlement',
            default => 'Lihat Detail',
        };
    }
}
