<?php

use App\Jobs\ExpirePendingRentals;
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
