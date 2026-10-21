<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class CashierController extends Controller
{
    /**
     * Tampilkan halaman manajemen akun kasir untuk Owner Vendor.
     */
    public function index(): View
    {
        return view('vendor.cashiers.index');
    }
}
