<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CampingPackage;
use App\Models\ItemCategory;
use App\Models\MasterItem;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreController extends Controller
{
    private const TAB_ITEMS = 'items';

    private const TAB_PACKAGES = 'packages';

    /**
     * Rentang harga untuk filter sidebar. `min` inklusif, `max` eksklusif (dalam rupiah).
     * Alat dihitung per hari, paket per paket, sehingga rentangnya berbeda.
     */
    private const PRICE_BUCKETS = [
        self::TAB_ITEMS => [
            'lt_25' => ['label' => 'Di bawah Rp 25.000', 'min' => null, 'max' => 25000],
            '25_50' => ['label' => 'Rp 25.000 – 50.000', 'min' => 25000, 'max' => 50000],
            '50_100' => ['label' => 'Rp 50.000 – 100.000', 'min' => 50000, 'max' => 100000],
            'gte_100' => ['label' => 'Di atas Rp 100.000', 'min' => 100000, 'max' => null],
        ],
        self::TAB_PACKAGES => [
            'lt_150' => ['label' => 'Di bawah Rp 150.000', 'min' => null, 'max' => 150000],
            '150_300' => ['label' => 'Rp 150.000 – 300.000', 'min' => 150000, 'max' => 300000],
            '300_500' => ['label' => 'Rp 300.000 – 500.000', 'min' => 300000, 'max' => 500000],
            'gte_500' => ['label' => 'Di atas Rp 500.000', 'min' => 500000, 'max' => null],
        ],
    ];

    private const SORT_OPTIONS = [
        'latest' => 'Terbaru',
        'price_asc' => 'Harga terendah',
        'price_desc' => 'Harga tertinggi',
        'name' => 'Nama A–Z',
    ];

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
        $tab = $request->query('tab') === self::TAB_PACKAGES && $vendorRecord->camping_packages_count > 0
            ? self::TAB_PACKAGES
            : self::TAB_ITEMS;

        $buckets = self::PRICE_BUCKETS[$tab];
        $price = $request->query('price');
        $price = is_string($price) && isset($buckets[$price]) ? $price : null;

        $sort = $request->query('sort');
        $sort = is_string($sort) && isset(self::SORT_OPTIONS[$sort]) ? $sort : 'latest';

        // Filter kategori & stok hanya berlaku untuk tab alat satuan.
        $categoryId = $tab === self::TAB_ITEMS && $request->query('category_id')
            ? (int) $request->query('category_id')
            : null;
        $inStock = $tab === self::TAB_ITEMS && $request->boolean('stock');

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

        $items = null;
        $packages = null;

        if ($tab === self::TAB_PACKAGES) {
            $baseQuery = fn (?string $bucket) => $this->packagesBase($vendorRecord, $search, $bucket);

            $packages = $baseQuery($price)
                ->with(['packageItems', 'masterItems', 'vendor'])
                ->tap(fn (Builder $q) => $this->applySort($q, $sort, 'price'))
                ->paginate(12)
                ->withQueryString();

            $priceCounts = $this->bucketCounts($baseQuery(null), 'price', $buckets);
            $stockCount = null;
        } else {
            $baseQuery = fn (?string $bucket, bool $onlyInStock) => $this->itemsBase(
                $vendorRecord, $search, $categoryId, $bucket, $onlyInStock
            );

            // Eager loading mencegah N+1
            $items = $baseQuery($price, $inStock)
                ->with(['category', 'primaryImage', 'images', 'vendor', 'units:id,master_item_id,condition,purchase_date'])
                ->withCount(['units' => fn ($q) => $this->availableUnits($q)])
                ->tap(fn (Builder $q) => $this->applySort($q, $sort, 'daily_rate'))
                ->paginate(12)
                ->withQueryString();

            // Facet dihitung dengan filter lain tetap aktif, sehingga angkanya cocok dengan hasil klik.
            $priceCounts = $this->bucketCounts($baseQuery(null, $inStock), 'daily_rate', $buckets);
            $stockCount = $baseQuery($price, false)
                ->whereHas('units', fn ($q) => $this->availableUnits($q))
                ->count();
        }

        return view('customer.store.show', [
            'vendor' => $vendorRecord,
            'items' => $items,
            'packages' => $packages,
            'categories' => $categories,
            'search' => $search,
            'categoryId' => $categoryId,
            'tab' => $tab,
            'price' => $price,
            'priceBuckets' => $buckets,
            'priceCounts' => $priceCounts,
            'inStock' => $inStock,
            'stockCount' => $stockCount,
            'sort' => $sort,
            'sortOptions' => self::SORT_OPTIONS,
            'spotlightItems' => $this->spotlightItems($vendorRecord),
        ]);
    }

    private function itemsBase(Vendor $vendor, string $search, ?int $categoryId, ?string $bucket, bool $inStock): Builder
    {
        $query = MasterItem::where('vendor_id', $vendor->id)->where('is_active', true);

        $this->applySearch($query, $search);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $this->applyBucket($query, $bucket !== null ? self::PRICE_BUCKETS[self::TAB_ITEMS][$bucket] ?? null : null, 'daily_rate');

        if ($inStock) {
            $query->whereHas('units', fn ($q) => $this->availableUnits($q));
        }

        return $query;
    }

    private function packagesBase(Vendor $vendor, string $search, ?string $bucket): Builder
    {
        $query = CampingPackage::where('vendor_id', $vendor->id)->where('is_active', true);

        $this->applySearch($query, $search);
        $this->applyBucket($query, $bucket !== null ? self::PRICE_BUCKETS[self::TAB_PACKAGES][$bucket] ?? null : null, 'price');

        return $query;
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * @param  array{min: ?int, max: ?int}|null  $bucket
     */
    private function applyBucket(Builder $query, ?array $bucket, string $column): void
    {
        if ($bucket === null) {
            return;
        }

        if ($bucket['min'] !== null) {
            $query->where($column, '>=', $bucket['min']);
        }

        if ($bucket['max'] !== null) {
            $query->where($column, '<', $bucket['max']);
        }
    }

    private function applySort(Builder $query, string $sort, string $priceColumn): void
    {
        match ($sort) {
            'price_asc' => $query->orderBy($priceColumn),
            'price_desc' => $query->orderByDesc($priceColumn),
            'name' => $query->orderBy('name'),
            default => $query->latest(),
        };

        // Urutan tetap stabil antar halaman saat nilai sort sama.
        $query->orderByDesc('id');
    }

    /**
     * Unit yang benar-benar bisa disewa (selaras dengan hitungan stok di kartu produk).
     */
    private function availableUnits(Builder $query): void
    {
        $query->where('status', 'available')
            ->whereNotIn('condition', ['damaged', 'lost']);
    }

    /**
     * Hitung jumlah baris per rentang harga dalam satu query.
     *
     * @param  array<string, array{label: string, min: ?int, max: ?int}>  $buckets
     * @return array<string, int>
     */
    private function bucketCounts(Builder $query, string $column, array $buckets): array
    {
        $selects = [];

        foreach ($buckets as $key => $bucket) {
            $conditions = [];
            if ($bucket['min'] !== null) {
                $conditions[] = "{$column} >= ".(int) $bucket['min'];
            }
            if ($bucket['max'] !== null) {
                $conditions[] = "{$column} < ".(int) $bucket['max'];
            }

            $selects[] = 'COUNT(CASE WHEN '.implode(' AND ', $conditions)." THEN 1 END) AS b_{$key}";
        }

        $row = (array) $query->toBase()->selectRaw(implode(', ', $selects))->first();

        return collect($buckets)
            ->mapWithKeys(fn ($bucket, $key) => [$key => (int) ($row["b_{$key}"] ?? 0)])
            ->all();
    }

    /**
     * Alat pilihan toko untuk visual header: yang paling sering disewa, lalu terbaru.
     * Hanya alat yang punya foto agar komposisi header tidak kosong.
     */
    private function spotlightItems(Vendor $vendor)
    {
        return MasterItem::where('vendor_id', $vendor->id)
            ->where('is_active', true)
            ->with(['category', 'primaryImage', 'images'])
            ->withCount('rentalDetails')
            ->orderByDesc('rental_details_count')
            ->latest()
            ->limit(12)
            ->get()
            ->filter(fn (MasterItem $item) => $this->itemImage($item) !== null)
            ->take(3)
            ->values();
    }

    private function itemImage(MasterItem $item): ?string
    {
        return $item->images->pluck('image_url')->filter()->first() ?: ($item->image_url ?: null);
    }
}
