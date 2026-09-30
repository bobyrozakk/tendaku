<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class RentalController extends Controller
{
    /**
     * Dashboard operasional rental & POS kasir untuk Vendor (Role 4).
     */
    public function index(): View
    {
        return view('vendor.dashboard.index');
    }

    /**
     * Alur pengambilan & pengembalian barang (Pickup / Return).
     */
    public function pickupReturn(): View
    {
        return view('vendor.pickup-return.index');
    }
}
