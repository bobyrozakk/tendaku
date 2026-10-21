<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kanal Notifikasi
    |--------------------------------------------------------------------------
    |
    | Kanal yang dipakai untuk pengingat pengembalian barang. Nilai yang
    | didukung:
    |
    |   "whatsapp" : kirim via Fonnte WhatsApp API (butuh FONNTE_TOKEN)
    |   "in_app"   : hanya dicatat di tabel `notifications` (ditampilkan di aplikasi)
    |   "email"    : dikirim via mailer Laravel (butuh MAIL_MAILER yang benar)
    |
    | Kode WhatsApp TIDAK dihapus saat kanal diganti — cukup ubah nilai ini.
    |
    */

    'channel' => env('NOTIFICATION_CHANNEL', 'whatsapp'),

    /*
    |--------------------------------------------------------------------------
    | Pengingat Pengembalian Barang
    |--------------------------------------------------------------------------
    |
    | "hour"    : jam eksekusi pengingat (WIB) oleh scheduler harian.
    |             ✅ Disetujui Boby (2026-10-21): 09:00 Asia/Jakarta.
    | "timezone": zona waktu acuan untuk menghitung "hari ini" dan "H-1".
    | "days"    : daftar offset hari sebelum tanggal kembali yang memicu
    |             pengingat. 1 = H-1, 0 = hari-H.
    |
    */

    'reminder' => [
        'enabled' => (bool) env('REMINDER_ENABLED', true),
        'hour' => (int) env('REMINDER_HOUR', 9),
        'timezone' => env('REMINDER_TIMEZONE', 'Asia/Jakarta'),
        'days' => [1, 0],
    ],

    /*
    |--------------------------------------------------------------------------
    | Konten Pesan
    |--------------------------------------------------------------------------
    |
    | "include_late_fee" : sertakan informasi denda keterlambatan per barang.
    | "include_item_list": sertakan daftar barang yang disewa.
    |
    */

    'message' => [
        'include_item_list' => (bool) env('REMINDER_INCLUDE_ITEM_LIST', true),
        'include_late_fee' => (bool) env('REMINDER_INCLUDE_LATE_FEE', true),
    ],

];
