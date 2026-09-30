<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    /**
     * Halaman checkout booking untuk Customer (Role 3).
     */
    public function index(): View
    {
        return view('customer.checkout.index');
    }

    /**
     * Proses pemesanan/checkout rental.
     */
    public function store(Request $request)
    {
        // Akan memanfaatkan RentalService dan MidtransService
    }
}
