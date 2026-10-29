<?php

return [

    /*
    | Konfigurasi Biaya Admin (Platform Fee)
    |
    | platform_fee_rate : Persentase fee dari nominal pembayaran online pertama.
    |                     Default 2% (0.02). Dapat dilihat/diubah di panel Boby.
    |
    | platform_fee_min  : Minimum fee dalam rupiah. Default Rp1.000.
    |
    | dp_percentage     : Persentase DP dari grand_total, default 50% (D4).
    */
    'platform_fee_rate' => env('PAYMENT_PLATFORM_FEE_RATE', 0.02),
    'platform_fee_min' => env('PAYMENT_PLATFORM_FEE_MIN', 1000),
    'dp_percentage' => env('PAYMENT_DP_PERCENTAGE', 50),

    // pembayaran
    'enabled_channels' => [
        // 'other_qris',
        'bank_transfer',
        'bca_va',
        'bni_va',
        'bri_va',
        'echannel',
        'permata_va',
        'cimb_va',
        'other_va',
    ],
];
