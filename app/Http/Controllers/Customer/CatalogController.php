<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * Tampilkan katalog produk alat camping untuk Customer (Role 3).
     */
    public function index(Request $request): View
    {
        return view('customer.catalog.index');
    }

    /**
     * Detail produk alat camping.
     */
    public function show(int $id): View
    {
        return view('customer.product.show');
    }
}
