<?php

use App\Http\Controllers\Customer\BookingController;
use App\Http\Controllers\Customer\CheckoutController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('design-system', 'design-system')->name('design-system');

// Customer Checkout Flow
Route::get('checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');

// Customer Booking Status & Digital Collateral Receipt (Role 3B)
Route::get('booking/status/{code?}', [BookingController::class, 'status'])->name('booking.status');
Route::get('booking/receipt/{code?}', [BookingController::class, 'receipt'])->name('booking.receipt');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
