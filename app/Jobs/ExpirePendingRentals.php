<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpirePendingRentals implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     * Ubah status rental pending yang expires_at < now menjadi 'expired'.
     * Idempoten dan tidak menyentuh status non-pending.
     */
    public function handle(): int
    {
        $now = Carbon::now();

        // Cari rental pending yang melewati 'expires_at' dan belum memiliki pembayaran 'paid'
        $affectedRows = DB::transaction(function () use ($now) {
            $expiredRentals = Rental::query()
                ->where('status', 'pending')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', $now)
                ->whereDoesntHave('payments', function ($query) {
                    $query->where('status', 'paid');
                })
                ->lockForUpdate()
                ->get();

            if ($expiredRentals->isEmpty()) {
                return 0;
            }

            $rentalIds = $expiredRentals->pluck('id')->all();

            Rental::query()
                ->whereIn('id', $rentalIds)
                ->update([
                    'status' => 'expired',
                    'updated_at' => $now,
                ]);

            Payment::query()
                ->whereIn('rental_id', $rentalIds)
                ->where('status', 'pending')
                ->update([
                    'status' => 'failed',
                    'updated_at' => $now,
                ]);

            return count($rentalIds);
        });

        if ($affectedRows > 0) {
            Log::info("ExpirePendingRentals: {$affectedRows} rental pending berhasil diubah statusnya menjadi 'expired'.");
        }

        return $affectedRows;
    }
}
