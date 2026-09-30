<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Manajemen inventaris / produk untuk Vendor (Role 4).
     */
    public function index(): View
    {
        return view('vendor.products.index');
    }
}
