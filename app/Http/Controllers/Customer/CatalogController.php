<?php

namespace App\Http\Controllers\Customer;

use App\Enums\RentalStatus;
use App\Http\Controllers\Controller;
use App\Models\MasterItem;
use App\Models\RentalDetail;
use App\Services\Booking\CatalogAvailability;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(
        protected CatalogAvailability $availability
    ) {}

    /**
     * Tampilkan katalog produk alat camping untuk Customer (Role 3).
     */
    public function index(): View
    {
        return view('customer.catalog.index');
    }

    public function home(Request $request): View
    {
        $dateRange = $this->availability->dateRange(
            $request->string('start_date')->toString(),
            $request->string('end_date')->toString(),
        );
        $bookingCount = RentalDetail::query()
            ->selectRaw('COUNT(DISTINCT rental_details.rental_id)')
            ->whereColumn('rental_details.master_item_id', 'master_items.id')
            ->whereHas('rental', fn ($rental) => $rental
                ->withoutGlobalScopes()
                ->whereIn('status', [
                    RentalStatus::Confirmed->value,
                    RentalStatus::PickedUp->value,
                    RentalStatus::Returned->value,
                    RentalStatus::Completed->value,
                ]));

        $popularItems = MasterItem::publicCatalog()
            ->addSelect(['booking_count' => $bookingCount])
            ->with(['category:id,name', 'vendor:id,name,slug', 'units:id,master_item_id,condition,purchase_date'])
            ->orderByDesc('booking_count')
            ->latest('id')
            ->limit(3)
            ->get();
        $dateStockMap = $this->availability->batchStock($popularItems->pluck('id')->all(), $dateRange);

        if ($request->ajax() || $request->boolean('bestsellers_partial')) {
            return view('landing.sections.partials.bestsellers-results', compact('popularItems', 'dateRange', 'dateStockMap'));
        }

        return view('welcome', compact('popularItems', 'dateRange', 'dateStockMap'));
    }

    /**
     * Detail produk alat camping.
     * Menggunakan findOrFail yang hanya mengizinkan public catalog (vendor aktif).
     */
    public function show(Request $request, int $id): View
    {
        /** @var MasterItem $item */
        $item = MasterItem::publicCatalog()
            ->with(['category', 'vendor', 'units', 'images'])
            ->findOrFail($id);

        return view('customer.product.show', [
            'item' => $item,
            'startDate' => $request->string('start_date')->toString(),
            'endDate' => $request->string('end_date')->toString(),
        ]);
    }
}
