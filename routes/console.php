<?php

use App\Jobs\ExpirePendingRentals;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('rentals:expire-pending', function () {
    $job = new ExpirePendingRentals;
    $count = $job->handle();
    $this->info("Expired {$count} pending rental(s).");
})->purpose('Ubah rental pending yang kedaluwarsa menjadi expired');

Schedule::job(new ExpirePendingRentals)->everyMinute();

// Lengkapi escrow dan komisi untuk pembayaran yang dilunasi lewat Edge Function Supabase.
Schedule::command('payments:reconcile')->everyMinute()->withoutOverlapping();

/*
 * Pengingat pengembalian barang via Gmail (H-5 jam sebelum batas waktu pengembalian)
 * dikirim ke email customer secara periodik.
 */
Schedule::command('rentals:send-email-reminders')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

/*
 * Draft batch settlement mingguan untuk vendor (komisi, escrow, piutang tunai).
 * Dijalankan Senin dini hari sesuai config/monetization.php.
 */
Schedule::command('monetization:generate-settlements')
    ->weeklyOn(Carbon::MONDAY, (string) config('monetization.settlement.time', '01:00'))
    ->timezone((string) config('monetization.settlement.timezone', 'Asia/Jakarta'));
