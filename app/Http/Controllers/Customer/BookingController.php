<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * Ambil data static/dummy booking untuk keperluan status dan tanda terima.
     *
     * @return array<string, mixed>
     */
    private function getDummyBooking(?string $code = null): array
    {
        $bookingCode = $code ? strtoupper($code) : 'TDK-2026-B8921';

        return [
            'booking_code' => $bookingCode,
            'status' => 'confirmed',
            'status_label' => 'Sewa Aktif (Sedang Berjalan)',
            'created_at' => '02 Oktober 2026, 19:30 WIB',
            'expires_at' => '03 Oktober 2026, 07:30 WIB',

            // Status Spesifik
            'status_pembayaran' => [
                'code' => 'paid',
                'label' => 'Lunas',
                'sublabel' => 'Terverifikasi otomatis via QRIS Midtrans',
                'paid_at' => '02 Oktober 2026, 19:35 WIB',
                'method' => 'QRIS Realtime',
                'reference' => 'MID-TDK-9920141',
            ],
            'status_booking' => [
                'code' => 'active',
                'label' => 'Dikonfirmasi & Aktif',
                'sublabel' => 'Pesanan aktif dan masa sewa sedang berjalan',
            ],
            'status_pickup' => [
                'code' => 'picked_up',
                'label' => 'Sudah Diambil (Picked Up)',
                'sublabel' => 'Unit fisik telah diserahkan di workshop vendor',
                'picked_up_at' => '03 Oktober 2026, 09:15 WIB',
                'staff_name' => 'Rizky Pratama (Staf Mahameru)',
            ],
            'status_jaminan_ktp' => [
                'code' => 'held',
                'label' => 'KTP Fisik Ditahan di Toko (Jaminan)',
                'sublabel' => 'Tersimpan aman di loker jaminan vendor #A-04',
                'receipt_number' => 'REC-KTP-2026-8921',
                'received_at' => '03 Oktober 2026, 09:15 WIB',
                'received_by' => 'Rizky Pratama (Staff ID: #STF-02)',
                'vault_box' => 'Loker Brankas Jaminan A-04',
                'notes' => 'KTP asli fisik dipegang vendor sebagai jaminan sewa dan akan dikembalikan langsung saat pengembalian barang.',
            ],

            // Vendor
            'vendor' => [
                'name' => 'Mahameru Outdoor Equipment & Rental',
                'city' => 'Kota Batu, Jawa Timur',
                'address' => 'Jl. Oro-Oro Ombo No. 42, Temas, Kec. Batu, Kota Batu',
                'phone' => '0812-9876-5432',
                'pickup_location' => 'Store Offline Mahameru Outdoor (Kav. B4)',
                'operational_hours' => 'Senin - Minggu, 07:00 - 21:00 WIB',
            ],

            // Customer
            'customer' => [
                'name' => 'Budi Santoso',
                'phone' => '0857-8901-2345',
                'email' => 'budi.santoso@example.com',
                'nik' => '3579012304910002',
                'address' => 'Jl. Candi Mendut No. 18, Kel. Mojolangu, Kec. Lowokwaru, Kota Malang, Jawa Timur 65141',
            ],

            // Jadwal
            'pickup_date' => 'Sabtu, 03 Oktober 2026',
            'pickup_time' => '09:00 WIB',
            'return_date' => 'Senin, 05 Oktober 2026',
            'return_time' => '17:00 WIB',
            'rental_days' => 2,
            'pickup_method' => 'self_pickup',
            'pickup_method_label' => 'Ambil Sendiri di Toko Vendor',

            // Rincian Pembayaran
            'subtotal' => 310000,
            'deposit' => 100000,
            'delivery_fee' => 0,
            'discount' => 0,
            'grand_total' => 410000,

            // Daftar Barang
            'items' => [
                [
                    'id' => 1,
                    'unit_code' => 'TND-41-08',
                    'name' => 'Tenda Dome Arpenaz 4.1 Fresh & Black',
                    'category' => 'Tenda Camping',
                    'variant' => 'Kapasitas 4 Orang',
                    'specs' => 'Double layer waterproof 2000mm, frame alloy & pasak lengkap',
                    'condition_before' => 'Sangat Baik (Lengkap)',
                    'price_per_day' => 75000,
                    'qty' => 1,
                    'subtotal' => 150000,
                    'icon' => 'tent',
                ],
                [
                    'id' => 2,
                    'unit_code' => 'SB-80-14 & #SB-80-15',
                    'name' => 'Sleeping Bag Bulu Angsa Ultralight 800FP',
                    'category' => 'Perlengkapan Tidur',
                    'variant' => 'Comfort Limit -5°C',
                    'specs' => 'Bulu angsa alami bersih steril & compression sack',
                    'condition_before' => 'Sangat Baik (Bersih)',
                    'price_per_day' => 25000,
                    'qty' => 2,
                    'subtotal' => 100000,
                    'icon' => 'sleeping_bag',
                ],
                [
                    'id' => 3,
                    'unit_code' => 'KMP-WP-03',
                    'name' => 'Kompor Camping Portable Windproof + Adapter',
                    'category' => 'Peralatan Masak',
                    'variant' => 'Model Bunga Anti Angin',
                    'specs' => 'Pemantik piezo normal & adapter tabung gas hi-cook',
                    'condition_before' => 'Berfungsi Normal',
                    'price_per_day' => 20000,
                    'qty' => 1,
                    'subtotal' => 40000,
                    'icon' => 'stove',
                ],
                [
                    'id' => 4,
                    'unit_code' => 'LMP-LED-09',
                    'name' => 'Lampu Tenda LED Camping Rechargeable 3000mAh',
                    'category' => 'Penerangan & Kelistrikan',
                    'variant' => 'Warm White 300 Lumens',
                    'specs' => 'Baterai terisi 100% & hook gantungan fleksibel',
                    'condition_before' => 'Berfungsi Normal',
                    'price_per_day' => 10000,
                    'qty' => 1,
                    'subtotal' => 20000,
                    'icon' => 'lamp',
                ],
            ],

            // Timeline status untuk visual tracking
            'timeline' => [
                [
                    'title' => 'Booking Dibuat',
                    'time' => '02 Okt 2026, 19:30 WIB',
                    'description' => 'Pesanan berhasil dibuat melalui website Tendaku.',
                    'completed' => true,
                ],
                [
                    'title' => 'Pembayaran Lunas',
                    'time' => '02 Okt 2026, 19:35 WIB',
                    'description' => 'Pembayaran Rp 410.000 via QRIS Midtrans berhasil diverifikasi.',
                    'completed' => true,
                ],
                [
                    'title' => 'Pengambilan Unit & Serah KTP Fisik',
                    'time' => '03 Okt 2026, 09:15 WIB',
                    'description' => 'Unit diserahterimakan di workshop vendor. KTP fisik diserahkan sebagai jaminan.',
                    'completed' => true,
                ],
                [
                    'title' => 'Masa Sewa Berjalan',
                    'time' => '03 Okt - 05 Okt 2026',
                    'description' => 'Peralatan sedang digunakan pelanggan untuk trip camping.',
                    'active' => true,
                    'completed' => false,
                ],
                [
                    'title' => 'Pengembalian Unit & Serah Terima KTP',
                    'time' => 'Maksimal 05 Okt 2026, 17:00 WIB',
                    'description' => 'Inspeksi kondisi barang retur dan penyerahan kembali KTP fisik asli kepada pelanggan.',
                    'completed' => false,
                ],
            ],
        ];
    }

    /**
     * Halaman Status Booking Customer.
     */
    public function status(Request $request, ?string $code = null): View
    {
        $booking = $this->getDummyBooking($code);

        return view('customer.booking.status', compact('booking'));
    }

    /**
     * Halaman Digital Collateral Receipt (Tanda Terima KTP Digital).
     */
    public function receipt(Request $request, ?string $code = null): View
    {
        $booking = $this->getDummyBooking($code);

        return view('customer.booking.receipt', compact('booking'));
    }
}
