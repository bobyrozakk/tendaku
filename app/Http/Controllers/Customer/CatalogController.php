<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ItemCategory;
use App\Models\MasterItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * Tampilkan katalog produk alat camping untuk Customer (Role 3).
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $categoryId = $request->query('category_id');

        $categories = ItemCategory::withCount('items')->get();

        $itemsQuery = MasterItem::with(['category', 'vendor', 'units'])
            ->where('is_active', true);

        if ($search) {
            $itemsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $itemsQuery->where('category_id', $categoryId);
        }

        $items = $itemsQuery->latest()->paginate(12)->withQueryString();

        return view('customer.catalog.index', compact('categories', 'items', 'search', 'categoryId'));
    }

    /**
     * Detail produk alat camping.
     */
    public function show(int $id): View
    {
        $item = MasterItem::with(['category', 'vendor', 'units', 'rentalDetails.rental'])
            ->find($id);

        $bookedDates = [];

        if ($item) {
            foreach ($item->rentalDetails as $detail) {
                if ($detail->rental && in_array($detail->rental->status, ['pending', 'confirmed', 'picked_up'])) {
                    $start = Carbon::parse($detail->rental->pickup_date);
                    $end = Carbon::parse($detail->rental->return_date);
                    while ($start->lte($end)) {
                        $bookedDates[] = $start->format('Y-m-d');
                        $start->addDay();
                    }
                }
            }
            $bookedDates = array_values(array_unique($bookedDates));
        }

        return view('customer.product.show', compact('item', 'bookedDates'));
    }
}
