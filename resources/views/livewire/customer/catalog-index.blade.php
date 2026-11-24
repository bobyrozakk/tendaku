@php
    $hasSearch = filled($search);
    $hasCategory = $categoryId !== null;
    $hasVendor = $vendorId !== null;
    $hasMinPrice = filled($minPrice);
    $hasMaxPrice = filled($maxPrice);
    $hasPrice = $hasMinPrice || $hasMaxPrice;
    $hasStartDate = filled($startDate);
    $hasEndDate = filled($endDate);
    $hasDates = $hasStartDate || $hasEndDate;
    $hasSort = $sort !== 'latest';
    $hasActiveFilters = $hasSearch || $hasCategory || $hasVendor || $hasPrice || $hasDates || $onlyAvailable || $hasSort;
    $hasAdvancedFilters = $hasCategory || $hasVendor || $hasPrice || $hasDates || $onlyAvailable;

    $advancedFilterCount = collect([
        $hasCategory,
        $hasVendor,
        $hasPrice,
        $hasDates,
        $onlyAvailable,
    ])->filter()->count();

    $selectedCategory = $hasCategory ? $categories->firstWhere('id', $categoryId) : null;
    $selectedVendor = $hasVendor ? $vendors->firstWhere('id', $vendorId) : null;

    $sortLabels = [
        'latest' => 'Terbaru',
        'price_asc' => 'Harga termurah',
        'price_desc' => 'Harga termahal',
        'name_asc' => 'Nama A-Z',
    ];

    $formatRupiah = static fn (?string $value): string => filled($value)
        ? 'Rp '.number_format((float) $value, 0, ',', '.')
        : '';

    $formatDate = static function (?string $value): string {
        if (! filled($value)) {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable) {
            return $value;
        }
    };

    $priceFilterLabel = match (true) {
        $hasMinPrice && $hasMaxPrice => $formatRupiah($minPrice).' – '.$formatRupiah($maxPrice),
        $hasMinPrice => 'Mulai '.$formatRupiah($minPrice),
        $hasMaxPrice => 'Sampai '.$formatRupiah($maxPrice),
        default => '',
    };

    $dateFilterLabel = match (true) {
        $hasStartDate && $hasEndDate => $formatDate($startDate).' – '.$formatDate($endDate),
        $hasStartDate => 'Mulai '.$formatDate($startDate),
        $hasEndDate => 'Sampai '.$formatDate($endDate),
        default => '',
    };
@endphp

<div
    class="space-y-6"
    x-data="{ filtersOpen: false }"
>
    {{-- Panel pencarian & filter --}}
    <section
        aria-labelledby="catalog-filter-heading"
        class="relative rounded-3xl border border-wheat-200 bg-white shadow-tendaku-sm"
    >
        <div class="absolute inset-x-0 top-0 h-1 rounded-t-3xl bg-gradient-to-r from-avocado-500 via-sunglow-300 to-goldenrod-400" aria-hidden="true"></div>
        <h2 id="catalog-filter-heading" class="sr-only">Pencarian dan filter katalog</h2>

        <div class="space-y-4 p-4 pt-5 sm:p-5 sm:pt-6">
            {{-- Pencarian --}}
            <div class="flex flex-col gap-2.5 sm:flex-row">
                @include('customer.catalog.partials.search-bar')

                @include('customer.catalog.partials.filter-dropdown')
            </div>

            {{-- Ringkasan filter aktif --}}
            @include('customer.catalog.partials.active-filters')
        </div>
    </section>

    @guest
        @include('customer.catalog.partials.guest-notice')
    @endguest

    {{-- Header hasil --}}
    <section aria-labelledby="catalog-results-heading" class="space-y-4">
        @include('customer.catalog.partials.results-header')

        @include('customer.catalog.partials.availability-warning')

        {{-- Grid produk --}}
        @if ($items->count() > 0)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3 xl:grid-cols-4" wire:loading.class="opacity-60">
                @foreach ($items as $item)
                    @include('customer.catalog.partials.product-card')
                @endforeach
            </div>

            <div class="pt-2">
                {{ $items->links('vendor.pagination.tendaku') }}
            </div>
        @else
            {{-- Empty state --}}
            @include('customer.catalog.partials.empty-state')
        @endif
    </section>
</div>
