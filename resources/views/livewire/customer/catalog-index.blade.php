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
                <form wire:submit.prevent role="search" class="relative min-w-0 flex-1">
                    <label for="catalog-search" class="sr-only">Cari alat camping</label>
                    <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-darkbrown-400" aria-hidden="true"></i>
                    <input
                        type="search"
                        id="catalog-search"
                        name="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari tenda, kompor, carrier..."
                        autocomplete="off"
                        class="input-tendaku h-12 w-full pl-11 pr-16 text-sm font-medium [&::-webkit-search-cancel-button]:appearance-none"
                    />
                    <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-2">
                        <span
                            wire:loading
                            wire:target="search"
                            class="mr-1 inline-block h-4 w-4 animate-spin rounded-full border-2 border-avocado-600 border-t-transparent"
                            aria-hidden="true"
                        ></span>
                        @if ($hasSearch)
                            <button
                                type="button"
                                wire:click="$set('search', '')"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full text-darkbrown-400 transition hover:bg-wheat-100 hover:text-darkbrown-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500"
                                title="Hapus Pencarian"
                                aria-label="Hapus pencarian"
                            >
                                <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
                            </button>
                        @endif
                    </div>
                </form>

                <div
                    class="relative shrink-0"
                    @keydown.escape.window="filtersOpen = false"
                    @click.outside="filtersOpen = false"
                >
                    <button
                        type="button"
                        @click="filtersOpen = !filtersOpen"
                        :aria-expanded="filtersOpen ? 'true' : 'false'"
                        aria-controls="catalog-advanced-filters"
                        class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl border px-4 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2 sm:w-auto {{ $advancedFilterCount > 0 ? 'border-avocado-800 bg-avocado-700 text-white hover:bg-avocado-800' : 'border-wheat-300 bg-wheat-100 text-darkbrown-800 hover:bg-wheat-200' }}"
                    >
                        <i data-lucide="sliders-horizontal" class="h-4 w-4" aria-hidden="true"></i>
                        @if ($advancedFilterCount > 0)
                            <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-white px-1 text-[10px] font-bold text-avocado-800" aria-hidden="true">
                                {{ $advancedFilterCount }}
                            </span>
                        @endif
                        <i
                            data-lucide="chevron-down"
                            class="h-4 w-4 transition-transform duration-200 motion-reduce:transition-none"
                            :class="filtersOpen ? 'rotate-180' : ''"
                            aria-hidden="true"
                        ></i>
                    </button>

                    <div
                        id="catalog-advanced-filters"
                        role="dialog"
                        aria-label="Filter katalog"
                        x-cloak
                        x-show="filtersOpen"
                        x-transition.origin.top.right.duration.150ms
                        class="absolute right-0 top-full z-40 mt-2 w-full space-y-4 rounded-2xl border border-wheat-200 bg-white p-4 shadow-tendaku-lg sm:w-[36rem] sm:max-w-[calc(100vw-4rem)]"
                    >
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-[11px] font-bold uppercase tracking-[.18em] text-darkbrown-500">Kategori</span>
                                <span class="hidden text-[11px] font-medium text-darkbrown-400 sm:inline">
                                    Pilih satu kategori untuk mempersempit hasil
                                </span>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    wire:click="selectCategory(null)"
                                    aria-pressed="{{ is_null($categoryId) ? 'true' : 'false' }}"
                                    class="inline-flex min-h-[2.5rem] shrink-0 items-center gap-1.5 rounded-xl border px-3.5 py-2 text-xs font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2 {{ is_null($categoryId) ? 'border-avocado-700 bg-avocado-600 text-white shadow-sm' : 'border-wheat-300 bg-white text-darkbrown-700 hover:border-avocado-400 hover:bg-wheat-50' }}"
                                >
                                    <i data-lucide="sparkles" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                    <span>Semua Kategori</span>
                                </button>

                                @foreach ($categories as $cat)
                                    @php
                                        $isActive = $categoryId === $cat->id;
                                        $catIcon = match (strtolower($cat->icon ?? '')) {
                                            'tent' => 'tent',
                                            'moon' => 'moon',
                                            'flame' => 'flame',
                                            'sun', 'flashlight' => 'flashlight',
                                            'bag', 'backpack' => 'backpack',
                                            default => match (true) {
                                                str_contains(strtolower($cat->name), 'tenda') => 'tent',
                                                str_contains(strtolower($cat->name), 'sleep') || str_contains(strtolower($cat->name), 'tidur') => 'moon',
                                                str_contains(strtolower($cat->name), 'masak') || str_contains(strtolower($cat->name), 'cook') => 'flame',
                                                str_contains(strtolower($cat->name), 'lampu') || str_contains(strtolower($cat->name), 'light') => 'flashlight',
                                                str_contains(strtolower($cat->name), 'tas') || str_contains(strtolower($cat->name), 'carrier') || str_contains(strtolower($cat->name), 'bag') => 'backpack',
                                                default => 'tag',
                                            },
                                        };
                                    @endphp

                                    <button
                                        type="button"
                                        wire:click="selectCategory({{ $cat->id }})"
                                        aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                                        class="inline-flex min-h-[2.5rem] shrink-0 items-center gap-1.5 rounded-xl border px-3.5 py-2 text-xs font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2 {{ $isActive ? 'border-avocado-700 bg-avocado-600 text-white shadow-sm' : 'border-wheat-300 bg-white text-darkbrown-700 hover:border-avocado-400 hover:bg-wheat-50' }}"
                                    >
                                        <i data-lucide="{{ $catIcon }}" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                        <span>{{ $cat->name }}</span>
                                        @if (isset($cat->items_count))
                                            <span class="ml-0.5 rounded-full px-1.5 py-0.5 text-[10px] {{ $isActive ? 'bg-avocado-800 text-white' : 'bg-wheat-200 text-darkbrown-800' }}">
                                                {{ $cat->items_count }}
                                            </span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="border-t border-wheat-100 pt-4">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="catalog-vendor" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-darkbrown-500">
                                        Vendor / Toko
                                    </label>
                                    <select
                                        id="catalog-vendor"
                                        wire:model.live="vendorId"
                                        class="input-tendaku h-11 w-full px-3 text-sm font-medium"
                                    >
                                        <option value="">Semua Vendor</option>
                                        @foreach ($vendors as $ven)
                                            <option value="{{ $ven->id }}">{{ $ven->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <span class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-darkbrown-500">
                                        Rentang Harga / Hari
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <div class="relative min-w-0 flex-1">
                                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-darkbrown-400" aria-hidden="true">Rp</span>
                                            <input
                                                type="number"
                                                inputmode="numeric"
                                                min="0"
                                                step="1000"
                                                wire:model.live.debounce.400ms="minPrice"
                                                placeholder="Min"
                                                aria-label="Harga minimum per hari"
                                                class="input-tendaku h-11 w-full pl-9 pr-2 text-sm font-medium"
                                            />
                                        </div>
                                        <span class="text-xs text-darkbrown-400" aria-hidden="true">–</span>
                                        <div class="relative min-w-0 flex-1">
                                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-darkbrown-400" aria-hidden="true">Rp</span>
                                            <input
                                                type="number"
                                                inputmode="numeric"
                                                min="0"
                                                step="1000"
                                                wire:model.live.debounce.400ms="maxPrice"
                                                placeholder="Maks"
                                                aria-label="Harga maksimum per hari"
                                                class="input-tendaku h-11 w-full pl-9 pr-2 text-sm font-medium"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="sm:col-span-2">
                                    <span class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-darkbrown-500">
                                        Tanggal Sewa
                                    </span>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="block min-w-0">
                                            <span class="mb-1 block text-[11px] font-semibold text-darkbrown-600">Mulai</span>
                                            <input
                                                type="date"
                                                wire:model.live="startDate"
                                                min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                                                class="input-tendaku h-11 w-full px-3 text-xs font-medium"
                                            />
                                        </label>
                                        <label class="block min-w-0">
                                            <span class="mb-1 block text-[11px] font-semibold text-darkbrown-600">Selesai</span>
                                            <input
                                                type="date"
                                                wire:model.live="endDate"
                                                min="{{ $startDate ?: \Carbon\Carbon::today()->format('Y-m-d') }}"
                                                class="input-tendaku h-11 w-full px-3 text-xs font-medium"
                                            />
                                        </label>
                                    </div>
                                </div>

                                <label class="flex cursor-pointer items-start justify-between gap-3 rounded-2xl border border-wheat-200 bg-wheat-50/60 p-3.5 transition hover:border-avocado-300 sm:col-span-2">
                                    <span class="flex min-w-0 items-start gap-2.5">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-avocado-700 shadow-sm">
                                            <i data-lucide="calendar-check" class="h-4 w-4" aria-hidden="true"></i>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block text-xs font-bold text-darkbrown-900">Hanya tampilkan yang tersedia</span>
                                            <span class="mt-0.5 block text-[11px] leading-relaxed text-darkbrown-500">
                                                Sembunyikan alat yang sudah penuh pada tanggal sewa yang dipilih.
                                            </span>
                                        </span>
                                    </span>
                                    <input
                                        type="checkbox"
                                        wire:model.live="onlyAvailable"
                                        role="switch"
                                        class="peer sr-only"
                                    />
                                    <span
                                        class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-wheat-300 transition after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition after:content-[''] peer-checked:bg-avocado-600 peer-checked:after:translate-x-5 peer-focus-visible:ring-2 peer-focus-visible:ring-avocado-500 peer-focus-visible:ring-offset-2"
                                        aria-hidden="true"
                                    ></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            {{-- Ringkasan filter aktif --}}
            @if ($hasActiveFilters)
                <div class="flex flex-wrap items-center gap-2 border-t border-wheat-100 pt-4">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-darkbrown-500">Filter aktif</span>

                    @if ($hasSearch)
                        <span class="inline-flex min-h-8 items-center gap-1.5 rounded-full border border-avocado-200 bg-avocado-50 px-2.5 py-1 text-[11px] font-semibold text-avocado-900">
                            <i data-lucide="search" class="h-3 w-3" aria-hidden="true"></i>
                            <span class="max-w-[12rem] truncate">“{{ $search }}”</span>
                            <button
                                type="button"
                                wire:click="$set('search', '')"
                                class="-mr-1 inline-flex h-6 w-6 items-center justify-center rounded-full text-avocado-700 transition hover:bg-avocado-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500"
                                aria-label="Hapus filter pencarian"
                            >
                                <i data-lucide="x" class="h-3 w-3" aria-hidden="true"></i>
                            </button>
                        </span>
                    @endif

                    @if ($hasCategory)
                        <span class="inline-flex min-h-8 items-center gap-1.5 rounded-full border border-avocado-200 bg-avocado-50 px-2.5 py-1 text-[11px] font-semibold text-avocado-900">
                            <i data-lucide="layers" class="h-3 w-3" aria-hidden="true"></i>
                            <span class="max-w-[12rem] truncate">{{ $selectedCategory?->name ?? 'Kategori' }}</span>
                            <button
                                type="button"
                                wire:click="selectCategory(null)"
                                class="-mr-1 inline-flex h-6 w-6 items-center justify-center rounded-full text-avocado-700 transition hover:bg-avocado-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500"
                                aria-label="Hapus filter kategori"
                            >
                                <i data-lucide="x" class="h-3 w-3" aria-hidden="true"></i>
                            </button>
                        </span>
                    @endif

                    @if ($hasVendor)
                        <span class="inline-flex min-h-8 items-center gap-1.5 rounded-full border border-avocado-200 bg-avocado-50 px-2.5 py-1 text-[11px] font-semibold text-avocado-900">
                            <i data-lucide="store" class="h-3 w-3" aria-hidden="true"></i>
                            <span class="max-w-[12rem] truncate">{{ $selectedVendor?->name ?? 'Vendor' }}</span>
                            <button
                                type="button"
                                wire:click="$set('vendorId', null)"
                                class="-mr-1 inline-flex h-6 w-6 items-center justify-center rounded-full text-avocado-700 transition hover:bg-avocado-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500"
                                aria-label="Hapus filter vendor"
                            >
                                <i data-lucide="x" class="h-3 w-3" aria-hidden="true"></i>
                            </button>
                        </span>
                    @endif

                    @if ($hasPrice)
                        <span class="inline-flex min-h-8 items-center gap-1.5 rounded-full border border-avocado-200 bg-avocado-50 px-2.5 py-1 text-[11px] font-semibold text-avocado-900">
                            <i data-lucide="tag" class="h-3 w-3" aria-hidden="true"></i>
                            <span class="max-w-[14rem] truncate">{{ $priceFilterLabel }}</span>
                            <button
                                type="button"
                                wire:click="clearPriceRange"
                                class="-mr-1 inline-flex h-6 w-6 items-center justify-center rounded-full text-avocado-700 transition hover:bg-avocado-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500"
                                aria-label="Hapus filter harga"
                            >
                                <i data-lucide="x" class="h-3 w-3" aria-hidden="true"></i>
                            </button>
                        </span>
                    @endif

                    @if ($hasDates)
                        <span class="inline-flex min-h-8 items-center gap-1.5 rounded-full border border-avocado-200 bg-avocado-50 px-2.5 py-1 text-[11px] font-semibold text-avocado-900">
                            <i data-lucide="calendar" class="h-3 w-3" aria-hidden="true"></i>
                            <span class="max-w-[15rem] truncate">{{ $dateFilterLabel }}</span>
                            <button
                                type="button"
                                wire:click="clearDateRange"
                                class="-mr-1 inline-flex h-6 w-6 items-center justify-center rounded-full text-avocado-700 transition hover:bg-avocado-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500"
                                aria-label="Hapus filter tanggal"
                            >
                                <i data-lucide="x" class="h-3 w-3" aria-hidden="true"></i>
                            </button>
                        </span>
                    @endif

                    @if ($onlyAvailable)
                        <span class="inline-flex min-h-8 items-center gap-1.5 rounded-full border border-avocado-200 bg-avocado-50 px-2.5 py-1 text-[11px] font-semibold text-avocado-900">
                            <i data-lucide="calendar-check" class="h-3 w-3" aria-hidden="true"></i>
                            <span>Hanya stok tersedia</span>
                            <button
                                type="button"
                                wire:click="$set('onlyAvailable', false)"
                                class="-mr-1 inline-flex h-6 w-6 items-center justify-center rounded-full text-avocado-700 transition hover:bg-avocado-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500"
                                aria-label="Hapus filter ketersediaan"
                            >
                                <i data-lucide="x" class="h-3 w-3" aria-hidden="true"></i>
                            </button>
                        </span>
                    @endif

                    @if ($hasSort)
                        <span class="inline-flex min-h-8 items-center gap-1.5 rounded-full border border-avocado-200 bg-avocado-50 px-2.5 py-1 text-[11px] font-semibold text-avocado-900">
                            <i data-lucide="arrow-up-down" class="h-3 w-3" aria-hidden="true"></i>
                            <span>Urutkan: {{ $sortLabels[$sort] ?? 'Terbaru' }}</span>
                            <button
                                type="button"
                                wire:click="$set('sort', 'latest')"
                                class="-mr-1 inline-flex h-6 w-6 items-center justify-center rounded-full text-avocado-700 transition hover:bg-avocado-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500"
                                aria-label="Kembalikan urutan ke terbaru"
                            >
                                <i data-lucide="x" class="h-3 w-3" aria-hidden="true"></i>
                            </button>
                        </span>
                    @endif

                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="ml-auto inline-flex min-h-8 items-center gap-1 rounded-full px-2 text-[11px] font-bold text-darkbrown-500 transition hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400"
                    >
                        <i data-lucide="rotate-ccw" class="h-3 w-3" aria-hidden="true"></i>
                        Hapus semua
                    </button>
                </div>
            @endif
        </div>
    </section>

    @guest
        <section class="flex flex-col gap-3 rounded-2xl border border-avocado-200 bg-avocado-50/80 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-avocado-700 shadow-sm">
                    <i data-lucide="lock" class="h-4 w-4" aria-hidden="true"></i>
                </span>
                <div>
                    <p class="text-xs font-bold text-avocado-900">Keranjang khusus member</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-avocado-800">
                        Masuk atau daftar dulu untuk menambahkan alat ke keranjang dan melanjutkan penyewaan.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 sm:shrink-0">
                <a href="{{ route('login') }}" class="btn-tendaku-secondary min-h-[44px] px-4 text-xs">Masuk</a>
                <a href="{{ route('register') }}" class="btn-tendaku-primary min-h-[44px] px-4 text-xs">Daftar</a>
            </div>
        </section>
    @endguest

    {{-- Header hasil --}}
    <section aria-labelledby="catalog-results-heading" class="space-y-4">
        <div class="flex flex-col gap-3 border-b border-wheat-200 pb-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 id="catalog-results-heading" class="font-heading text-xl font-extrabold tracking-tight text-darkbrown-900">
                        Daftar Peralatan Tersedia
                    </h2>
                    <span wire:loading class="inline-flex animate-pulse items-center gap-1 rounded-full border border-avocado-200 bg-avocado-50 px-2.5 py-0.5 text-[11px] font-semibold text-avocado-800">
                        Memuat data...
                    </span>
                </div>
                <p class="mt-1 text-xs text-darkbrown-600" role="status" aria-live="polite">
                    @if ($items->total() > 0)
                        Menampilkan {{ $items->firstItem() }}–{{ $items->lastItem() }} dari {{ $items->total() }} unit siap sewa untuk camping kamu.
                    @else
                        Tidak ada peralatan yang ditemukan.
                    @endif
                </p>
                <div class="mt-1.5 flex items-start gap-1.5 text-xs text-darkbrown-600">
                    <i data-lucide="calendar" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-avocado-600" aria-hidden="true"></i>
                    <x-stock-period :date-range="$dateRange" />
                </div>
            </div>

            <div class="flex w-full shrink-0 flex-col gap-1.5 sm:w-auto sm:flex-row sm:items-center sm:gap-2">
                <span class="text-[11px] font-bold uppercase tracking-wider text-darkbrown-500">Urutkan</span>
                <div class="relative w-full sm:w-auto">
                    <i data-lucide="arrow-up-down" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-darkbrown-400" aria-hidden="true"></i>
                    <select
                        id="catalog-sort"
                        wire:model.live="sort"
                        aria-label="Urutkan peralatan"
                        class="input-tendaku h-10 w-full pl-9 pr-8 text-xs font-semibold sm:min-w-[10.5rem]"
                    >
                        <option value="latest">Terbaru</option>
                        <option value="price_asc">Harga: Termurah</option>
                        <option value="price_desc">Harga: Termahal</option>
                        <option value="name_asc">Nama (A-Z)</option>
                    </select>
                </div>
            </div>
        </div>

        @if ($onlyAvailable && in_array(null, $dateStockMap, true))
            <div class="flex items-start gap-2.5 rounded-xl border border-goldenrod-200 bg-goldenrod-50 px-3.5 py-3 text-xs text-goldenrod-900" role="status">
                <i data-lucide="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0 text-goldenrod-600" aria-hidden="true"></i>
                <p>Ketersediaan belum dapat diperiksa untuk sebagian peralatan. Nonaktifkan filter untuk memilih jadwal lain.</p>
            </div>
        @endif

        {{-- Grid produk --}}
        @if ($items->count() > 0)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5 lg:grid-cols-3 xl:grid-cols-4" wire:loading.class="opacity-60">
                @foreach ($items as $item)
                    @php
                        $rate = (float) $item->daily_rate;
                        $formattedRate = number_format($rate, 0, ',', '.');
                        $unitCount = $dateStockMap[$item->id] ?? null;
                        $detailUrl = route('customer.product.show', ['id' => $item->id] + ($dateRange ?? ['start_date' => $startDate, 'end_date' => $endDate]));
                        $img = $item->image_url ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80';
                        $vendorName = $item->vendor?->name ?? 'Tendaku Vendor';
                    @endphp

                    <article
                        wire:key="catalog-item-{{ $item->id }}"
                        class="group flex h-full flex-col overflow-hidden rounded-2xl border border-wheat-200/90 bg-white shadow-tendaku-sm transition duration-300 hover:-translate-y-1 hover:border-avocado-300 hover:shadow-tendaku-lg focus-within:ring-2 focus-within:ring-avocado-500/30 motion-reduce:transform-none motion-reduce:transition-none"
                    >
                        <div class="relative aspect-[4/3] overflow-hidden bg-gradient-to-br from-wheat-100 via-white to-sunglow-100">
                            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-1.5 text-darkbrown-500" aria-hidden="true">
                                <i data-lucide="image" class="h-8 w-8"></i>
                                <span class="text-[11px] font-semibold">Foto belum tersedia</span>
                            </div>

                            <img
                                src="{{ $img }}"
                                alt="{{ $item->name }}"
                                width="640"
                                height="480"
                                loading="lazy"
                                decoding="async"
                                onerror="this.style.display='none'"
                                class="relative h-full w-full object-cover transition duration-500 group-hover:scale-105 motion-reduce:transform-none motion-reduce:transition-none"
                            />

                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-darkbrown-900/50 via-darkbrown-900/0 to-transparent" aria-hidden="true"></div>

                            <div class="pointer-events-none absolute inset-x-3 bottom-3 flex items-end justify-between gap-2">
                                <x-stock-status :stock="$unitCount" size="sm" class="shadow-sm" />
                                <span class="inline-flex items-center rounded-full border border-sunglow-200/70 bg-sunglow-300/95 px-2.5 py-1 text-[11px] font-bold text-darkbrown-900 shadow-sm">
                                    Tanpa Deposit
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col gap-2.5 p-4 sm:p-5">
                            @if ($item->vendor)
                                <a
                                    href="{{ route('customer.stores.show', $item->vendor->slug ?? $item->vendor_id) }}"
                                    class="group/store inline-flex min-w-0 items-center gap-1.5 py-1 text-xs font-semibold text-goldenrod-800 transition hover:text-avocado-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2"
                                    title="Kunjungi Toko {{ $vendorName }}"
                                >
                                    <i data-lucide="store" class="h-3.5 w-3.5 shrink-0 text-goldenrod-600 transition-colors group-hover/store:text-avocado-600" aria-hidden="true"></i>
                                    <span class="truncate underline-offset-2 group-hover/store:underline">{{ $vendorName }}</span>
                                </a>
                            @else
                                <div class="flex min-w-0 items-center gap-1.5 py-1 text-xs font-semibold text-goldenrod-800">
                                    <i data-lucide="store" class="h-3.5 w-3.5 shrink-0 text-goldenrod-600" aria-hidden="true"></i>
                                    <span class="truncate">{{ $vendorName }}</span>
                                </div>
                            @endif

                            <h3 class="min-w-0 font-heading text-base font-extrabold leading-snug tracking-tight text-darkbrown-900 transition-colors group-hover:text-avocado-700">
                                <a href="{{ $detailUrl }}" class="line-clamp-2 py-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2">
                                    {{ $item->name }}
                                </a>
                            </h3>

                            <p class="line-clamp-2 text-xs leading-relaxed text-darkbrown-600">
                                {{ $item->description ?? 'Peralatan outdoor kualitas standar pendakian gunung, steril & siap pakai.' }}
                            </p>

                            <div class="mt-auto flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] font-medium text-darkbrown-500">
                                @if ($item->dominant_condition)
                                    <span class="inline-flex items-center gap-1">
                                        <i data-lucide="shield-check" class="h-3 w-3 text-avocado-600" aria-hidden="true"></i>
                                        {{ $item->dominant_condition }}
                                    </span>
                                @endif
                                @if ($item->item_age)
                                    <span class="text-wheat-400" aria-hidden="true">•</span>
                                    <span>{{ $item->item_age }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="border-t border-wheat-100 p-4 pt-3 sm:p-5 sm:pt-3">
                            <div class="flex items-end justify-between gap-3 pb-3">
                                <div class="min-w-0">
                                    <span class="block text-[11px] font-semibold uppercase tracking-wider text-darkbrown-500">Harga sewa</span>
                                    <div class="flex items-baseline gap-1">
                                        <span class="font-heading text-lg font-extrabold tracking-tight text-darkbrown-900 sm:text-xl">Rp {{ $formattedRate }}</span>
                                        <span class="text-[11px] font-semibold text-darkbrown-500">/hari</span>
                                    </div>
                                </div>
                            </div>

                            @if ($unitCount > 0)
                                <div class="flex items-center gap-2">
                                    <a href="{{ $detailUrl }}" class="btn-tendaku-primary min-h-[44px] flex-1 px-3 text-xs">
                                        Lihat Detail
                                    </a>
                                    @auth
                                        <button
                                            type="button"
                                            wire:click.stop="addToCart({{ $item->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="addToCart({{ $item->id }})"
                                            title="Tambah ke Keranjang"
                                            aria-label="Tambah {{ $item->name }} ke keranjang"
                                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border-2 border-avocado-600 text-avocado-700 transition hover:bg-avocado-600 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2 active:scale-95 disabled:cursor-wait disabled:opacity-60 motion-reduce:transform-none"
                                        >
                                            <span wire:loading.remove wire:target="addToCart({{ $item->id }})">
                                                <i data-lucide="shopping-cart" class="h-4 w-4" aria-hidden="true"></i>
                                            </span>
                                            <span wire:loading wire:target="addToCart({{ $item->id }})" class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-avocado-600 border-t-transparent"></span>
                                        </button>
                                    @endauth
                                </div>
                            @else
                                <a href="{{ $detailUrl }}" class="btn-tendaku-secondary min-h-[44px] w-full px-3 text-xs">
                                    Pilih Jadwal Lain
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="pt-2">
                {{ $items->links('vendor.pagination.tendaku') }}
            </div>
        @else
            {{-- Empty state --}}
            <div class="rounded-3xl border border-dashed border-wheat-300 bg-gradient-to-b from-white to-wheat-50/60 px-5 py-12 text-center sm:px-8">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-wheat-100 text-darkbrown-600 shadow-inner">
                    <i data-lucide="search-x" class="h-8 w-8" aria-hidden="true"></i>
                </div>
                <h3 class="mt-4 font-heading text-lg font-bold text-darkbrown-900">Belum ada alat yang cocok</h3>
                <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-darkbrown-600">
                    @if ($hasSearch)
                        Tidak ada hasil untuk “{{ $search }}”. Coba kata kunci lain atau hapus beberapa filter.
                    @elseif ($hasDates && (! $hasStartDate || ! $hasEndDate))
                        Lengkapi tanggal mulai dan selesai untuk memeriksa ketersediaan alat.
                    @elseif ($onlyAvailable)
                        Tidak ada alat yang tersedia pada tanggal yang dipilih. Coba geser tanggal atau nonaktifkan filter ketersediaan.
                    @else
                        Tidak ada alat camping yang cocok dengan filter yang dipilih saat ini.
                    @endif
                </p>

                <div class="mt-6 flex flex-wrap justify-center gap-2">
                    <button type="button" wire:click="resetFilters" class="btn-tendaku-primary min-h-[44px] px-5 text-sm">
                        <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
                        Lihat Semua Peralatan
                    </button>
                    @if ($hasSearch)
                        <button type="button" wire:click="$set('search', '')" class="btn-tendaku-secondary min-h-[44px] px-4 text-sm">
                            Hapus pencarian
                        </button>
                    @endif
                </div>

                @if ($categories->isNotEmpty())
                    <div class="mt-7 border-t border-wheat-200 pt-5">
                        <p class="text-[11px] font-bold uppercase tracking-[.18em] text-darkbrown-500">Coba kategori lain</p>
                        <div class="mt-3 flex flex-wrap justify-center gap-2">
                            @foreach ($categories->take(4) as $cat)
                                <button
                                    type="button"
                                    wire:click="selectCategory({{ $cat->id }})"
                                    class="inline-flex min-h-[2.25rem] items-center gap-1.5 rounded-xl border border-wheat-300 bg-white px-3 py-1.5 text-xs font-semibold text-darkbrown-700 transition hover:border-avocado-400 hover:bg-wheat-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2"
                                >
                                    <i data-lucide="tag" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                    {{ $cat->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </section>
</div>
