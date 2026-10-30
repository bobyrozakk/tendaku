<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CampingPackage;
use App\Models\ItemCategory;
use App\Models\MasterItem;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreController extends Controller
{
    /**
     * Tampilkan profil toko vendor, informasi ringkas, serta katalog alat & paket camping miliknya.
     */
    public function show(Request $request, string $vendor): View
    {
        // Temukan vendor aktif berdasarkan slug atau id
        $vendorRecord = Vendor::where(function ($query) use ($vendor) {
            $query->where('slug', $vendor);
            if (is_numeric($vendor)) {
                $query->orWhere('id', (int) $vendor);
            }
        })
            ->whereIn('status', ['active', 'approved'])
            ->withCount([
                'items' => fn ($q) => $q->where('is_active', true),
                'campingPackages' => fn ($q) => $q->where('is_active', true),
            ])
            ->firstOrFail();

        $search = trim((string) $request->query('search', ''));
        $categoryId = $request->query('category_id');
        $tab = $request->query('tab', 'items'); // 'items' atau 'packages'

        // Kategori yang dimiliki oleh item toko ini saja
        $categories = ItemCategory::whereHas('items', function ($query) use ($vendorRecord) {
            $query->where('vendor_id', $vendorRecord->id)
                ->where('is_active', true);
        })
            ->withCount(['items' => function ($query) use ($vendorRecord) {
                $query->where('vendor_id', $vendorRecord->id)
                    ->where('is_active', true);
            }])
            ->get();

        // Query katalog alat camping milik vendor ini saja (Eager loading mencegah N+1)
        $itemsQuery = MasterItem::where('vendor_id', $vendorRecord->id)
            ->where('is_active', true)
            ->with(['category', 'primaryImage', 'images', 'vendor'])
            ->withCount(['units' => function ($query) {
                $query->where('status', 'available')
                    ->whereNotIn('condition', ['damaged', 'lost']);
            }]);

        if ($search !== '') {
            $itemsQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($categoryId)) {
            $itemsQuery->where('category_id', $categoryId);
        }

        $items = $itemsQuery->latest()->paginate(12)->withQueryString();

        // Query paket camping milik vendor ini saja
        $packagesQuery = CampingPackage::where('vendor_id', $vendorRecord->id)
            ->where('is_active', true)
            ->with(['packageItems.masterItem', 'vendor']);

        if ($search !== '') {
            $packagesQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $packages = $packagesQuery->latest()->paginate(12)->withQueryString();

        return view('customer.store.show', [
            'vendor' => $vendorRecord,
            'items' => $items,
            'packages' => $packages,
            'categories' => $categories,
            'search' => $search,
            'categoryId' => $categoryId ? (int) $categoryId : null,
            'tab' => $tab,
        ]);
    }
}
