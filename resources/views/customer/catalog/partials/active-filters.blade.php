@php
$activeChips = collect([
    ['show' => $hasSearch, 'icon' => 'search', 'label' => '“'.$search.'”', 'max' => 'max-w-[12rem]', 'clear' => "\$set('search', '')", 'aria' => 'Hapus filter pencarian'],
    ['show' => $hasCategory, 'icon' => 'layers', 'label' => $selectedCategory?->name ?? 'Kategori', 'max' => 'max-w-[12rem]', 'clear' => 'selectCategory(null)', 'aria' => 'Hapus filter kategori'],
    ['show' => $hasVendor, 'icon' => 'store', 'label' => $selectedVendor?->name ?? 'Vendor', 'max' => 'max-w-[12rem]', 'clear' => "\$set('vendorId', null)", 'aria' => 'Hapus filter vendor'],
    ['show' => $hasPrice, 'icon' => 'tag', 'label' => $priceFilterLabel, 'max' => 'max-w-[14rem]', 'clear' => 'clearPriceRange', 'aria' => 'Hapus filter harga'],
    ['show' => $hasDates, 'icon' => 'calendar', 'label' => $dateFilterLabel, 'max' => 'max-w-[15rem]', 'clear' => 'clearDateRange', 'aria' => 'Hapus filter tanggal'],
    ['show' => $onlyAvailable, 'icon' => 'calendar-check', 'label' => 'Hanya stok tersedia', 'max' => 'max-w-[15rem]', 'clear' => "\$set('onlyAvailable', false)", 'aria' => 'Hapus filter ketersediaan'],
])->filter(fn (array $chip): bool => $chip['show']);
@endphp

@if ($hasActiveFilters)
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-[11px] font-semibold uppercase tracking-wider text-darkbrown-500">Filter aktif</span>

        @foreach ($activeChips as $chip)
            <span class="inline-flex min-h-7 items-center gap-1.5 rounded-full border border-avocado-200 bg-avocado-50 px-2.5 py-1 text-[11px] font-semibold text-avocado-900">
                <i data-lucide="{{ $chip['icon'] }}" class="h-3 w-3" aria-hidden="true"></i>
                <span class="{{ $chip['max'] }} truncate">{{ $chip['label'] }}</span>
                <button
                    type="button"
                    wire:click="{{ $chip['clear'] }}"
                    class="-mr-1 inline-flex h-11 w-11 sm:h-6 sm:w-6 items-center justify-center rounded-full text-avocado-700 transition hover:bg-avocado-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500"
                    aria-label="{{ $chip['aria'] }}"
                >
                    <i data-lucide="x" class="h-3 w-3" aria-hidden="true"></i>
                </button>
            </span>
        @endforeach

        <button
            type="button"
            wire:click="resetFilters"
            class="ml-auto inline-flex min-h-[44px] sm:min-h-8 items-center gap-1 rounded-full px-2 text-[11px] font-bold text-darkbrown-500 transition hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400"
        >
            <i data-lucide="rotate-ccw" class="h-3 w-3" aria-hidden="true"></i>
            Hapus semua
        </button>
    </div>
@endif
