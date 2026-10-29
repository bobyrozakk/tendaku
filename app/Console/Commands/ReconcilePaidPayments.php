<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payment\MidtransService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Pembayaran yang dilunasi lewat Edge Function Supabase hanya mengubah status
 * payment. Command ini melengkapinya di sisi Laravel: escrow ditahan, komisi
 * dicatat, dan biaya gateway QRIS dihitung. Aman dijalankan berulang.
 */
class ReconcilePaidPayments extends Command
{
    /**
     * @var string
     */
    protected $signature = 'payments:reconcile {--days=7 : Periksa pembayaran lunas dalam N hari terakhir}';

    /**
     * @var string
     */
    protected $description = 'Lengkapi escrow dan komisi untuk pembayaran Midtrans yang sudah lunas';

    public function handle(MidtransService $midtrans): int
    {
        $payments = Payment::withoutGlobalScopes()
            ->where('status', PaymentStatus::Paid->value)
            ->where('payment_method', 'midtrans_snap')
            ->where('updated_at', '>=', now()->subDays(max(1, (int) $this->option('days'))))
            ->where(function ($query): void {
                $query->whereNull('escrow_status')
                    ->orWhere('escrow_status', 'not_applicable');
            })
            ->get();

        $done = 0;

        foreach ($payments as $payment) {
            try {
                $midtrans->applyMonetization($payment);
                $done++;
            } catch (Throwable $e) {
                report($e);
                $this->error("Gagal memproses payment #{$payment->id}: {$e->getMessage()}");
            }
        }

        $this->info("Rekonsiliasi selesai: {$done} pembayaran diproses.");

        return self::SUCCESS;
    }
}
