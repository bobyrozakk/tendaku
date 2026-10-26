<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job auto-cancel tagihan expired.
 * Rental pending yang expires_at < now → expired.
 * Payments pending terkait → expired (§4.2, §5.1).
 * Idempoten: tidak menyentuh status non-pending.
 *
 * @see docs/TENDAKU_payment_spec.md §4.2, §5.2
 */
class ExpirePendingRentals implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     * Ubah status rental pending yang expires_at < now menjadi 'expired'.
     * Sekaligus expire payments pending yang terkait dengan rental tersebut.
     */
    public function handle(): int
    {
        $now = Carbon::now();

        $affectedRows = DB::transaction(function () use ($now) {
            // Cari rental pending yang sudah lewat expires_at
            $expiredRentalIds = Rental::query()
                ->where('status', 'pending')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', $now)
                ->pluck('id');

            if ($expiredRentalIds->isEmpty()) {
                return 0;
            }

            // Expire payments pending yang terkait rental expired (§5.2: expire → expired)
            // Hanya expire jika rental belum punya pembayaran yang sudah paid
            // (jika sudah paid, rental tidak akan di-status pending lagi)
            Payment::query()
                ->whereIn('rental_id', $expiredRentalIds)
                ->where('status', 'pending')
                ->update([
                    'status' => 'expired',
                    'updated_at' => $now,
                ]);

            // Expire rental
            // Guard status: rental yang baru dikonfirmasi webhook di antara SELECT dan UPDATE tidak boleh ditimpa
            $count = Rental::query()
                ->whereIn('id', $expiredRentalIds)
                ->where('status', 'pending')
                ->update([
                    'status' => 'expired',
                    'updated_at' => $now,
                ]);

            return $count;
        });

        if ($affectedRows > 0) {
            Log::info("ExpirePendingRentals: {$affectedRows} rental pending berhasil diubah statusnya menjadi 'expired'.");
        }

        return $affectedRows;
    }
}
