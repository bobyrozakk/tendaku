<?php

namespace App\Jobs;

use App\Services\Booking\RentalService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Job auto-cancel tagihan kedaluwarsa. Logikanya di RentalService::expire().
 * Idempoten: tidak menyentuh rental yang bukan pending atau sudah dibayar.
 */
class ExpirePendingRentals implements ShouldQueue
{
    use Queueable;

    public function handle(): int
    {
        $count = app(RentalService::class)->expirePastDue();

        if ($count > 0) {
            Log::info("ExpirePendingRentals: {$count} rental pending menjadi 'expired'.");
        }

        return $count;
    }
}
