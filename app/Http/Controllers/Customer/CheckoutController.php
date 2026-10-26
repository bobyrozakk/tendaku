<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCheckoutRequest;
use App\Models\MasterItem;
use App\Services\CheckoutSummaryBuilder;
use App\Services\GuestRentalAccess;
use App\Services\MidtransService;
use App\Services\PaymentService;
use App\Services\RentalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    private const CART_SESSION_KEY = 'booking_cart';

    public function __construct(
        private readonly CheckoutSummaryBuilder $summary,
        private readonly GuestRentalAccess $guestAccess,
    ) {}

    /**
     * Halaman checkout. Data keranjang dibaca dari session('booking_cart').
     */
    public function index(Request $request): View|RedirectResponse
    {
        $cart = $this->cart();

        if ($cart === null) {
            return redirect()
                ->route('customer.catalog.index')
                ->with('warning', 'Silakan pilih alat camping yang ingin disewa terlebih dahulu.');
        }

        $booking = $this->summary->build($cart, Auth::user(), $request->query('pickup_method'));
        $isLoggedIn = Auth::check();

        return view('customer.checkout.index', compact('booking', 'isLoggedIn'));
    }

    /**
     * Buat rental + tagihan Midtrans, lalu kembalikan Snap token.
     * Nominal selalu dihitung di server (§0); order_id berawalan TDK- (§0, §7.2).
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

        try {
            $masterItem = MasterItem::with('vendor')->findOrFail($cart['master_item_id']);

            $rental = $rentalService->createBooking($this->bookingData($request, $cart, $masterItem));

            $payment = $paymentService->createOnlinePayment(
                $rental,
                $request->validated('payment_option'),
                1,
                $request->validated('payment_channel'),
            );

            $snapToken = $midtrans->createTransaction($payment);

            if ($request->user() === null) {
                $this->guestAccess->grant($rental);
            }

            session()->forget(self::CART_SESSION_KEY);

            $redirectUrl = route('booking.status', $rental->booking_code, false);
            if ($request->user() === null && ! empty($rental->guest_phone)) {
                $redirectUrl .= '?phone='.urlencode($rental->guest_phone);
            }

            return response()->json([
                'snap_token' => $snapToken,
                'booking_code' => $rental->booking_code,
                'redirect_url' => $redirectUrl,
                'payment_type' => $payment->payment_type,
                'payment_amount' => (int) $payment->amount,
            ]);
        } catch (InsufficientStockException $e) {
            return $this->failure($request, 'Stok tidak tersedia: '.$e->getMessage());
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
     * Susun data yang dibutuhkan RentalService.
     */
    private function bookingData(StoreCheckoutRequest $request, array $cart, MasterItem $masterItem): array
    {
        $user = $request->user();
        $pickupMethod = $request->validated('pickup_method');

        $data = [
            'vendor_id' => $masterItem->vendor_id,
            'pickup_date' => $request->validated('pickup_date') ?: $cart['pickup_date'],
            'return_date' => $request->validated('return_date') ?: $cart['return_date'],
            'pickup_method' => $pickupMethod,
            'delivery_fee' => $this->summary->deliveryFee($masterItem->vendor, $pickupMethod, $user !== null),
            'notes' => $request->validated('notes'),
            'items' => [
                ['master_item_id' => $masterItem->id, 'qty' => $this->summary->quantityOf($cart)],
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

        if ($pickupMethod === CheckoutSummaryBuilder::PICKUP_DELIVERY) {
            $data['delivery_address'] = $request->validated('delivery_address') ?: ($user->address ?? '');
        }

        return $data;
    }

    /**
     * Keranjang booking dari session, atau null kalau kosong/tidak valid.
     */
    private function cart(): ?array
    {
        $cart = session(self::CART_SESSION_KEY);

        return (empty($cart) || empty($cart['master_item_id'])) ? null : $cart;
    }

    /**
     * Respons error yang sesuai: JSON untuk AJAX, redirect untuk form biasa.
     */
    private function failure(
        Request $request,
        string $message,
        int $status = 422,
        ?string $redirectRoute = null,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        return $redirectRoute
            ? redirect()->route($redirectRoute)->with('warning', $message)
            : redirect()->back()->with('error', $message);
    }
}
