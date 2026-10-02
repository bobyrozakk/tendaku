<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * Halaman checkout booking untuk Customer (Role 3).
     */
    public function index(Request $request): View
    {
        $isLoggedIn = Auth::check();
        $user = Auth::user();

        // Aturan Bisnis TENDAKU:
        // - User tanpa login (Guest) HANYA BISA ambil barang di tempat (self_pickup).
        // - User yang login/register BISA memilih ambil di tempat (self_pickup) atau diantar (delivery).
        $requestedMethod = $request->query('pickup_method');
        if (! $isLoggedIn) {
            $pickupMethod = 'self_pickup';
        } else {
            $pickupMethod = in_array($requestedMethod, ['self_pickup', 'delivery']) ? $requestedMethod : 'delivery';
        }

        $subtotal = 310000;
        $deposit = 100000;
        $deliveryFee = $pickupMethod === 'delivery' ? 25000 : 0;
        $discount = 0;
        $grandTotal = $subtotal + $deposit + $deliveryFee - $discount;

        $booking = [
            'booking_code' => 'TDK-2026-B8921',
            'status' => 'pending',
            'status_label' => 'Menunggu Pembayaran',
            'created_at' => '02 Oktober 2026, 19:30 WIB',
            'expires_at' => '03 Oktober 2026, 07:30 WIB',
            'is_guest' => ! $isLoggedIn,
            'vendor' => [
                'name' => 'Mahameru Outdoor Equipment & Rental',
                'badge' => 'Official Partner',
                'city' => 'Kota Batu, Jawa Timur',
                'address' => 'Jl. Oro-Oro Ombo No. 42, Temas, Kec. Batu, Kota Batu',
                'phone' => '0812-9876-5432',
                'rating' => '4.9',
                'total_reviews' => 148,
                'pickup_location' => 'Store Offline Mahameru Outdoor (Kav. B4)',
                'operational_hours' => 'Senin - Minggu, 07:00 - 21:00 WIB',
            ],
            'customer' => [
                'name' => $isLoggedIn ? ($user->name ?? 'Pelanggan Terdaftar') : 'Tamu (Guest)',
                'phone' => $isLoggedIn ? ($user->phone ?? '0857-8901-2345') : '',
                'email' => $isLoggedIn ? ($user->email ?? 'budi.santoso@example.com') : '',
            ],
            'pickup_date' => 'Sabtu, 03 Oktober 2026',
            'pickup_time' => '09:00 WIB',
            'return_date' => 'Senin, 05 Oktober 2026',
            'return_time' => '17:00 WIB',
            'rental_days' => 2,
            'pickup_method' => $pickupMethod,
            'delivery_address' => 'Jl. Candi Mendut No. 18, Kel. Mojolangu, Kec. Lowokwaru, Kota Malang, Jawa Timur 65141',
            'delivery_notes' => 'Hubungi via WhatsApp 30 menit sebelum pengantaran ke lokasi.',
            'items' => [
                [
                    'id' => 1,
                    'name' => 'Tenda Dome Arpenaz 4.1 Fresh & Black',
                    'category' => 'Tenda Camping',
                    'variant' => 'Kapasitas 4 Orang',
                    'specs' => 'Double layer waterproof 2000mm, frame alloy kokoh & ventilasi sejuk',
                    'price_per_day' => 75000,
                    'deposit_per_unit' => 50000,
                    'qty' => 1,
                    'subtotal' => 150000,
                    'icon' => 'tent',
                ],
                [
                    'id' => 2,
                    'name' => 'Sleeping Bag Bulu Angsa Ultralight 800FP',
                    'category' => 'Perlengkapan Tidur',
                    'variant' => 'Comfort Limit -5°C',
                    'specs' => 'Bulu angsa alami ultra-hangat & compression sack kedap air',
                    'price_per_day' => 25000,
                    'deposit_per_unit' => 15000,
                    'qty' => 2,
                    'subtotal' => 100000,
                    'icon' => 'sleeping_bag',
                ],
                [
                    'id' => 3,
                    'name' => 'Kompor Camping Portable Windproof + Adapter',
                    'category' => 'Peralatan Masak',
                    'variant' => 'Model Bunga Anti Angin',
                    'specs' => 'Pemantik piezo otomatis & adapter tabung gas hi-cook',
                    'price_per_day' => 20000,
                    'deposit_per_unit' => 10000,
                    'qty' => 1,
                    'subtotal' => 40000,
                    'icon' => 'stove',
                ],
                [
                    'id' => 4,
                    'name' => 'Lampu Tenda LED Camping Rechargeable 3000mAh',
                    'category' => 'Penerangan & Kelistrikan',
                    'variant' => 'Warm White 300 Lumens',
                    'specs' => 'Baterai tahan hingga 18 jam & hook gantung fleksibel anti air IPX4',
                    'price_per_day' => 10000,
                    'deposit_per_unit' => 10000,
                    'qty' => 1,
                    'subtotal' => 20000,
                    'icon' => 'lamp',
                ],
            ],
            'subtotal' => $subtotal,
            'deposit' => $deposit,
            'delivery_fee' => $deliveryFee,
            'discount' => $discount,
            'grand_total' => $grandTotal,
            'payment_methods' => [
                [
                    'id' => 'qris',
                    'name' => 'QRIS Realtime',
                    'desc' => 'GoPay, OVO, Dana, ShopeePay, BCA Mobile, & Livin Mandiri',
                    'badge' => 'Verifikasi Otomatis',
                ],
                [
                    'id' => 'bca_va',
                    'name' => 'BCA Virtual Account',
                    'desc' => 'Konfirmasi pembayaran instan 24 jam',
                    'badge' => 'Otomatis',
                ],
                [
                    'id' => 'mandiri_va',
                    'name' => 'Mandiri Virtual Account',
                    'desc' => 'Transfer via Livin by Mandiri / ATM',
                    'badge' => 'Otomatis',
                ],
                [
                    'id' => 'bri_va',
                    'name' => 'BRI Virtual Account (BRIVA)',
                    'desc' => 'Transfer via BRImo / ATM BRI',
                    'badge' => 'Otomatis',
                ],
            ],
        ];

        return view('customer.checkout.index', compact('booking', 'isLoggedIn'));
    }

    /**
     * Proses pemesanan/checkout rental.
     */
    public function store(Request $request): RedirectResponse
    {
        $pickupMethod = $request->input('pickup_method', 'self_pickup');

        // Validasi aturan: Opsi delivery hanya diizinkan bagi user yang telah login
        if ($pickupMethod === 'delivery' && ! Auth::check()) {
            return redirect()->route('login')->with('warning', 'Layanan pengantaran (delivery) mengharuskan Anda memiliki akun terverifikasi. Silakan masuk atau daftar akun terlebih dahulu.');
        }

        $methodLabel = $pickupMethod === 'delivery' ? 'Diantar ke Lokasi (Kurir Vendor)' : 'Ambil di Tempat (Self-Pickup)';

        return redirect()->route('booking.status', 'TDK-2026-B8921')->with('success', "Pembayaran booking #TDK-2026-B8921 ({$methodLabel}) berhasil diproses! Vendor Mahameru Outdoor telah menerima konfirmasi dan mempersiapkan alat sewa Anda.");
    }
}
