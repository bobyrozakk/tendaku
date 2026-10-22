<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\MasterItem;
use App\Services\MidtransService;
use App\Services\RentalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * Halaman checkout booking untuk Customer (Role 3).
     *
     * Membaca data dari session('booking_cart') yang dikirim oleh ProductBooking Livewire.
     * Redirect ke katalog jika session kosong (user langsung buka URL tanpa melalui booking form).
     */
    public function index(Request $request): View|RedirectResponse
    {
        $cart = session('booking_cart');

        if (empty($cart) || empty($cart['master_item_id'])) {
            return redirect()
                ->route('customer.catalog.index')
                ->with('warning', 'Silakan pilih alat camping yang ingin disewa terlebih dahulu.');
        }

        $isLoggedIn = Auth::check();
        $user = Auth::user();

        /** @var MasterItem $masterItem */
        $masterItem = MasterItem::with(['vendor', 'category'])->findOrFail($cart['master_item_id']);
        $vendor = $masterItem->vendor;

        $requestedMethod = $request->query('pickup_method');
        if (! $isLoggedIn) {
            $pickupMethod = 'self_pickup';
        } else {
            $pickupMethod = in_array($requestedMethod, ['self_pickup', 'delivery']) ? $requestedMethod : 'self_pickup';
        }

        $quantity = (int) ($cart['quantity'] ?? $cart['qty'] ?? 1);
        $rentalDays = (int) ($cart['total_days'] ?? 1);
        $dailyRate = (int) $masterItem->daily_rate;
        $depositPerUnit = (int) ($masterItem->deposit_amount ?? 0);

        $itemSubtotal = $dailyRate * $quantity * $rentalDays;
        $totalDeposit = $depositPerUnit * $quantity;
        $deliveryFee = ($isLoggedIn && $pickupMethod === 'delivery') ? 25000 : 0;
        $discount = 0;
        $grandTotal = $itemSubtotal + $totalDeposit + $deliveryFee - $discount;

        // Opsi DP: 30% dari grand total
        $dpPercentage = 30;
        $dpAmount = (int) ceil($grandTotal * $dpPercentage / 100);

        $pickupDate = Carbon::parse($cart['pickup_date']);
        $returnDate = Carbon::parse($cart['return_date']);

        $booking = [
            'booking_code' => 'TDK-PENDING',
            'status' => 'pending',
            'status_label' => 'Menunggu Pembayaran',
            'created_at' => now()->format('d F Y, H:i').' WIB',
            'expires_at' => now()->addHours(12)->format('d F Y, H:i').' WIB',
            'is_guest' => ! $isLoggedIn,
            'vendor' => [
                'id' => $vendor?->id,
                'name' => $vendor?->name ?? 'Vendor',
                'badge' => 'Official Partner',
                'city' => $vendor?->city ?? 'Malang, Jawa Timur',
                'address' => $vendor?->address ?? '-',
                'phone' => $vendor?->phone ?? '-',
                'rating' => '4.9',
                'total_reviews' => 0,
                'pickup_location' => 'Store Offline '.($vendor?->name ?? 'Vendor'),
                'operational_hours' => 'Senin - Minggu, 08:00 - 20:00 WIB',
            ],
            'customer' => [
                'name' => $isLoggedIn ? ($user->name ?? 'Pelanggan Terdaftar') : 'Tamu (Guest)',
                'phone' => $isLoggedIn ? ($user->phone ?? '') : '',
                'email' => $isLoggedIn ? ($user->email ?? '') : '',
            ],
            'pickup_date' => $pickupDate->translatedFormat('l, d F Y'),
            'pickup_time' => '09:00 WIB',
            'return_date' => $returnDate->translatedFormat('l, d F Y'),
            'return_time' => '17:00 WIB',
            'rental_days' => $rentalDays,
            'pickup_method' => $pickupMethod,
            'delivery_address' => $isLoggedIn ? ($user->address ?? '') : '',
            'delivery_notes' => '',
            'items' => [
                [
                    'id' => $masterItem->id,
                    'name' => $masterItem->name,
                    'category' => $masterItem->category?->name ?? 'Perlengkapan Camping',
                    'variant' => '',
                    'specs' => $masterItem->description ?? '',
                    'price_per_day' => $dailyRate,
                    'deposit_per_unit' => $depositPerUnit,
                    'qty' => $quantity,
                    'subtotal' => $itemSubtotal,
                    'icon' => 'tent',
                ],
            ],
            'subtotal' => $itemSubtotal,
            'deposit' => $totalDeposit,
            'delivery_fee' => $deliveryFee,
            'discount' => $discount,
            'grand_total' => $grandTotal,
            'dp_amount' => $dpAmount,
            'dp_percentage' => $dpPercentage,
            'payment_options' => ['full', 'dp'],
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
     * Proses pembuatan rental & generate Snap Token Midtrans.
     *
     * Mengembalikan JSON {snap_token, booking_code, redirect_url} agar frontend
     * dapat langsung memicu window.snap.pay(snapToken).
     * Jika user memilih DP, payment dibuat dengan payment_type='dp' dan nominal sesuai persentase.
     */
    public function store(Request $request, RentalService $rentalService, MidtransService $midtrans): JsonResponse|RedirectResponse
    {
        $cart = session('booking_cart');

        if (empty($cart) || empty($cart['master_item_id'])) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesi booking tidak ditemukan. Mulai ulang dari halaman produk.'], 422);
            }

            return redirect()->route('customer.catalog.index')
                ->with('warning', 'Sesi booking tidak ditemukan. Mulai ulang pemesanan dari halaman katalog.');
        }

        $pickupMethod = $request->input('pickup_method', 'self_pickup');
        $paymentOption = $request->input('payment_option', 'full'); // 'full' atau 'dp'

        if ($pickupMethod === 'delivery' && ! Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Layanan delivery memerlukan akun terdaftar.'], 422);
            }

            return redirect()->route('login')
                ->with('warning', 'Layanan pengantaran (delivery) mengharuskan Anda memiliki akun terverifikasi.');
        }

        $isGuest = ! Auth::check();
        $user = Auth::user();

        $request->validate([
            'pickup_method' => ['required', 'in:self_pickup,delivery'],
            'delivery_address' => [$pickupMethod === 'delivery' ? 'required' : 'nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'guest_name' => [$isGuest ? 'required' : 'nullable', 'string', 'max:150'],
            'guest_phone' => [$isGuest ? 'required' : 'nullable', 'string', 'max:20'],
        ]);

        /** @var MasterItem $masterItem */
        $masterItem = MasterItem::with('vendor')->findOrFail($cart['master_item_id']);

        $quantity = (int) ($cart['quantity'] ?? $cart['qty'] ?? 1);
        $rentalDays = (int) ($cart['total_days'] ?? 1);
        $dailyRate = (int) $masterItem->daily_rate;
        $depositPerUnit = (int) ($masterItem->deposit_amount ?? 0);

        $itemSubtotal = $dailyRate * $quantity * $rentalDays;
        $totalDeposit = $depositPerUnit * $quantity;
        $deliveryFee = (! $isGuest && $pickupMethod === 'delivery') ? 25000 : 0;
        $grandTotal = $itemSubtotal + $totalDeposit + $deliveryFee;

        // Kalkulasi nominal DP jika dipilih (30% dari grand total)
        $dpPercentage = 30;
        $dpAmount = (int) ceil($grandTotal * $dpPercentage / 100);
        $isDownPayment = ($paymentOption === 'dp') && ! $isGuest;
        $paymentAmount = $isDownPayment ? $dpAmount : $grandTotal;

        try {
            $bookingData = [
                'vendor_id' => $masterItem->vendor_id,
                'pickup_date' => $cart['pickup_date'],
                'return_date' => $cart['return_date'],
                'pickup_method' => $pickupMethod,
                'delivery_fee' => $deliveryFee,
                'notes' => $request->input('notes'),
                'items' => [
                    ['master_item_id' => $masterItem->id, 'qty' => $quantity],
                ],
            ];

            if (! $isGuest) {
                $bookingData['user_id'] = $user->id;

                if ($pickupMethod === 'delivery') {
                    $bookingData['delivery_address'] = $request->input('delivery_address', $user->address ?? '');
                }
            } else {
                $bookingData['guest_name'] = $request->input('guest_name', '');
                $bookingData['guest_phone'] = $request->input('guest_phone', '');
                $bookingData['guest_id_number'] = $request->input('guest_id_number', '');
            }

            $rental = $rentalService->createBooking($bookingData);

            // Ambil payment settlement yang baru saja dibuat oleh createBooking()
            $payment = $rental->payments()
                ->where('status', 'pending')
                ->where('payment_type', 'settlement')
                ->latest('id')
                ->firstOrFail();

            // Jika DP: ubah payment_type dan amount sesuai pilihan pelanggan
            if ($isDownPayment) {
                $payment->update([
                    'payment_type' => 'dp',
                    'amount' => $paymentAmount,
                ]);
                $payment->refresh();
            }

            $snapToken = $midtrans->createTransaction($payment);

            // Hapus session booking_cart setelah booking berhasil dibuat
            session()->forget('booking_cart');

            return response()->json([
                'snap_token' => $snapToken,
                'booking_code' => $rental->booking_code,
                'redirect_url' => route('booking.status', $rental->booking_code),
                'payment_type' => $payment->payment_type,
                'payment_amount' => (int) $payment->amount,
            ]);
        } catch (InsufficientStockException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Stok tidak tersedia: '.$e->getMessage()], 422);
            }

            return redirect()->back()->with('error', 'Stok tidak tersedia untuk tanggal yang dipilih. Silakan pilih tanggal lain.');
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Terjadi kesalahan saat memproses pembayaran. Silakan coba lagi.'], 500);
            }

            return redirect()->back()->with('error', 'Terjadi kesalahan teknis. Silakan coba beberapa saat lagi.');
        }
    }
}
