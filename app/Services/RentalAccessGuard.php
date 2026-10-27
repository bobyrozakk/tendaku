<?php

namespace App\Services;

use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RentalAccessGuard
{
    public function __construct(
        private readonly GuestRentalAccess $guestAccess
    ) {}

    /**
     * Memastikan request berhak mengakses transaksi rental.
     * - Jika user login: harus pemilik rental (user_id cocok).
     * - Jika guest: diverifikasi via session/signed URL/nomor telepon melalui GuestRentalAccess.
     */
    public function authorize(Request $request, Rental $rental): void
    {
        if ($rental->isGuest()) {
            abort_unless(
                $this->guestAccess->allows($request, $rental),
                403,
                'NOT FOUND'
            );

            return;
        }

        abort_unless(
            Auth::check() && $rental->user_id === Auth::id(),
            403,
            'NOT FOUND'
        );
    }
}
