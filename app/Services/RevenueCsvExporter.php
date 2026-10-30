<?php

namespace App\Services;

use Carbon\Carbon;
use Generator;

class RevenueCsvExporter
{
    public function __construct(
        private readonly RevenueReportService $revenueReportService,
    ) {}

    /**
     * Stream baris CSV tanpa memuat seluruh transaksi ke memory.
     *
     * @param  array<string, mixed>  $filters
     * @return Generator<int, list<string|int|float|null>>
     */
    public function stream(array $filters): Generator
    {
        yield [
            'Kode Booking',
            'Vendor',
            'Tipe Pembayaran',
            'Metode',
            'Kanal',
            'Nominal',
            'Komisi',
            'MDR',
            'Status Escrow',
            'Batch',
            'Dibayar Pada',
        ];

        $query = $this->revenueReportService->transactionQuery($filters)
            ->orderByDesc('payments.paid_at')
            ->orderByDesc('payments.id');

        foreach ($query->cursor() as $row) {
            yield [
                (string) $row->booking_code,
                (string) $row->vendor_name,
                $this->paymentTypeLabel((string) $row->payment_type),
                $this->paymentMethodLabel((string) $row->payment_method),
                $row->payment_channel !== null ? strtoupper((string) $row->payment_channel) : '',
                (float) $row->amount,
                (float) ($row->commission_amount ?? 0),
                (float) $row->gateway_fee_amount,
                $this->escrowStatusLabel((string) $row->escrow_status),
                $row->settlement_id !== null ? (string) $row->settlement_id : '',
                $row->paid_at !== null ? Carbon::parse($row->paid_at)->format('Y-m-d H:i:s') : '',
            ];
        }
    }

    private function paymentTypeLabel(string $paymentType): string
    {
        return match ($paymentType) {
            'dp' => 'DP',
            'full' => 'Bayar Penuh',
            'remaining' => 'Pelunasan',
            'fine' => 'Denda',
            'deposit' => 'Deposit',
            'deposit_refund' => 'Refund Deposit',
            'refund' => 'Refund',
            default => $paymentType,
        };
    }

    private function paymentMethodLabel(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'cash' => 'Tunai',
            'midtrans_snap' => 'Midtrans',
            'transfer' => 'Transfer',
            default => $paymentMethod,
        };
    }

    private function escrowStatusLabel(string $escrowStatus): string
    {
        return match ($escrowStatus) {
            'held' => 'Ditahan',
            'released' => 'Dicairkan',
            'refunded' => 'Dikembalikan',
            'void' => 'Void',
            'not_applicable' => 'Tidak berlaku',
            default => $escrowStatus,
        };
    }
}
