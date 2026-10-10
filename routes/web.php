<?php

use App\Http\Controllers\Api\MidtransPaymentController;
use App\Http\Controllers\Customer\BookingController;
use App\Http\Controllers\Customer\CatalogController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Webhook\MidtransWebhookController;
use Illuminate\Support\Facades\Route;

// 1. Beranda (Home)
Route::view('/', 'welcome')->name('home');

// 2. Booking / Checkout Flow (Ditaruh SEBELUM katalog/{id} agar tidak tertimpa wildcard)
Route::prefix('katalog/booking')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('status/{code?}', [BookingController::class, 'status'])->name('booking.status');
    Route::get('receipt/{code?}', [BookingController::class, 'receipt'])->name('booking.receipt');
});

// 3. Katalog (menggunakan nama route dari tim)
Route::get('/katalog', [CatalogController::class, 'index'])->name('customer.catalog.index');
Route::get('/katalog/{id}', [CatalogController::class, 'show'])->whereNumber('id')->name('customer.product.show');

// Alias Route URL langsung (untuk backward compatibility & kemudahan akses)
Route::get('checkout', fn () => redirect()->route('checkout.index'));
Route::get('booking/status/{code?}', fn ($code = null) => redirect()->route('booking.status', $code ? ['code' => $code] : []));
Route::get('booking/receipt/{code?}', fn ($code = null) => redirect()->route('booking.receipt', $code ? ['code' => $code] : []));

// TODO: Add guest payment only after defining a secure way to prove rental ownership without login.
Route::post('rentals/{rental}/pay', [MidtransPaymentController::class, 'store'])
    ->middleware('auth')
    ->name('rentals.pay');
Route::post('webhook/midtrans', [MidtransWebhookController::class, 'handle'])
    ->name('webhook.midtrans');

// Design System Reference
Route::view('design-system', 'design-system')->name('design-system');

// Authenticated Routes (Dashboard & Profile)
Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');
// Rute untuk Vendor Dashboard
Route::view('vendor/dashboard', 'vendor.dashboard.index')
    ->middleware(['auth', 'verified'])
    ->name('vendor.dashboard');

// Rute untuk Vendor Units Management
Route::view('vendor/units', 'vendor.units.index')
    ->middleware(['auth', 'verified'])
    ->name('vendor.units');

// Rute untuk Return & Inspeksi Barang
Route::view('vendor/return', 'vendor.return.index')
    ->middleware(['auth', 'verified'])
    ->name('vendor.return');

// Rute untuk Pickup / Serah Terima Barang
Route::view('vendor/pickup', 'vendor.pickup.index')
    ->middleware(['auth', 'verified'])
    ->name('vendor.pickup');

require __DIR__.'/auth.php';
