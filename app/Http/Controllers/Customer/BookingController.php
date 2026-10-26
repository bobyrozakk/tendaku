<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Services\PhoneNumberNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingController extends Controller
{
    /**
     * Halaman riwayat sewa customer (login).
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $status = $request->query('status');

        $query = Rental::query()
            ->where('user_id', $user->id)
            ->with(['vendor:id,name,phone,city', 'details.masterItem'])
            ->latest('id');

        if (! empty($status)) {
            $query->where('status', $status);
        }

        $rentals = $query->paginate(10)->withQueryString();

        return view('customer.booking.index', compact('rentals', 'status'));
    }

    /**
     * Halaman Status Booking & Rental Customer.
     */
    public function status(Request $request, string $code): View|RedirectResponse
    {
        $rental = $this->resolveAndAuthorizeRental($request, $code);

        // Otomatis ubah status menjadi expired jika batas waktu pembayaran (5 menit) telah lewat
        $rental->expireIfPastDue();

        $rental->loadMissing([
            'vendor',
            'customer',
            'details.masterItem',
            'details.itemUnit',
            'payments' => fn ($q) => $q->latest('id'),
        ]);

        return view('customer.booking.status', compact('rental'));
    }

    /**
     * Halaman Digital Collateral Receipt (Tanda Terima KTP Digital).
     */
    public function receipt(Request $request, string $code): View|RedirectResponse
    {
        $rental = $this->resolveAndAuthorizeRental($request, $code);

        $rental->loadMissing([
            'vendor',
            'customer',
            'pickedUpBy',
        ]);

        return view('customer.booking.receipt', compact('rental'));
    }

    /**
     * Route berotorisasi untuk melihat foto jaminan KTP.
     */
    public function collateralPhoto(Request $request, string $code): BinaryFileResponse|StreamedResponse
    {
        $rental = $this->resolveAndAuthorizeRental($request, $code);

        if (empty($rental->ktp_collateral_photo_url)) {
            abort(404, 'Foto jaminan KTP belum tersedia.');
        }

        $photoPath = $rental->ktp_collateral_photo_url;

        // Cek jika foto tersimpan di storage lokal disk
        if (Storage::disk('public')->exists($photoPath)) {
            return Storage::disk('public')->response($photoPath);
        }

        if (Storage::exists($photoPath)) {
            return Storage::response($photoPath);
        }

        if (file_exists($photoPath)) {
            return response()->file($photoPath);
        }

        // Jika berupa URL eksternal, redirect ke URL tersebut
        return redirect()->away($photoPath);
    }

    /**
     * Otorisasi rental:
     * - User login: hanya boleh buka rental dengan user_id miliknya.
     * - Guest: wajib menyediakan phone yang cocok (via query ?phone=... atau session/signed URL).
     */
    private function resolveAndAuthorizeRental(Request $request, string $code): Rental
    {
        /** @var Rental $rental */
        $rental = Rental::query()
            ->where('booking_code', strtoupper($code))
            ->firstOrFail();

        // 1. Jika rental milik user terdaftar
        if ($rental->user_id !== null) {
            if (! Auth::check()) {
                abort(403, 'Akses ditolak. Silakan login dengan akun pemilik rental.');
            }

            if ((int) Auth::id() !== (int) $rental->user_id) {
                abort(403, 'Anda tidak memiliki hak akses untuk melihat transaksi rental ini.');
            }

            return $rental;
        }

        // 2. Jika rental merupakan transaksi guest
        // Cek apakah request memiliki signed signature valid
        if ($request->hasValidSignature()) {
            return $rental;
        }

        // Cek verifikasi nomor telepon guest (via input query parameter ?phone= atau session)
        $providedPhone = $request->query('phone') ?? session("guest_rental_auth_{$rental->booking_code}");

        if (! empty($providedPhone)) {
            // Nomor dinormalisasi (62xxx) supaya tamu boleh mengetik format 08xxx maupun 62xxx.
            $normalizedProvided = PhoneNumberNormalizer::normalize((string) $providedPhone);
            $normalizedGuest = PhoneNumberNormalizer::normalize((string) $rental->guest_phone);

            if ($normalizedProvided !== null && $normalizedProvided === $normalizedGuest) {
                session(["guest_rental_auth_{$rental->booking_code}" => $providedPhone]);

                return $rental;
            }
        }

        abort(403, 'Akses ditolak. Transaksi guest memerlukan verifikasi nomor telepon yang sesuai.');
    }
}
