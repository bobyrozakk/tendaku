<?php

namespace App\Jobs;

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

        // Update rental berstatus 'pending' yang telah melewati 'expires_at'
        $affectedRows = DB::transaction(function () use ($now) {
            return Rental::query()
                ->where('status', 'pending')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', $now)
                ->update([
                    'status' => 'expired',
                    'updated_at' => $now,
                ]);
        });

        if ($affectedRows > 0) {
            Log::info("ExpirePendingRentals: {$affectedRows} rental pending berhasil diubah statusnya menjadi 'expired'.");
        }

        return $affectedRows;
    }
}
