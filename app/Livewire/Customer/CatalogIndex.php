<?php

namespace App\Livewire\Customer;

use App\Models\ItemCategory;
use App\Models\MasterItem;
use App\Models\Vendor;
use App\Services\AvailabilityService;
use App\Services\CartService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CatalogIndex extends Component
{
    use WithPagination;

    #[Url(as: 'search', history: true, keep: false)]
    public string $search = '';

    #[Url(as: 'category_id', history: true, keep: false)]
    public ?int $categoryId = null;

    #[Url(as: 'vendor_id', history: true, keep: false)]
    public ?int $vendorId = null;

    #[Url(as: 'min_price', history: true, keep: false)]
    public ?string $minPrice = null;

    #[Url(as: 'max_price', history: true, keep: false)]
    public ?string $maxPrice = null;

    #[Url(as: 'sort', history: true, keep: false)]
    public string $sort = 'latest';

    #[Url(as: 'start_date', history: true, keep: false)]
    public ?string $startDate = null;

    #[Url(as: 'end_date', history: true, keep: false)]
    public ?string $endDate = null;

    #[Url(as: 'available_only', history: true, keep: false)]
    public bool $onlyAvailable = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatedVendorId(): void
    {
        $this->resetPage();
    }

    public function updatedMinPrice(): void
    {
        $this->resetPage();
    }

    public function updatedMaxPrice(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    public function updatedOnlyAvailable(): void
    {
        $this->resetPage();
    }

    public function selectCategory(?int $id = null): void
    {
        $this->categoryId = ($this->categoryId === $id) ? null : $id;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'categoryId',
            'vendorId',
            'minPrice',
            'maxPrice',
            'sort',
            'startDate',
            'endDate',
            'onlyAvailable',
        ]);
        $this->sort = 'latest';
        $this->onlyAvailable = false;
        $this->resetPage();
    }

    /**
     * Tambah item langsung ke keranjang dari card katalog.
     * Menerapkan aturan:
     * 1. 1 keranjang hanya boleh dari 1 vendor toko yang sama.
     * 2. Periksa ketersediaan stok fisik sebelum masuk keranjang.
     * 3. Update counter keranjang di navbar dan trigger toast notifikasi.
     */
    public function addToCart(int $masterItemId, AvailabilityService $availabilityService): void
    {
        if (Auth::guest()) {
            $this->redirectRoute('login');

            return;
        }

        $masterItem = MasterItem::with(['vendor', 'category'])->findOrFail($masterItemId);

        $cartService = app(CartService::class);
        $user = Auth::user();
        $existingCart = $cartService->get($user);

        // Validasi aturan: 1 keranjang hanya boleh dari 1 vendor toko
        if (
            is_array($existingCart)
            && ! empty($existingCart['master_item_id'])
            && isset($existingCart['vendor_id'])
            && (int) $existingCart['vendor_id'] !== (int) $masterItem->vendor_id
        ) {
            $existingVendor = $existingCart['vendor_name'] ?? 'toko sebelumnya';
            $this->dispatch('notify', [
                'type' => 'warning',
                'title' => 'Keranjang Beda Toko',
                'message' => "Keranjangmu masih berisi produk dari {$existingVendor}. Sewa harus dari 1 vendor yang sama. Selesaikan atau kosongkan keranjang terlebih dahulu.",
            ]);

            return;
        }

        // Tentukan tanggal sewa (dari filter tanggal aktif atau default besok - lusa)
        $pickupDate = (! empty($this->startDate) && Carbon::parse($this->startDate)->gte(Carbon::today()))
            ? $this->startDate
            : Carbon::tomorrow()->toDateString();

        $returnDate = (! empty($this->endDate) && Carbon::parse($this->endDate)->gte(Carbon::parse($pickupDate)))
            ? $this->endDate
            : Carbon::parse($pickupDate)->addDay()->toDateString();

        $pickupCarbon = Carbon::parse($pickupDate);
        $returnCarbon = Carbon::parse($returnDate);
        $durationDays = max(1, (int) $pickupCarbon->diffInDays($returnCarbon));

        // Cek ketersediaan stok
        $availableStock = $availabilityService->checkAvailability($masterItem->id, $pickupDate, $returnDate);
        if ($availableStock < 1) {
            $this->dispatch('notify', [
                'type' => 'error',
                'title' => 'Stok Habis',
                'message' => "Maaf, stok {$masterItem->name} sedang tidak tersedia untuk tanggal yang dipilih.",
            ]);

            return;
        }

        $totalPrice = (int) $masterItem->daily_rate * $durationDays;

        $cartData = [
            'master_item_id' => $masterItem->id,
            'product_id' => $masterItem->id,
            'product_name' => $masterItem->name,
            'product_image' => $masterItem->image_url,
            'daily_rate' => (int) $masterItem->daily_rate,
            'quantity' => 1,
            'qty' => 1,
            'pickup_date' => $pickupDate,
            'pickup_time' => '10:00',
            'return_date' => $returnDate,
            'return_time' => '16:00',
            'duration_days' => $durationDays,
            'total_days' => $durationDays,
            'total_price' => $totalPrice,
            'vendor_id' => $masterItem->vendor_id,
            'vendor_name' => $masterItem->vendor?->name ?? 'Vendor',
            'category_name' => $masterItem->category?->name,
        ];

        $result = $cartService->addItem($cartData, $user);

        if ($result['status'] === 'diff_vendor') {
            $existingVendor = $result['existing_vendor'] ?? 'toko sebelumnya';
            $this->dispatch('notify', [
                'type' => 'warning',
                'title' => 'Keranjang Beda Toko',
                'message' => "Keranjangmu masih berisi produk dari {$existingVendor}. Sewa harus dari 1 vendor yang sama. Selesaikan atau kosongkan keranjang terlebih dahulu.",
            ]);

            return;
        }

        $totalCount = $result['total_count'] ?? 1;

        // Update badge keranjang di navbar dan tampilkan notifikasi sukses
        $this->dispatch('cart-updated', count: $totalCount);

        $this->dispatch('notify', [
            'type' => 'success',
            'title' => 'Masuk Keranjang! 🛒',
            'message' => "{$masterItem->name} berhasil ditambahkan ke keranjang.",
        ]);
    }

    public function render(AvailabilityService $availabilityService): View
    {
        $categories = ItemCategory::withCount(['items' => function ($query) {
            $query->publicCatalog();
        }])->get();

        $vendors = Vendor::whereIn('status', ['active', 'approved'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $itemsQuery = MasterItem::publicCatalog()
            ->with(['category', 'vendor'])
            ->withCount(['units' => function ($query) {
                $query->whereNotIn('status', ['maintenance', 'retired'])
                    ->whereNotIn('condition', ['damaged', 'lost']);
            }]);

        $trimmedSearch = trim($this->search);
        if ($trimmedSearch !== '') {
            $itemsQuery->where(function ($query) use ($trimmedSearch) {
                $query->where('name', 'like', "%{$trimmedSearch}%")
                    ->orWhere('description', 'like', "%{$trimmedSearch}%");
            });
        }

        if ($this->categoryId) {
            $itemsQuery->where('category_id', $this->categoryId);
        }

        if ($this->vendorId) {
            $itemsQuery->where('vendor_id', $this->vendorId);
        }

        if ($this->minPrice !== null && is_numeric($this->minPrice)) {
            $itemsQuery->where('daily_rate', '>=', (float) $this->minPrice);
        }

        if ($this->maxPrice !== null && is_numeric($this->maxPrice)) {
            $itemsQuery->where('daily_rate', '<=', (float) $this->maxPrice);
        }

        match ($this->sort) {
            'price_asc' => $itemsQuery->orderBy('daily_rate', 'asc'),
            'price_desc' => $itemsQuery->orderBy('daily_rate', 'desc'),
            'name_asc' => $itemsQuery->orderBy('name', 'asc'),
            default => $itemsQuery->latest(),
        };

        $hasValidDateRange = false;
        if (! empty($this->startDate) && ! empty($this->endDate)) {
            try {
                $sDate = Carbon::parse($this->startDate);
                $eDate = Carbon::parse($this->endDate);
                if ($eDate->gte($sDate) && $sDate->gte(Carbon::today())) {
                    $hasValidDateRange = true;
                }
            } catch (\Throwable) {
                // Ignore parsing errors
            }
        }

        // Jika filter "hanya yang tersedia" dicentang tanpa filter tanggal, pastikan unit fisik > 0
        if ($this->onlyAvailable && ! $hasValidDateRange) {
            $itemsQuery->has('units');
        }

        $items = $itemsQuery->paginate(12);

        $dateStockMap = [];
        if ($hasValidDateRange) {
            foreach ($items->items() as $item) {
                $dateStockMap[$item->id] = $availabilityService->checkAvailability(
                    $item->id,
                    $this->startDate,
                    $this->endDate
                );
            }

            // Jika user memilih filter tanggal dan juga mencentang "hanya yang tersedia"
            if ($this->onlyAvailable) {
                $filteredItems = $items->getCollection()->filter(function ($item) use ($dateStockMap) {
                    return ($dateStockMap[$item->id] ?? 0) > 0;
                });
                $items->setCollection($filteredItems->values());
            }
        }

        return view('livewire.customer.catalog-index', [
            'categories' => $categories,
            'vendors' => $vendors,
            'items' => $items,
            'dateStockMap' => $dateStockMap,
        ]);
    }
}
