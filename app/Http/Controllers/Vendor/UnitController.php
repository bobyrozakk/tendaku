<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class UnitController extends Controller
{
    /**
     * Tampilkan halaman manajemen unit fisik untuk Owner Vendor.
     */
    public function index(): View
    {
        return view('vendor.units.index');
    }
}
