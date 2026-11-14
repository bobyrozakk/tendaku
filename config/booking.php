<?php

return [
    'payment_expiry_minutes' => (int) env('BOOKING_PAYMENT_EXPIRY_MINUTES', 60),
    'admin_fee_rate' => (float) env('BOOKING_ADMIN_FEE_RATE', 0.03),
    'admin_fee_minimum' => (int) env('BOOKING_ADMIN_FEE_MINIMUM', 2000),
    'delivery_fee' => (int) env('BOOKING_DELIVERY_FEE', 20000),
    'default_pickup_time' => '09:00',
    'default_return_time' => '09:00',

    'cancel_reasons' => [
        'schedule_changed' => 'Jadwal sewa berubah',
        'wrong_order' => 'Salah memilih alat atau tanggal',
        'found_alternative' => 'Sudah menemukan alternatif lain',
        'other' => 'Lainnya',
    ],

    'statuses' => [
        'pending' => [
            'label' => 'Menunggu Pembayaran',
            'filter' => 'Menunggu Pembayaran / Validasi',
        ],
        'confirmed' => [
            'label' => 'Dikonfirmasi',
            'filter' => 'Dikonfirmasi',
        ],
        'picked_up' => [
            'label' => 'Sedang Disewa',
            'filter' => 'Sedang Disewa',
        ],
        'returned' => [
            'label' => 'Dikembalikan',
            'filter' => 'Dikembalikan',
        ],
        'completed' => [
            'label' => 'Selesai',
            'filter' => 'Selesai',
        ],
        'cancelled' => [
            'label' => 'Dibatalkan',
            'filter' => 'Dibatalkan',
        ],
        'expired' => [
            'label' => 'Kedaluwarsa',
            'filter' => 'Kedaluwarsa',
        ],
    ],
];
