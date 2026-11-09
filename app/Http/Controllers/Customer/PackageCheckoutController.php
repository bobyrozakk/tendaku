<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\ShippingPreviewException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StorePackageCheckoutRequest;
use App\Models\CampingPackage;
use App\Services\Booking\CheckoutService;
use App\Services\Booking\CheckoutShippingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PackageCheckoutController extends Controller
{
    /**
     * Nama channel pembayaran untuk pesan sukses.
     */
    private const CHANNEL_LABELS = [
        'qris' => 'QRIS',
        'bca_va' => 'BCA Virtual Account',
        'bni_va' => 'BNI Virtual Account',
        'bri_va' => 'BRI Virtual Account',
        'permata_va' => 'Permata Virtual Account',
        'cimb_va' => 'CIMB Niaga Virtual Account',
    ];

    /**
     * Halaman konfirmasi sewa paket camping.
     */
    public function index(Request $request, int $id): View
    {
        $package = $this->findPackage($id);

        $requestedMethod = $request->query('pickup_method');
        $pickupMethod = in_array($requestedMethod, ['self_pickup', 'delivery'], true)
            ? $requestedMethod
            : 'self_pickup';

        $pickupDate = $this->resolvePickupDate($request->query('pickup_date'));
        $pickupTime = $this->resolveTime($request->query('pickup_time'), '09:00');

        $duration = max(1, (int) $request->query('duration_days', $package->duration_days ?: 1));

        $returnDate = $this->resolveReturnDate(
            $pickupDate,
            $pickupTime,
            $request->query('return_date'),
            $request->query('return_time'),
            $duration
        );
        $returnTime = $this->resolveTime($request->query('return_time'), $pickupTime);

        $pickupDateTime = Carbon::parse($pickupDate->format('Y-m-d').' '.$pickupTime);
        $returnDateTime = Carbon::parse($returnDate->format('Y-m-d').' '.$returnTime);

        $hours = $returnDateTime->gt($pickupDateTime)
            ? $pickupDateTime->floatDiffInHours($returnDateTime, false)
            : ($duration * 24);

        $rentalDays = max(1, (int) ceil(max(0, $hours) / 24));
        $subtotal = (float) ($package->price * $rentalDays);

        $user = Auth::user();
        $addresses = $user ? $user->addresses()->orderByDesc('is_default')->latest('id')->get() : collect();
        $defaultAddress = $user ? $user->defaultAddress() : null;

        return view('customer.checkout.package', [
            'package' => $package,
            'vendor' => $package->vendor,
            'isLoggedIn' => true,
            'user' => $user,
            'addresses' => $addresses,
            'defaultAddress' => $defaultAddress,
            'pickupMethod' => $pickupMethod,
            'pickupDate' => $pickupDate,
            'pickupTime' => $pickupTime,
            'returnDate' => $returnDate,
            'returnTime' => $returnTime,
            'rentalDays' => $rentalDays,
            'hours' => $hours,
            'subtotal' => $subtotal,
        ]);
    }

    /**
     * Buat rental dari paket camping.
     */
    public function store(StorePackageCheckoutRequest $request, int $id, CheckoutService $checkout, CheckoutShippingService $shipping): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $expectedContext = $request->validated('expected_shipping_context');
        $attemptId = $request->validated('checkout_attempt_id') ?? $expectedContext ?? '';
        $receiptKey = 'package_checkout_receipts.'.$user->id.'.'.$id.'.'.$attemptId;
        if ($expectedContext !== null && $request->session()->has($receiptKey)) {
            $receipt = $request->session()->get($receiptKey);

            return $request->expectsJson() ? response()->json($receipt) : redirect()->to($receipt['redirect_url']);
        }
        $package = $this->findPackage($id);
        $pickupMethod = $request->validated('pickup_method') ?? 'self_pickup';
        $paymentChannel = $request->input('payment_channel');
        if (! array_key_exists((string) $paymentChannel, self::CHANNEL_LABELS)) {
            $paymentChannel = 'bca_va';
        }
        $input = array_filter($request->safe()->only(['pickup_date', 'pickup_time', 'return_date', 'return_time', 'customer_address_id']), fn (mixed $value): bool => $value !== null && $value !== '');
        $input += ['checkout_type' => 'package', 'package_id' => $id, 'pickup_method' => $pickupMethod];

        try {
            $preview = $shipping->preview($input, $user);
            $needsConfirmation = ($pickupMethod === 'delivery' || $request->expectsJson()) && (
                $request->validated('expected_delivery_fee') === null || $expectedContext === null
                || (int) $request->validated('expected_delivery_fee') !== $preview['delivery_fee']
            );
            if ($needsConfirmation || ($expectedContext !== null && ! hash_equals($preview['shipping_context'], $expectedContext))) {
                $message = 'Ongkir atau rincian paket berubah. Periksa total terbaru dan setujui sebelum melanjutkan.';
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'status' => 'shipping_changed', 'message' => $message, 'shipping' => $preview], 409);
                }

                return redirect()->route('customer.packages.checkout', $id)->withInput()->with('shipping_conflict', $message);
            }
            $userUpdates = [];
            foreach (['phone', 'nik'] as $field) {
                if ($request->filled($field) && blank($user->$field)) {
                    $userUpdates[$field] = $request->validated($field);
                }
            }
            if ($userUpdates !== []) {
                $user->update($userUpdates);
            }
            $result = $checkout->checkout([
                'vendor_id' => $preview['vendor_id'], 'user_id' => $user->id,
                'guest_name' => null, 'guest_phone' => null, 'guest_id_number' => null,
                'pickup_date' => $preview['pickup_date'], 'pickup_time' => $preview['pickup_time'],
                'return_date' => $preview['return_date'], 'return_time' => $preview['return_time'],
                'rental_days' => $preview['rental_days'], 'pickup_method' => $pickupMethod,
                'delivery_address' => $preview['delivery_address'], 'customer_address_id' => $preview['customer_address_id'],
                'delivery_fee' => $preview['delivery_fee'], 'discount_amount' => 0,
                'subtotal_override' => $preview['subtotal'],
                'expires_in_minutes' => (int) config('booking.payment_expiry_minutes'),
                'notes' => 'Paket Camping: '.$preview['package_name'].' ('.$preview['rental_days'].' hari sewa)',
                'items' => array_map(fn (array $item): array => ['master_item_id' => $item['id'], 'quantity' => $item['qty']], $preview['items']),
            ], 'full', $paymentChannel, false);
        } catch (ShippingPreviewException $exception) {
            if ($exception->errorCode === 'invalid_schedule') {
                throw ValidationException::withMessages([
                    str_starts_with($exception->getMessage(), 'Waktu pengambilan') ? 'pickup_time' : 'return_time' => $exception->getMessage(),
                ]);
            }
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'status' => $exception->errorCode, 'message' => $exception->getMessage(), 'delivery_fee' => null], $exception->httpStatus);
            }

            return redirect()->route('customer.packages.checkout', $id)->withInput()->with('warning', $exception->getMessage());
        } catch (InsufficientStockException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'status' => 'insufficient_stock', 'message' => $exception->getMessage(), 'delivery_fee' => null], 422);
            }

            return redirect()->route('customer.packages.show', $package)->with('error', $exception->getMessage());
        }
        $rental = $result['rental'];
        $receipt = ['success' => true, 'booking_code' => $rental->booking_code, 'redirect_url' => route('booking.status', $rental->booking_code), 'payment_amount' => (int) $result['payment']->amount];
        if ($expectedContext !== null) {
            $request->session()->put($receiptKey, $receipt);
        }
        if ($request->expectsJson()) {
            return response()->json($receipt);
        }
        $channelLabel = self::CHANNEL_LABELS[$paymentChannel] ?? 'Midtrans Sandbox';

        return redirect()->to($receipt['redirect_url'])
            ->with('success', 'Paket camping "'.$package->name.'" berhasil dipesan dengan kode #'.$rental->booking_code.' via '.$channelLabel.'.');
    }

    /**
     * Ambil paket yang aktif & vendornya aktif.
     */
    protected function findPackage(int $id): CampingPackage
    {
        $package = CampingPackage::publicCatalog()
            ->with(['vendor', 'packageItems.masterItem.category'])
            ->findOrFail($id);

        if ($package->packageItems->contains(fn ($item) => $item->masterItem === null)) {
            abort(404, 'Paket camping ini sedang tidak tersedia karena ada produk yang telah dihapus.');
        }

        return $package;
    }

    protected function resolvePickupDate(?string $value): Carbon
    {
        if (! empty($value)) {
            try {
                $date = Carbon::parse($value)->startOfDay();

                if ($date->gte(Carbon::today())) {
                    return $date;
                }
            } catch (\Throwable) {
                // Abaikan format tanggal tidak valid, gunakan default besok.
            }
        }

        return Carbon::tomorrow();
    }

    protected function resolveTime(?string $value, string $default = '09:00'): string
    {
        if (! empty($value) && preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', trim($value))) {
            return substr(trim($value), 0, 5);
        }

        return $default;
    }

    protected function resolveReturnDate(
        Carbon $pickupDate,
        string $pickupTime,
        ?string $returnDateStr,
        ?string $returnTimeStr,
        int $defaultDays = 1
    ): Carbon {
        if (! empty($returnDateStr)) {
            try {
                $rDate = Carbon::parse($returnDateStr)->startOfDay();
                $rTime = $this->resolveTime($returnTimeStr, $pickupTime);
                $pDateTime = Carbon::parse($pickupDate->format('Y-m-d').' '.$pickupTime);
                $rDateTime = Carbon::parse($rDate->format('Y-m-d').' '.$rTime);

                if ($rDateTime->gt($pDateTime)) {
                    return $rDate;
                }
            } catch (\Throwable) {
                // Abaikan
            }
        }

        return $pickupDate->copy()->addDays(max(1, $defaultDays));
    }
}
