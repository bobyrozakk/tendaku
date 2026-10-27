<?php

return [
    // Batas waktu pembayaran setelah pesanan dibuat (jam)
    'payment_expiry_hours' => (int) env('RENTAL_PAYMENT_EXPIRY_HOURS', 24),
];
