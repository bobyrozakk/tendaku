<?php

use App\Http\Controllers\Customer\CatalogController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('/katalog', [CatalogController::class, 'index'])->name('customer.catalog.index');
Route::get('/katalog/{id}', [CatalogController::class, 'show'])->name('customer.product.show');

Route::view('design-system', 'design-system')->name('design-system');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
