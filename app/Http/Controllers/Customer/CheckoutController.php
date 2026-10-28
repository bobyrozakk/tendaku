<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCheckoutRequest;
use App\Models\MasterItem;
use App\Models\Rental;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutSummaryBuilder;
use App\Services\GuestRentalAccess;
use App\Services\MidtransService;
use App\Services\PaymentService;
use App\Services\RentalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    private const CART_SESSION_KEY = 'booking_cart';
    private const PAYMENT_TIMEOUT_MINUTES = 60;

    public function __construct(
        private readonly CheckoutSummaryBuilder $summary,
        private readonly GuestRentalAccess $guestAccess,
    ) {}

    /**
     * Halaman checkout.
     * Data keranjang dibaca dari session('booking_cart').
     */
    public function index(Request $request): View|RedirectResponse
    {
        $cart = $this->cart();

        if ($cart === null) {
            return redirect()
                ->route('customer.catalog.index')
                ->with(
                    'warning',
                    'Silakan pilih alat camping yang ingin disewa terlebih dahulu.'
                );
        }

        $booking = $this->summary->build(
            $cart,
            Auth::user(),
            $request->query('pickup_method')
        );

        $isLoggedIn = Auth::check();

        return view(
            'customer.checkout.index',
            compact('booking', 'isLoggedIn')
        );
    }

    /**
     * Membuat rental dan pembayaran Midtrans Snap.
     * Nilai transaksi harus dihitung dan divalidasi di server.
     */
    public function store(
        StoreCheckoutRequest $request,
        RentalService $rentalService,
        PaymentService $paymentService,
        MidtransService $midtrans,
    ): JsonResponse|RedirectResponse {
        $cart = $this->cart();

        if ($cart === null) {
            return $this->failure(
                $request,
                'Sesi booking tidak ditemukan. Mulai ulang dari halaman produk.',
                422,
                'customer.catalog.index',
            );
        }

        $pickupMethod = $request->validated('pickup_method');

        // Pertahankan aturan dari versi anggota tim:
        // delivery hanya diperbolehkan bagi pengguna yang login.
        if (
            $pickupMethod === CheckoutSummaryBuilder::PICKUP_DELIVERY
            && ! Auth::check()
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Layanan delivery mengharuskan Anda masuk atau mendaftar akun terlebih dahulu.',
                    'redirect_url' => route('login'),
                ], 403);
            }

            return redirect()
                ->route('login')
                ->with(
                    'warning',
                    'Layanan pengantaran (delivery) mengharuskan Anda memiliki akun terverifikasi. Silakan masuk atau daftar akun terlebih dahulu.'
                );
        }

        try {
            $masterItem = MasterItem::with('vendor')
                ->findOrFail($cart['master_item_id']);

            $bookingData = $this->bookingData(
                $request,
                $cart,
                $masterItem
            );

            $rental = $rentalService->createBooking($bookingData);

            $rental = $this->applyPreviewBookingCode(
                $rental,
                $request->input('booking_code')
            );

            // Hanya satu mekanisme pembuatan payment.
            // Jangan membuat payment kedua secara manual.
            $paymentOption = $request->validated('payment_option') ?: 'full';
            $payment = $paymentService->createOnlinePayment(
                $rental,
                $paymentOption,
                1,
                $this->paymentChannel(
                    $request->validated('payment_channel')
                ),
            );

            $snapToken = $midtrans->createTransaction($payment);

            // Pertahankan akses status booking untuk guest.
            if ($request->user() === null) {
                $this->guestAccess->grant($rental);
            }

            // Keranjang dibersihkan setelah checkout berhasil diproses
            app(CartService::class)->clear(Auth::user());

            if ($request->expectsJson()) {
                return response()->json([
                    'snap_token' => $snapToken,
                    'booking_code' => $rental->booking_code,
                    'redirect_url' => $this->statusUrl($request, $rental),
                    'payment_type' => $payment->payment_type,
                    'payment_amount' => (int) $payment->amount,
                ]);
            }

            $methodLabel = $pickupMethod === CheckoutSummaryBuilder::PICKUP_DELIVERY
                ? 'Diantar ke Lokasi (Kurir Vendor)'
                : 'Ambil di Tempat (Self-Pickup)';

            $isGuest = $request->user() === null;
            $successMessage = $isGuest
                ? "Booking #{$rental->booking_code} ({$methodLabel}) berhasil dibuat. Tunjukkan kode booking saat pengambilan; pembayaran diselesaikan di outlet vendor."
                : "Booking #{$rental->booking_code} ({$methodLabel}) berhasil dibuat. Selesaikan pembayaran sebelum batas waktu.";

            return redirect()->route('booking.status', $rental->booking_code)
                ->with('success', $successMessage);
        } catch (InsufficientStockException $e) {
            return $this->failure(
                $request,
                'Stok tidak tersedia: '.$e->getMessage()
            );
        } catch (\InvalidArgumentException $e) {
            return $this->failure($request, $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return $this->failure(
                $request,
                'Terjadi kesalahan saat memproses pembayaran. Silakan coba lagi.',
                500,
            );
        }
    }

    /**
     * Menyusun data yang dibutuhkan RentalService.
     */
    private function bookingData(
        StoreCheckoutRequest $request,
        array $cart,
        MasterItem $masterItem,
    ): array {
        $user = $request->user();
        $pickupMethod = $request->validated('pickup_method');
        $pickupDate = $request->validated('pickup_date') ?: ($cart['pickup_date'] ?? null);
        $returnDate = $request->validated('return_date') ?: ($cart['return_date'] ?? null);

        $requestedItems = $request->input('items');
        $qty = 1;
        if (is_array($requestedItems) && isset($requestedItems[0]['qty'])) {
            $qty = max(1, (int) $requestedItems[0]['qty']);
        } else {
            $qty = $this->summary->quantityOf($cart);
        }
        $data = [
            'vendor_id' => $masterItem->vendor_id,
            'pickup_date' => $pickupDate,
            'pickup_time' => $masterItem->vendor->pickup_time ?: config('rental.default_pickup_time', '09:00'),
            'return_date' => $returnDate,
            'return_time' => $masterItem->vendor->return_time ?: config('rental.default_return_time', '09:00'),
            'expires_in_minutes' => self::PAYMENT_TIMEOUT_MINUTES,
            'pickup_method' => $pickupMethod,
            'delivery_fee' => $this->summary->deliveryFee(
                $masterItem->vendor,
                $pickupMethod,
                $user !== null
            ),
            'notes' => $request->validated('notes'),
            'items' => [
                [
                    'master_item_id' => $masterItem->id,
                    'qty' => $qty,
                ],
            ],
        ];

        if ($user === null) {
            return $data + [
                'guest_name' => $request->validated('guest_name'),
                'guest_phone' => $request->validated('guest_phone'),
                'guest_id_number' => $request->validated('guest_id_number') ?? '',
            ];
        }

        $data['user_id'] = $user->id;

        if (
            $pickupMethod === CheckoutSummaryBuilder::PICKUP_DELIVERY
        ) {
            $data['delivery_address'] =
                $request->validated('delivery_address')
                ?: ($user->address ?? '');
        }

        return $data;
    }

    /**
     * Kanal pembayaran yang didukung halaman checkout.
     */
    protected function paymentChannel(?string $channel): string
    {
        return in_array(
            $channel,
            ['qris', 'bca_va', 'mandiri_va', 'bri_va'],
            true
        ) ? $channel : 'qris';
    }

    /**
     * Metode pembayaran untuk ditampilkan di halaman checkout.
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
     * URL halaman status booking.
     * Guest membawa nomor telepon sebagai bukti akses sesuai
     * mekanisme status yang sudah digunakan aplikasi.
     */
    private function statusUrl(Request $request, Rental $rental): string
    {
        $url = route(
            'booking.status',
            $rental->booking_code,
            false
        );

        if (
            $request->user() === null
            && ! empty($rental->guest_phone)
        ) {
            $url .= '?phone='.urlencode($rental->guest_phone);
        }

        return $url;
    }

    /**
     * Membaca keranjang booking dari CartService (session atau database user).
     */
    private function cart(): ?array
    {
        return app(CartService::class)->get(Auth::user());
    }

    /**
     * JSON untuk AJAX, redirect untuk form biasa.
     */
    private function failure(
        Request $request,
        string $message,
        int $status = 422,
        ?string $redirectRoute = null,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], $status);
        }

        return $redirectRoute
            ? redirect()
                ->route($redirectRoute)
                ->with('warning', $message)
            : redirect()
                ->back()
                ->with('error', $message);
    }

    /**
     * Terapkan kode booking yang sebelumnya sudah ditampilkan di pratinjau checkout.
     */
    protected function applyPreviewBookingCode(Rental $rental, ?string $previewCode): Rental
    {
        if (! is_string($previewCode) || $previewCode === '' || $previewCode === $rental->booking_code) {
            return $rental;
        }

        $exists = Rental::withoutGlobalScopes()
            ->where('booking_code', $previewCode)
            ->where('id', '!=', $rental->id)
            ->exists();

        if ($exists) {
            return $rental;
        }

        $rental->booking_code = $previewCode;
        $rental->save();

        return $rental;
    }
}
