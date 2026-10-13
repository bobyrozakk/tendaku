<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ItemCategory;
use App\Models\MasterItem;
use App\Models\Vendor;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService
    ) {}

    /**
     * Tampilkan katalog produk alat camping untuk Customer (Role 3).
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $categoryId = $request->query('category_id');
        $vendorId = $request->query('vendor_id');
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $sort = $request->query('sort', 'latest');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $categories = ItemCategory::withCount(['items' => function ($q) {
            $q->publicCatalog();
        }])->get();

        $vendors = Vendor::whereIn('status', ['active', 'approved'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $itemsQuery = MasterItem::publicCatalog()
            ->with(['category', 'vendor'])
            ->withCount(['units' => function ($q) {
                $q->whereNotIn('status', ['maintenance', 'retired'])
                    ->whereNotIn('condition', ['damaged', 'lost']);
            }]);

        if (! empty($search)) {
            $itemsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($categoryId)) {
            $itemsQuery->where('category_id', $categoryId);
        }

        if (! empty($vendorId)) {
            $itemsQuery->where('vendor_id', $vendorId);
        }

        if (! empty($minPrice) && is_numeric($minPrice)) {
            $itemsQuery->where('daily_rate', '>=', (float) $minPrice);
        }

        if (! empty($maxPrice) && is_numeric($maxPrice)) {
            $itemsQuery->where('daily_rate', '<=', (float) $maxPrice);
        }

        match ($sort) {
            'price_asc' => $itemsQuery->orderBy('daily_rate', 'asc'),
            'price_desc' => $itemsQuery->orderBy('daily_rate', 'desc'),
            'name_asc' => $itemsQuery->orderBy('name', 'asc'),
            default => $itemsQuery->latest(),
        };

        $items = $itemsQuery->paginate(12)->withQueryString();

        // Jika user memilih filter rentang tanggal sewa, hitung stok tersedia per item via AvailabilityService
        $dateStockMap = [];
        if (! empty($startDate) && ! empty($endDate)) {
            try {
                $sDate = Carbon::parse($startDate);
                $eDate = Carbon::parse($endDate);
                if ($eDate->gte($sDate) && $sDate->gte(Carbon::today())) {
                    foreach ($items->items() as $item) {
                        $dateStockMap[$item->id] = $this->availabilityService->checkAvailability(
                            $item->id,
                            $startDate,
                            $endDate
                        );
                    }
                }
            } catch (\Throwable) {
                // Ignore invalid date format and fallback to total candidate units
            }
        }

        return view('customer.catalog.index', compact(
            'categories',
            'vendors',
            'items',
            'search',
            'categoryId',
            'vendorId',
            'minPrice',
            'maxPrice',
            'sort',
            'startDate',
            'endDate',
            'dateStockMap'
        ));
    }

    /**
     * Detail produk alat camping.
     * Menggunakan findOrFail yang hanya mengizinkan public catalog (vendor aktif).
     */
    public function show(int $id): View
    {
        /** @var MasterItem $item */
        $item = MasterItem::publicCatalog()
            ->with(['category', 'vendor', 'units'])
            ->findOrFail($id);

        $bookedDates = $this->availabilityService->bookedDates(
            $item->id,
            Carbon::today(),
            Carbon::today()->addDays(60)
        );

        return view('customer.product.show', compact('item', 'bookedDates'));
    }
}
