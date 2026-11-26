<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CampingPackage;
use App\Services\Booking\AvailabilityService;
use Carbon\Carbon;
use Illuminate\View\View;

class CampingPackageController extends Controller
{
    /**
     * Katalog paket camping publik dari seluruh vendor aktif.
     */
    public function index(): View
    {
        return view('customer.packages.index');
    }

    /**
     * Detail paket camping beserta isi paket dan ketersediaan unit.
     */
    public function show(int $id): View
    {
        /** @var CampingPackage $package */
        $package = CampingPackage::publicCatalog()
            ->with(['vendor', 'packageItems.masterItem.category', 'packageItems.masterItem.units', 'packageItems.masterItem.images', 'masterItems'])
            ->findOrFail($id);

        try {
            $availabilityCalendar = app(AvailabilityService::class)->dailyPackageAvailability(
                $package->packageItems->map(fn ($packageItem) => [
                    'master_item_id' => $packageItem->master_item_id,
                    'quantity' => $packageItem->quantity,
                ]),
                Carbon::today(),
                Carbon::today()->addDays(60)
            );
        } catch (\Throwable $exception) {
            report($exception);
            $availabilityCalendar = ['total' => 0, 'days' => []];
        }

        return view('customer.packages.show', compact('package', 'availabilityCalendar'));
    }
}
