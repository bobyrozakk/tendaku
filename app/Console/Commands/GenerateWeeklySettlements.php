<?php

namespace App\Console\Commands;

use App\Services\Finance\SettlementService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateWeeklySettlements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monetization:generate-settlements
                            {--week= : Tanggal di dalam minggu yang akan diproses (YYYY-MM-DD). Default: minggu lalu}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Buat draft batch settlement mingguan untuk pembayaran dan komisi yang belum tercairkan';

    /**
     * Execute the console command.
     */
    public function handle(SettlementService $settlementService): int
    {
        $week = $this->option('week');

        try {
            $referenceDate = $week !== null && $week !== ''
                ? Carbon::parse((string) $week)
                : Carbon::today()->subWeek();
        } catch (\Exception) {
            $this->error('Format --week tidak valid. Gunakan tanggal YYYY-MM-DD.');

            return self::FAILURE;
        }

        $weekStart = $referenceDate->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $settlements = $settlementService->buildWeeklyDraft($weekStart);

        $this->info(sprintf(
            'Draft settlement %s s.d %s: %d batch dibuat.',
            $weekStart->toDateString(),
            $weekEnd->toDateString(),
            $settlements->count(),
        ));

        foreach ($settlements as $settlement) {
            $this->line(sprintf(
                '  #%d %s | %s | net Rp %s',
                $settlement->getKey(),
                $settlement->vendor?->name ?? 'Vendor #'.$settlement->vendor_id,
                $settlement->status,
                number_format((float) $settlement->net_payout, 0, ',', '.'),
            ));
        }

        return self::SUCCESS;
    }
}
