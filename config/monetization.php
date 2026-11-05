<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Komisi platform
    |--------------------------------------------------------------------------
    |
    | Komisi hanya dikenakan pada pembayaran DP dan pelunasan. Nilai minimum
    | berlaku per pembayaran, bukan per rental.
    |
    */

    'commission' => [
        'rate' => (float) env('MONETIZATION_COMMISSION_RATE', 0.05),
        'minimum' => (int) env('MONETIZATION_COMMISSION_MINIMUM', 2500),
    ],

    /*
    |--------------------------------------------------------------------------
    | MDR QRIS
    |--------------------------------------------------------------------------
    |
    | Tarif QRIS mengikuti kebijakan BI untuk merchant UKE/UME/UBE. Nominal
    | pada atau di bawah ambang tidak dikenai MDR.
    |
    */

    'qris' => [
        'mdr_threshold' => (int) env('MONETIZATION_QRIS_MDR_THRESHOLD', 100000),
        'mdr_rate_below_threshold' => (float) env('MONETIZATION_QRIS_MDR_RATE_BELOW', 0.0),
        'mdr_rate_above_threshold' => (float) env('MONETIZATION_QRIS_MDR_RATE_ABOVE', 0.007),
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch settlement
    |--------------------------------------------------------------------------
    */

    'settlement' => [
        'schedule' => 'weekly',
        'day' => 'monday',
        'time' => '01:00',
        'timezone' => 'Asia/Jakarta',
        'transfer_fee' => (int) env('MONETIZATION_TRANSFER_FEE', 3500),
        'minimum_payout' => (int) env('MONETIZATION_MINIMUM_PAYOUT', 50000),
    ],

    // Denda tidak dikenai komisi 5%.
    'fine_commission_rate' => 0.0,

    // Komisi tunai dipotong dari payout QRIS batch berikutnya.
    'cash_commission_net_off' => true,

];
