<?php

namespace App\Services;

interface WhatsAppService
{
    /**
     * Kirim pesan WhatsApp ke satu nomor telepon.
     *
     * Implementasi WAJIB mengembalikan `false` (bukan melempar exception) ketika
     * pengiriman gagal, supaya alur utama aplikasi tidak terganggu.
     *
     * @param  string  $phone  Nomor telepon tujuan (format bebas, dinormalisasi oleh implementasi).
     * @param  string  $message  Isi pesan.
     */
    public function send(string $phone, string $message): bool;
}
