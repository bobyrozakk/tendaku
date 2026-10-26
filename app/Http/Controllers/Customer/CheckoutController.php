<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\MasterItem;
use App\Models\Rental;
use App\Models\User;
use App\Services\CartService;
use App\Services\PhoneNumberNormalizer;
use App\Services\RentalService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * Biaya pengantaran (delivery) dalam Rupiah.
     */
    public const DELIVERY_FEE = 25000;

    /**
     * Masa berlaku pembayaran (menit) sebelum booking kedaluwarsa (maksimal 5 menit).
     */
    public const PAYMENT_TIMEOUT_MINUTES = 5;

    /**
     * Masa berlaku pembayaran (jam) sebelum booking kedaluwarsa (kompatibilitas backward).
     */
    public const PAYMENT_WINDOW_HOURS = 12;

    /**
     * Nama bulan dalam Bahasa Indonesia untuk tampilan tanggal.
     *
     * @var array<int, string>
     */
    protected const MONTHS = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    /**
     * Nama hari dalam Bahasa Indonesia untuk tampilan tanggal.
     *
     * @var array<int, string>
     */
    protected const DAYS = [
        0 => 'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu',
    ];

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
            $pickupMethod = in_array($requestedMethod, ['self_pickup', 'delivery'], true) ? $requestedMethod : 'delivery';
        }

        $cart = app(CartService::class)->get($user);

        $booking = is_array($cart) && ! empty($cart['master_item_id'])
            ? $this->bookingFromCart($cart, $pickupMethod, $isLoggedIn, $user)
            : $this->demoBooking($pickupMethod, $isLoggedIn, $user);

        return view('customer.checkout.index', compact('booking', 'isLoggedIn'));
    }

    /**
     * Proses pemesanan/checkout rental & simulasi pembayaran Midtrans.
     */
    public function store(Request $request, RentalService $rentalService): RedirectResponse
    {
        $pickupMethod = $request->input('pickup_method', 'self_pickup');
        $paymentChannel = $this->paymentChannel($request->input('payment_channel'));

        // Validasi aturan: Opsi delivery hanya diizinkan bagi user yang telah login
        if ($pickupMethod === 'delivery' && ! Auth::check()) {
            return redirect()->route('login')->with('warning', 'Layanan pengantaran (delivery) mengharuskan Anda memiliki akun terverifikasi. Silakan masuk atau daftar akun terlebih dahulu.');
        }

        $cart = app(CartService::class)->get(Auth::user());

        if (! is_array($cart) || empty($cart['master_item_id'])) {
            return redirect()->route('customer.catalog.index')
                ->with('warning', 'Keranjang sewa masih kosong. Silakan pilih peralatan di katalog terlebih dahulu.');
        }

        $isGuest = ! Auth::check();

        $validated = $request->validate([
            'guest_name' => [$isGuest ? 'required' : 'nullable', 'string', 'max:255'],
            'guest_phone' => [$isGuest ? 'required' : 'nullable', 'string', 'max:32'],
            'guest_id_number' => [$isGuest ? 'required' : 'nullable', 'string', 'max:32'],
        ], [
            'guest_name.required' => 'Nama lengkap wajib diisi untuk pemesanan tanpa akun.',
            'guest_phone.required' => 'Nomor WhatsApp wajib diisi agar pengingat pengembalian barang dapat dikirim.',
            'guest_id_number.required' => 'NIK wajib diisi untuk pemesanan tanpa akun.',
        ]);

        if ($isGuest && PhoneNumberNormalizer::normalize($validated['guest_phone']) === null) {
            throw ValidationException::withMessages([
                'guest_phone' => 'Nomor WhatsApp tidak valid. Gunakan format 08xxxxxxxxxx.',
            ]);
        }

        $deliveryAddress = $pickupMethod === 'delivery' ? (Auth::user()?->address) : null;

        if ($pickupMethod === 'delivery' && blank($deliveryAddress)) {
            return redirect()->route('checkout.index')
                ->with('warning', 'Alamat pengantaran belum lengkap di profil Anda. Lengkapi alamat pada halaman profil terlebih dahulu.');
        }

        $rental = $rentalService->createBooking([
            'vendor_id' => (int) $cart['vendor_id'],
            'user_id' => Auth::id(),
            'guest_name' => $isGuest ? $validated['guest_name'] : null,
            'guest_phone' => $isGuest ? $validated['guest_phone'] : null,
            'guest_id_number' => $isGuest ? $validated['guest_id_number'] : null,
            'pickup_date' => $cart['pickup_date'] ?? now()->addDay()->toDateString(),
            'pickup_time' => $cart['pickup_time'] ?? '09:00',
            'return_date' => $cart['return_date'] ?? now()->addDays(2)->toDateString(),
            'return_time' => $cart['return_time'] ?? '09:00',
            'rental_days' => isset($cart['total_days']) ? (int) $cart['total_days'] : null,
            'pickup_method' => $pickupMethod,
            'delivery_address' => $deliveryAddress,
            'delivery_fee' => $pickupMethod === 'delivery' ? self::DELIVERY_FEE : 0,
            'discount_amount' => 0,
            'expires_in_minutes' => self::PAYMENT_TIMEOUT_MINUTES,
            'items' => [
                [
                    'master_item_id' => (int) $cart['master_item_id'],
                    'quantity' => (int) ($cart['qty'] ?? 1),
                ],
            ],
        ]);

        $rental = $this->applyPreviewBookingCode($rental, $request->input('booking_code'));

        if (! $isGuest) {
            $rental->payments()->create([
                'vendor_id' => $rental->vendor_id,
                'payment_type' => 'settlement',
                'payment_method' => 'midtrans_snap',
                'payment_channel' => $paymentChannel,
                'amount' => $rental->grand_total,
                'status' => 'pending',
            ]);
        }

        app(CartService::class)->clear(Auth::user());

        $methodLabel = $pickupMethod === 'delivery' ? 'Diantar ke Lokasi (Kurir Vendor)' : 'Ambil di Tempat (Self-Pickup)';

        $channelNames = [
            'qris' => 'QRIS Realtime',
            'bca_va' => 'BCA Virtual Account',
            'mandiri_va' => 'Mandiri Bill Payment',
            'bri_va' => 'BRI Virtual Account (BRIVA)',
        ];
        $channelLabel = $channelNames[$paymentChannel] ?? 'Midtrans Sandbox';

        $successMessage = $isGuest
            ? "Booking #{$rental->booking_code} ({$methodLabel}) berhasil dibuat. Tunjukkan kode booking saat pengambilan; pembayaran diselesaikan di outlet vendor."
            : "Booking #{$rental->booking_code} ({$methodLabel}) berhasil dibuat. Selesaikan pembayaran via {$channelLabel} sebelum batas waktu.";

        return redirect()->route('booking.status', $rental->booking_code)
            ->with('success', $successMessage);
    }

    /**
     * Susun data booking dari keranjang session (`booking_cart`).
     *
     * @param  array<string, mixed>  $cart
     * @return array<string, mixed>
     */
    protected function bookingFromCart(array $cart, string $pickupMethod, bool $isLoggedIn, ?User $user): array
    {
        $item = MasterItem::query()
            ->with(['vendor', 'category'])
            ->find((int) $cart['master_item_id']);

        $pickupTime = $cart['pickup_time'] ?? '09:00';
        $returnTime = $cart['return_time'] ?? '09:00';

        $pickupDateStr = $cart['pickup_date'] ?? now()->addDay()->toDateString();
        $returnDateStr = $cart['return_date'] ?? now()->addDays(2)->toDateString();

        $pickupDateTime = Carbon::parse($pickupDateStr.' '.$pickupTime);
        $returnDateTime = Carbon::parse($returnDateStr.' '.$returnTime);

        $hours = $pickupDateTime->floatDiffInHours($returnDateTime, false);
        $calculatedDays = max(1, (int) ceil($hours / 24));
        $rentalDays = max(1, (int) ($cart['total_days'] ?? $calculatedDays));
        $quantity = max(1, (int) ($cart['qty'] ?? 1));
        $dailyRate = (int) ($item?->daily_rate ?? $cart['daily_rate'] ?? 0);
        $depositPerUnit = (int) ($item?->deposit_amount ?? 0);

        $subtotal = (int) ($cart['total_price'] ?? ($dailyRate * $rentalDays * $quantity));
        $deposit = $depositPerUnit * $quantity;
        $deliveryFee = $pickupMethod === 'delivery' ? self::DELIVERY_FEE : 0;
        $discount = 0;
        $grandTotal = $subtotal + $deposit + $deliveryFee - $discount;

        $now = Carbon::now();
        $expiresAt = $now->copy()->addMinutes(self::PAYMENT_TIMEOUT_MINUTES);

        return [
            'booking_code' => app(RentalService::class)->generateBookingCode(),
            'status' => 'pending',
            'status_label' => 'Menunggu Pembayaran',
            'created_at' => $this->formatTanggalWaktu($now),
            'expires_at' => $this->formatTanggalWaktu($expiresAt),
            'is_guest' => ! $isLoggedIn,
            'vendor' => [
                'name' => $vendor?->name ?? 'Vendor TENDAKU',
                'badge' => 'Official Partner',
                'city' => $vendor?->city ?? '-',
                'address' => $vendor?->address ?? '-',
                'phone' => $vendor?->phone ?? '-',
                'rating' => '4.9',
                'total_reviews' => 0,
                'pickup_location' => $vendor?->address ?? 'Ambil langsung di outlet vendor',
                'operational_hours' => 'Senin - Minggu, 07:00 - 21:00 WIB',
            ],
            'customer' => [
                'name' => $isLoggedIn ? ($user->name ?? 'Pelanggan Terdaftar') : 'Tamu (Guest)',
                'phone' => $isLoggedIn ? ($user->phone ?? '') : '',
                'email' => $isLoggedIn ? ($user->email ?? '') : '',
            ],
            'pickup_date' => $this->formatTanggal(Carbon::parse($pickupDateStr)),
            'pickup_time' => $pickupTime.' WIB',
            'return_date' => $this->formatTanggal(Carbon::parse($returnDateStr)),
            'return_time' => $returnTime.' WIB',
            'rental_days' => $rentalDays,
            'pickup_method' => $pickupMethod,
            'delivery_address' => $pickupMethod === 'delivery' ? ($user?->address ?? '') : '',
            'delivery_notes' => 'Hubungi via WhatsApp 30 menit sebelum pengantaran ke lokasi.',
            'items' => [
                [
                    'id' => (int) $cart['master_item_id'],
                    'name' => $item?->name ?? ($cart['product_name'] ?? 'Peralatan Camping'),
                    'category' => $item?->category?->name ?? ($cart['category_name'] ?? 'Camping'),
                    'variant' => '-',
                    'specs' => $item?->description ?? '-',
                    'price_per_day' => $dailyRate,
                    'deposit_per_unit' => $depositPerUnit,
                    'qty' => $quantity,
                    'subtotal' => $dailyRate * $rentalDays * $quantity,
                    'icon' => 'tent',
                ],
            ],
            'subtotal' => $subtotal,
            'deposit' => $deposit,
            'delivery_fee' => $deliveryFee,
            'discount' => $discount,
            'grand_total' => $grandTotal,
            'payment_methods' => $this->paymentMethods(),
        ];
    }

    /**
     * Data booking contoh untuk halaman checkout yang dibuka tanpa keranjang.
     *
     * @return array<string, mixed>
     */
    protected function demoBooking(string $pickupMethod, bool $isLoggedIn, ?User $user): array
    {
        $subtotal = 310000;
        $deposit = 100000;
        $deliveryFee = $pickupMethod === 'delivery' ? self::DELIVERY_FEE : 0;
        $discount = 0;
        $grandTotal = $subtotal + $deposit + $deliveryFee - $discount;

        return [
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
                'phone' => $isLoggedIn ? ($user->phone ?? '') : '',
                'email' => $isLoggedIn ? ($user->email ?? '') : '',
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
                    'specs' => 'Bulu angsa alami ultra-warm & compression sack kedap air',
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
                    'specs' => 'Pemantik piezo otomatis & adapter tangki hi-cook',
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
                    'variant' => 'Warm White 3000 Lumens',
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
            'payment_methods' => $this->paymentMethods(),
        ];
    }

    /**
     * Kanal pembayaran yang didukung halaman checkout.
     */
    protected function paymentChannel(?string $channel): string
    {
        return in_array($channel, ['qris', 'bca_va', 'mandiri_va', 'bri_va'], true)
            ? $channel
            : 'qris';
    }

    /**
     * Metode pembayaran yang ditampilkan di halaman checkout.
     *
     * @return array<int, array<string, string>>
     */
    protected function paymentMethods(): array
    {
        return [
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
                'name' => 'Mandiri Bill Payment',
                'desc' => 'Transfer via Livin by Mandiri / ATM',
                'badge' => 'Otomatis',
            ],
            [
                'id' => 'bri_va',
                'name' => 'BRI Virtual Account (BRIVA)',
                'desc' => 'Transfer via BRImo / ATM',
                'badge' => 'Otomatis',
            ],
        ];
    }

    /**
     * Selaraskan kode booking yang tampil di halaman dengan rental yang dibuat.
     *
     * Kode pratinjau dibuat oleh `RentalService::generateBookingCode()` sehingga
     * tetap mengikuti format & keunikan yang sama. Bila kode tersebut sudah
     * dipakai rental lain, kode asli dari `createBooking()` yang dipertahankan.
     */
    protected function applyPreviewBookingCode(Rental $rental, ?string $previewCode): Rental
    {
        if (! is_string($previewCode) || $previewCode === '' || $previewCode === $rental->booking_code) {
            return $rental;
        }

        if (preg_match('/^TDK-\d{4}-[A-Z0-9]{5}$/', $previewCode) !== 1) {
            return $rental;
        }

        $taken = Rental::withoutGlobalScopes()->where('booking_code', $previewCode)->exists();

        if ($taken) {
            return $rental;
        }

        $rental->update(['booking_code' => $previewCode]);

        return $rental->refresh();
    }

    /**
     * Format tanggal Bahasa Indonesia (contoh: Sabtu, 03 Oktober 2026).
     */
    protected function formatTanggal(Carbon $date): string
    {
        return self::DAYS[$date->dayOfWeek].', '.$date->day.' '.self::MONTHS[$date->month].' '.$date->year;
    }

    /**
     * Format tanggal + waktu Bahasa Indonesia (contoh: 02 Oktober 2026, 19:30 WIB).
     */
    protected function formatTanggalWaktu(Carbon $date): string
    {
        return $date->day.' '.self::MONTHS[$date->month].' '.$date->year.', '.$date->format('H:i').' WIB';
    }
}
