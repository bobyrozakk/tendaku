<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\CampingPackage;
use App\Services\Booking\RentalService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PackageCheckoutController extends Controller
{
    /**
     * Masa berlaku pembayaran (menit) sebelum booking paket kedaluwarsa (maksimal 5 menit).
     */
    public const PAYMENT_TIMEOUT_HOURS = 1;

    /**
     * Masa berlaku pembayaran (jam) sebelum booking paket kedaluwarsa (kompatibilitas backward).
     */
    public const PAYMENT_WINDOW_HOURS = 12;

    /**
     * Biaya pengantaran (delivery) dalam Rupiah.
     */
    public const DELIVERY_FEE = 25000;

    /**
     * Nama channel pembayaran untuk pesan sukses.
     */
    private const CHANNEL_LABELS = [
        'bca_va' => 'BCA Virtual Account',
        'bri_va' => 'BRI Virtual Account (BRIVA)',
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

        return view('customer.packages.checkout', [
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
    public function store(Request $request, RentalService $rentalService): RedirectResponse
    {
        $package = $this->findPackage((int) $request->input('package_id'));

        $pickupMethod = $request->input('pickup_method', 'self_pickup');
        $paymentChannel = $request->input('payment_channel', 'qris');
        $user = Auth::user();

        $isGuest = ! Auth::check();

        $validated = $this->validatedInput($request, $isGuest);

        $deliveryAddress = null;
        $customerAddressId = null;

        if ($pickupMethod === 'delivery') {
            $user = Auth::user();
            $addressId = $request->input('customer_address_id');
            $selectedAddress = null;

            if ($addressId) {
                // Pastikan alamat milik customer yang login (tidak dapat dimanipulasi)
                $selectedAddress = $user->addresses()->where('id', $addressId)->first();
            }

            if (! $selectedAddress) {
                $selectedAddress = $user->defaultAddress();
            }

            if ($selectedAddress) {
                $deliveryAddress = $selectedAddress->toDeliverySnapshot();
                $customerAddressId = $selectedAddress->id;
            } else {
                $deliveryAddress = $request->input('delivery_address') ?: $user->address;
            }

            if (blank($deliveryAddress)) {
                return redirect()->route('customer.packages.checkout', $package)
                    ->with('warning', 'Alamat pengantaran belum tersedia. Silakan pilih atau tambahkan alamat terlebih dahulu.');
            }
        }

        // Jika member melengkapi nomor telepon atau NIK, simpan ke akunnya
        if (! $isGuest) {
            $user = Auth::user();
            $userUpdates = [];
            if ($request->filled('phone') && blank($user->phone)) {
                $userUpdates['phone'] = $request->input('phone');
            }
            if ($request->filled('nik') && blank($user->nik)) {
                $userUpdates['nik'] = $request->input('nik');
            }
            if (! empty($userUpdates)) {
                $user->update($userUpdates);
            }
        }

        $pickupTimeStr = ! empty($validated['pickup_time']) ? trim($validated['pickup_time']) : '09:00';
        $pickupDateTime = Carbon::parse($validated['pickup_date'].' '.$pickupTimeStr);

        // Validasi waktu pengambilan tidak boleh di masa lalu
        if ($pickupDateTime->lt(Carbon::now()->subMinute())) {
            throw ValidationException::withMessages([
                'pickup_time' => 'Waktu pengambilan tidak boleh di masa lalu.',
            ]);
        }

        $returnDateStr = ! empty($validated['return_date'])
            ? trim($validated['return_date'])
            : $pickupDateTime->copy()->addDays(max(1, (int) $package->duration_days))->format('Y-m-d');
        $returnTimeStr = ! empty($validated['return_time'])
            ? trim($validated['return_time'])
            : $pickupTimeStr;

        $returnDateTime = Carbon::parse($returnDateStr.' '.$returnTimeStr);

        // Validasi waktu pengembalian wajib setelah waktu pengambilan (tidak boleh sama atau lebih awal)
        if ($returnDateTime->lte($pickupDateTime)) {
            throw ValidationException::withMessages([
                'return_time' => 'Waktu pengembalian harus setelah waktu pengambilan.',
            ]);
        }

        $hours = $pickupDateTime->floatDiffInHours($returnDateTime, false);
        if ($hours <= 0) {
            throw ValidationException::withMessages([
                'return_time' => 'Waktu pengembalian harus setelah waktu pengambilan.',
            ]);
        }

        // Durasi sewa 1 hari = 24 jam penuh. Melebihi 24 jam dibulatkan ke atas per 24 jam.
        $rentalDays = max(1, (int) ceil($hours / 24));
        $subtotal = $rentalDays * (float) $package->price;

        $items = $package->packageItems
            ->map(fn ($item) => [
                'master_item_id' => (int) $item->master_item_id,
                'quantity' => (int) $item->quantity,
            ])
            ->values()
            ->all();

        try {
            $rental = $rentalService->createBooking([
                'vendor_id' => (int) $package->vendor_id,
                'user_id' => $user->id,
                'guest_name' => null,
                'guest_phone' => null,
                'guest_id_number' => null,
                'pickup_date' => $pickupDateTime->format('Y-m-d'),
                'pickup_time' => $pickupDateTime->format('H:i:s'),
                'return_date' => $returnDateTime->format('Y-m-d'),
                'return_time' => $returnDateTime->format('H:i:s'),
                'rental_days' => $rentalDays,
                'pickup_method' => $pickupMethod,
                'delivery_address' => $deliveryAddress,
                'customer_address_id' => $customerAddressId,
                'delivery_fee' => $pickupMethod === 'delivery' ? self::DELIVERY_FEE : 0,
                'discount_amount' => 0,
                // Tarif paket camping per 24 jam x jumlah periode sewa
                'subtotal_override' => $subtotal,
                'expires_in_minutes' => self::PAYMENT_TIMEOUT_MINUTES,
                'notes' => 'Paket Camping: '.$package->name.' ('.$rentalDays.' hari sewa)',
                'items' => $items,
            ]);
        } catch (InsufficientStockException $exception) {
            return redirect()->route('customer.packages.show', $package)
                ->with('error', $exception->getMessage());
        }

        $channelLabel = self::CHANNEL_LABELS[$paymentChannel] ?? 'Midtrans Sandbox';

        return redirect()->route('booking.status', $rental->booking_code)
            ->with('success', "Paket camping \"{$package->name}\" berhasil dipesan dengan kode #{$rental->booking_code} via {$channelLabel}.");
    }

    /**
     * Validasi input checkout paket.
     *
     * @return array<string, mixed>
     */
    private function validatedInput(Request $request, bool $isGuest = false): array
    {
        return $request->validate([
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'pickup_time' => ['nullable', 'date_format:H:i'],
            'return_date' => ['nullable', 'date', 'after_or_equal:pickup_date'],
            'return_time' => ['nullable', 'date_format:H:i'],
            'pickup_method' => ['nullable', 'in:self_pickup,delivery'],
            'guest_name' => [$isGuest ? 'required' : 'nullable', 'string', 'max:255'],
            'guest_phone' => [$isGuest ? 'required' : 'nullable', 'string', 'max:32'],
            'guest_id_number' => [$isGuest ? 'required' : 'nullable', 'string', 'max:32'],
            'customer_address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            'delivery_address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:32'],
            'nik' => ['nullable', 'string', 'max:32'],
        ], [
            'pickup_date.required' => 'Tanggal mulai sewa wajib dipilih.',
            'pickup_date.after_or_equal' => 'Tanggal mulai sewa tidak boleh di masa lalu.',
            'return_date.after_or_equal' => 'Tanggal pengembalian harus sama atau setelah tanggal mulai sewa.',
            'guest_name.required' => 'Nama lengkap wajib diisi untuk pemesanan tanpa akun.',
            'guest_phone.required' => 'Nomor WhatsApp wajib diisi agar pengingat pengembalian dapat dikirim.',
            'guest_id_number.required' => 'NIK wajib diisi untuk pemesanan tanpa akun.',
            'customer_address_id.exists' => 'Alamat pengantaran yang dipilih tidak valid.',
        ]);
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
