<?php

return [
    // Aturan checkout (satu sumber untuk controller, service, dan summary)
    'payment_expiry_minutes' => (int) env('BOOKING_PAYMENT_EXPIRY_MINUTES', 60),
    'admin_fee_rate' => (float) env('BOOKING_ADMIN_FEE_RATE', 0.03),
    'admin_fee_minimum' => (int) env('BOOKING_ADMIN_FEE_MINIMUM', 2000),
    'default_pickup_time' => '09:00',
    'default_return_time' => '09:00',

    // Alasan pembatalan oleh customer (kunci disimpan di rentals.cancel_reason)
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
            'label' => 'Menunggu Penyelesaian',
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
