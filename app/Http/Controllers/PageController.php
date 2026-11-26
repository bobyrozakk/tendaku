<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Tampilkan halaman terpadu Layanan Tendaku.
     * Mencakup: Sewa Tenda, Sewa Matras, Perlengkapan Camping, Panduan Penyewaan, dan FAQ.
     */
    public function layanan(): View
    {
        return view('pages.layanan');
    }

    /**
     * Tampilkan halaman terpadu Pusat Bantuan & Kebijakan Tendaku.
     * Mencakup: Syarat & Ketentuan, Kebijakan Privasi, Deposit & Pengembalian, Kebijakan Pembatalan, dan Panduan Penggunaan.
     */
    public function bantuan(): View
    {
        return view('pages.bantuan');
    }

    /**
     * Tampilkan halaman resmi Hubungi Kami Tendaku.
     * Memuat kontak asli Malang, Jawa Timur, email admintendaku@gmail.com, dan tautan WhatsApp.
     */
    public function hubungiKami(): View
    {
        return view('pages.hubungi-kami');
    }
}
