<?php

namespace App\Http\Controllers\Customer;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\CampingPackage;
use App\Services\PhoneNumberNormalizer;
use App\Services\RentalService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PackageCheckoutController extends Controller
{
    /**
     * Masa berlaku pembayaran (jam) sebelum booking paket kedaluwarsa.
     */
    public const PAYMENT_WINDOW_HOURS = 12;

    /**
     * Biaya pengantaran (delivery) dalam Rupiah.
     */
    public const DELIVERY_FEE = 25000;

    /**
     * Halaman konfirmasi sewa paket camping.
     */
    public function index(Request $request, int $id): View
    {
        $package = $this->findPackage($id);

        $isLoggedIn = Auth::check();
        $requestedMethod = $request->query('pickup_method');
        $pickupMethod = $isLoggedIn && in_array($requestedMethod, ['self_pickup', 'delivery'], true)
            ? $requestedMethod
            : 'self_pickup';

        $pickupDate = $this->resolvePickupDate($request->query('pickup_date'));

        return view('customer.packages.checkout', [
            'package' => $package,
            'vendor' => $package->vendor,
            'isLoggedIn' => $isLoggedIn,
            'pickupMethod' => $pickupMethod,
            'pickupDate' => $pickupDate,
            'returnDate' => $this->resolveReturnDate($pickupDate, (int) $package->duration_days),
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

        if ($pickupMethod === 'delivery' && ! Auth::check()) {
            return redirect()->route('login')
                ->with('warning', 'Layanan pengantaran (delivery) mengharuskan Anda memiliki akun terverifikasi.');
        }

        $isGuest = ! Auth::check();

        $validated = $request->validate([
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'guest_name' => [$isGuest ? 'required' : 'nullable', 'string', 'max:255'],
            'guest_phone' => [$isGuest ? 'required' : 'nullable', 'string', 'max:32'],
            'guest_id_number' => [$isGuest ? 'required' : 'nullable', 'string', 'max:32'],
        ], [
            'pickup_date.required' => 'Tanggal mulai sewa wajib dipilih.',
            'pickup_date.after_or_equal' => 'Tanggal mulai sewa tidak boleh di masa lalu.',
            'guest_name.required' => 'Nama lengkap wajib diisi untuk pemesanan tanpa akun.',
            'guest_phone.required' => 'Nomor WhatsApp wajib diisi agar pengingat pengembalian dapat dikirim.',
            'guest_id_number.required' => 'NIK wajib diisi untuk pemesanan tanpa akun.',
        ]);

        if ($isGuest && PhoneNumberNormalizer::normalize($validated['guest_phone']) === null) {
            throw ValidationException::withMessages([
                'guest_phone' => 'Nomor WhatsApp tidak valid. Gunakan format 08xxxxxxxxxx.',
            ]);
        }

        $deliveryAddress = $pickupMethod === 'delivery' ? Auth::user()?->address : null;

        if ($pickupMethod === 'delivery' && blank($deliveryAddress)) {
            return redirect()->route('customer.packages.checkout', $package)
                ->with('warning', 'Alamat pengantaran belum lengkap di profil Anda. Lengkapi alamat pada halaman profil terlebih dahulu.');
        }

        $pickupDate = Carbon::parse($validated['pickup_date'])->startOfDay();
        $returnDate = $this->resolveReturnDate($pickupDate, (int) $package->duration_days);

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
                'user_id' => Auth::id(),
                'guest_name' => $isGuest ? $validated['guest_name'] : null,
                'guest_phone' => $isGuest ? $validated['guest_phone'] : null,
                'guest_id_number' => $isGuest ? $validated['guest_id_number'] : null,
                'pickup_date' => $pickupDate->toDateString(),
                'return_date' => $returnDate->toDateString(),
                'pickup_method' => $pickupMethod,
                'delivery_address' => $deliveryAddress,
                'delivery_fee' => $pickupMethod === 'delivery' ? self::DELIVERY_FEE : 0,
                'discount_amount' => 0,
                // Harga paket sudah ditetapkan owner, bukan akumulasi tarif harian alat.
                'subtotal_override' => (float) $package->price,
                'expires_in_hours' => self::PAYMENT_WINDOW_HOURS,
                'notes' => 'Paket Camping: '.$package->name.' ('.$package->duration_days.' hari)',
                'items' => $items,
            ]);
        } catch (InsufficientStockException $exception) {
            return redirect()->route('customer.packages.show', $package)
                ->with('error', $exception->getMessage());
        }

        $channelNames = [
            'qris' => 'QRIS Realtime',
            'bca_va' => 'BCA Virtual Account',
            'mandiri_va' => 'Mandiri Bill Payment',
            'bri_va' => 'BRI Virtual Account (BRIVA)',
        ];
        $channelLabel = $channelNames[$paymentChannel] ?? 'Midtrans Sandbox';

        return redirect()->route('booking.status', $rental->booking_code)
            ->with('success', "Paket camping \"{$package->name}\" berhasil dipesan dengan kode #{$rental->booking_code} via {$channelLabel}.");
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

    protected function resolveReturnDate(Carbon $pickupDate, int $durationDays): Carbon
    {
        return $pickupDate->copy()->addDays(max(1, $durationDays) - 1);
    }
}
