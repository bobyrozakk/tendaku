<?php

use App\Http\Controllers\Admin\RevenueExportController;
use App\Http\Controllers\Api\MidtransPaymentController;
use App\Http\Controllers\Customer\BookingController;
use App\Http\Controllers\Customer\CampingPackageController as CustomerCampingPackageController;
use App\Http\Controllers\Customer\CatalogController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\PackageCheckoutController;
use App\Http\Controllers\Vendor\CampingPackageController;
use App\Http\Controllers\Vendor\CashierController;
use App\Http\Controllers\Vendor\CategoryController;
use App\Http\Controllers\Vendor\OwnerDashboardController;
use App\Http\Controllers\Vendor\ProductController;
use App\Http\Controllers\Vendor\UnitController;
use App\Http\Controllers\Webhook\MidtransWebhookController;
use App\Livewire\Kasir\ProfileKasir;
use Illuminate\Support\Facades\Route;

// 1. Beranda (Home)
Route::view('/', 'welcome')->name('home');

// 2. Booking / Checkout Flow
Route::prefix('katalog/booking')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('status/{code}', [BookingController::class, 'status'])->name('booking.status');
    Route::get('receipt/{code}', [BookingController::class, 'receipt'])->name('booking.receipt');
    Route::get('receipt/{code}/photo', [BookingController::class, 'collateralPhoto'])->name('booking.receipt.photo');
});

// Riwayat Sewa Customer (Wajib Login)
Route::get('riwayat-sewa', [BookingController::class, 'index'])
    ->middleware(['auth'])
    ->name('customer.booking.index');

// 3. Katalog
Route::get('/katalog', [CatalogController::class, 'index'])->name('customer.catalog.index');
Route::get('/katalog/{id}', [CatalogController::class, 'show'])
    ->whereNumber('id')
    ->name('customer.product.show');

// Paket Camping (publik)
Route::get('/paket-camping', [CustomerCampingPackageController::class, 'index'])
    ->name('customer.packages.index');
Route::get('/paket-camping/{id}/checkout', [PackageCheckoutController::class, 'index'])
    ->whereNumber('id')
    ->name('customer.packages.checkout');
Route::post('/paket-camping/{id}/checkout', [PackageCheckoutController::class, 'store'])
    ->whereNumber('id')
    ->name('customer.packages.checkout.store');
Route::get('/paket-camping/{id}', [CustomerCampingPackageController::class, 'show'])
    ->whereNumber('id')
    ->name('customer.packages.show');

// Prakiraan Cuaca Spot Camping (publik)
Route::view('/cuaca', 'customer.weather.index')->name('customer.weather.index');

// Alias Route URL langsung (untuk backward compatibility & kemudahan akses)
Route::get('checkout', fn () => redirect()->route('checkout.index'));
Route::get('booking/status/{code}', fn ($code) => redirect()->route('booking.status', ['code' => $code]));
Route::get('booking/receipt/{code}', fn ($code) => redirect()->route('booking.receipt', ['code' => $code]));

// Rute Pembayaran Midtrans SNAP
// TODO: Add guest payment only after defining a secure way to prove rental ownership without login.
Route::post('rentals/{rental}/pay', [MidtransPaymentController::class, 'store'])
    ->middleware('auth')
    ->name('rentals.pay');

Route::post('webhook/midtrans', [MidtransWebhookController::class, 'handle'])
    ->name('webhook.midtrans');

// Design System Reference
Route::view('design-system', 'design-system')->name('design-system');

// Authenticated Routes (Dashboard & Profile)
Route::get('dashboard', function () {
    $user = auth()->user();

    if ($user?->isVendorOwner()) {
        return redirect()->route('vendor.owner.dashboard');
    }

    if ($user?->isVendorAdmin()) {
        return redirect()->route('kasir.dashboard');
    }

    return view('dashboard');
})
    ->middleware(['auth', 'verified', 'role:super_admin,vendor_owner,vendor_admin'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Rute Super Admin — laporan pendapatan platform (monetisasi).
Route::middleware(['super_admin', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('pendapatan', 'admin.revenue.index')->name('pendapatan');
        Route::get('pendapatan/export', [RevenueExportController::class, 'csv'])
            ->name('pendapatan.export');
    });

// Rute Modul KASIR & POS Toko (Role: vendor_admin / Kasir)
Route::middleware(['auth', 'verified', 'role:vendor_admin', 'vendor.active'])
    ->prefix('kasir')
    ->name('kasir.')
    ->group(function () {
        Route::view('dashboard', 'kasir.dashboard.index')->name('dashboard');
        Route::view('units', 'kasir.units.index')->name('units');
        Route::view('pickup', 'kasir.pickup.index')->name('pickup');
        Route::view('return', 'kasir.return.index')->name('return');
        Route::get('profile', ProfileKasir::class)->name('profile');
    });

// Rute khusus Owner Vendor
Route::middleware(['auth', 'verified', 'vendor.owner', 'vendor.active'])
    ->prefix('vendor')
    ->name('vendor.')
    ->group(function () {
        Route::get('owner/dashboard', [OwnerDashboardController::class, 'index'])
            ->name('owner.dashboard');

        Route::get('owner/dashboard/export', [OwnerDashboardController::class, 'exportDailyRevenue'])
            ->name('owner.dashboard.export');

        Route::resource('products', ProductController::class);
        Route::resource('packages', CampingPackageController::class)
            ->except(['show']);
        Route::resource('categories', CategoryController::class)
            ->except(['show']);

        Route::get('units', [UnitController::class, 'index'])
            ->name('units');

        Route::get('cashiers', [CashierController::class, 'index'])
            ->name('cashiers.index');
    });

// Backward compatibility redirect untuk rute lama /vendor/*
Route::get('vendor/dashboard', function () {
    if (auth()->user()?->isVendorOwner()) {
        return redirect()->route('vendor.owner.dashboard');
    }

    return redirect()->route('kasir.dashboard');
})->middleware(['auth', 'verified', 'role:vendor_owner,vendor_admin', 'vendor.active'])->name('vendor.dashboard');

Route::get('vendor/pickup', fn () => redirect()->route('kasir.pickup'))
    ->middleware(['auth', 'verified', 'role:vendor_owner,vendor_admin', 'vendor.active'])
    ->name('vendor.pickup');

Route::get('vendor/return', fn () => redirect()->route('kasir.return'))
    ->middleware(['auth', 'verified', 'role:vendor_owner,vendor_admin', 'vendor.active'])
    ->name('vendor.return');

require __DIR__.'/auth.php';
