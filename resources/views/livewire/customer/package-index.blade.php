@php
    $hasSearch = filled($search);
    $hasVendor = $vendorId !== null;
    $hasSort = $sort !== 'latest';
    $hasActiveFilters = $hasSearch || $hasVendor || $hasSort;
@endphp

<div class="space-y-5">
    {{-- Toolbar pencarian & filter (instant, tanpa tombol Terapkan) --}}
    <section aria-labelledby="package-filter-heading">
        <h2 id="package-filter-heading" class="sr-only">Pencarian dan filter paket</h2>

        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:flex lg:items-center">
            <form wire:submit.prevent role="search" class="relative min-w-0 sm:col-span-2 lg:flex-1">
                <label for="package-search" class="sr-only">Cari paket camping</label>
                <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-darkbrown-400" aria-hidden="true"></i>
                <input
                    type="search"
                    id="package-search"
                    name="search"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Cari paket, mis. Paket 2 Hari 1 Malam..."
                    autocomplete="off"
                    class="input-tendaku h-11 w-full pl-11 pr-16 text-sm font-medium [&::-webkit-search-cancel-button]:appearance-none"
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
                            class="inline-flex h-11 w-11 sm:h-8 sm:w-8 items-center justify-center rounded-full text-darkbrown-400 transition hover:bg-wheat-100 hover:text-darkbrown-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500"
                            title="Hapus Pencarian"
                            aria-label="Hapus pencarian"
                        >
                            <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
                        </button>
                    @endif
                </div>
            </form>

            <div class="min-w-0 lg:w-48 lg:shrink-0">
                <x-dropdown-select
                    wire:model.live="vendorId"
                    size="lg"
                    label="Vendor"
                    :options="collect($vendors)->pluck('name', 'id')->prepend('Semua Vendor', '')->all()"
                />
            </div>

            <div class="min-w-0 lg:w-44 lg:shrink-0">
                <x-dropdown-select
                    wire:model.live="sort"
                    size="lg"
                    align="right"
                    label="Urutkan paket"
                    :options="['latest' => 'Terbaru', 'price_asc' => 'Harga: Termurah', 'price_desc' => 'Harga: Termahal', 'duration_asc' => 'Durasi Terpendek']"
                />
            </div>

            @if ($hasActiveFilters)
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="btn-tendaku-secondary h-11 px-4 text-sm sm:col-span-2 lg:col-span-1 lg:shrink-0"
                    title="Reset filter"
                >
                    <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
                </button>
            @endif
        </div>
    </section>

    {{-- Hasil --}}
    <section aria-labelledby="package-results-heading" class="space-y-3">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h2 id="package-results-heading" class="font-display font-normal text-2xl text-darkbrown-900">
                    Daftar Paket Tersedia
                </h2>
                <span wire:loading class="animate-pulse text-xs font-semibold text-avocado-700">
                                    <i data-lucide="loader" class="h-3.5 w-3.5" aria-hidden="true"></i>...
                </span>
            </div>
            <p class="mt-1 text-xs text-darkbrown-600" role="status" aria-live="polite">
                @if ($packages->total() > 0)
                    Menampilkan {{ $packages->firstItem() }}–{{ $packages->lastItem() }} dari {{ $packages->total() }} paket siap sewa untuk camping kamu.
                @else
                    Tidak ada paket yang ditemukan.
                @endif
            </p>
        </div>

        @if ($packages->count() > 0)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" wire:loading.class="opacity-60">
                @foreach ($packages as $package)
                    @php
                        $image = $package->image_url
                            ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80';
                        $savings = (float) $package->savings;
                        $price = (float) $package->price;
                        $normalPrice = (float) $package->normal_price;
                        $vendorName = $package->vendor?->name ?? 'Tendaku Vendor';
                        $detailUrl = route('customer.packages.show', $package);
                    @endphp

                    <article
                        wire:key="package-{{ $package->id }}"
                        class="group flex h-full flex-col overflow-hidden rounded-2xl border border-wheat-200/90 bg-white transition duration-300 hover:-translate-y-1 hover:border-avocado-300 hover:shadow-tendaku-lg focus-within:ring-2 focus-within:ring-avocado-500/30 motion-reduce:transform-none motion-reduce:transition-none"
                    >
                        {{-- Foto --}}
                        <div class="relative aspect-[4/3] overflow-hidden bg-gradient-to-br from-wheat-100 via-white to-sunglow-100">
                            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-1.5 text-darkbrown-500" aria-hidden="true">
                                <i data-lucide="image" class="h-8 w-8"></i>
                                <span class="text-xs font-semibold">Foto belum tersedia</span>
                            </div>

                            <img
                                src="{{ $image }}"
                                alt="{{ $package->name }}"
                                width="640"
                                height="480"
                                loading="lazy"
                                decoding="async"
                                onerror="this.style.display='none'"
                                class="relative h-full w-full object-cover transition duration-500 group-hover:scale-105 motion-reduce:transform-none motion-reduce:transition-none"
                            />

                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-darkbrown-900/50 via-darkbrown-900/0 to-transparent" aria-hidden="true"></div>

                            <div class="pointer-events-none absolute inset-x-3 bottom-3 flex items-end justify-between gap-2">
                                <x-badge variant="dark" size="sm" class="shadow-sm">
                                    <i data-lucide="calendar-days" class="h-3 w-3" aria-hidden="true"></i>
                                    {{ $package->duration_days }} Hari
                                </x-badge>

                                @if ($savings > 0)
                                    <x-badge variant="sunglow" size="sm" class="shadow-sm">
                                        Hemat Rp {{ number_format($savings, 0, ',', '.') }}/hari
                                    </x-badge>
                                @endif
                            </div>
                        </div>

                        {{-- Info --}}
                        <div class="flex flex-1 flex-col gap-2.5 p-4">
                            @if ($package->vendor)
                                <a
                                    href="{{ route('customer.stores.show', $package->vendor->slug ?? $package->vendor_id) }}"
                                    class="group/store inline-flex min-h-[44px] min-w-0 items-center gap-1.5 py-2.5 sm:min-h-0 sm:py-1 text-xs font-semibold text-goldenrod-800 transition hover:text-avocado-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2"
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
                                <a href="{{ $detailUrl }}" class="line-clamp-2 min-h-[44px] py-2 sm:min-h-0 sm:py-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2">
                                    {{ $package->name }}
                                </a>
                            </h3>

                            <p class="line-clamp-2 text-xs leading-relaxed text-darkbrown-600">
                                {{ $package->description ?: 'Paket camping lengkap, siap pakai untuk petualangan akhir pekan Anda.' }}
                            </p>

                            <div class="mt-auto flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-medium text-darkbrown-500">
                                <span class="inline-flex items-center gap-1">
                                    <i data-lucide="package" class="h-3 w-3 text-avocado-600" aria-hidden="true"></i>
                                    {{ $package->total_items_count }} unit
                                </span>
                                @if ($package->max_people)
                                    <span class="text-wheat-400" aria-hidden="true">•</span>
                                    <span class="inline-flex items-center gap-1">
                                        <i data-lucide="users" class="h-3 w-3 text-avocado-600" aria-hidden="true"></i>
                                        {{ $package->max_people }} orang
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Harga & aksi --}}
                        <div class="border-t border-wheat-100 p-4 pt-3">
                            <div class="flex items-end justify-between gap-3 pb-3">
                                <div class="min-w-0">
                                    <span class="block text-xs font-semibold uppercase tracking-wider text-darkbrown-500">Harga paket</span>
                                    <div class="flex flex-wrap items-baseline gap-x-1.5">
                                        <div class="flex items-baseline gap-1">
                                            <span class="font-heading text-lg font-extrabold tracking-tight text-darkbrown-900 sm:text-xl">Rp {{ number_format($price, 0, ',', '.') }}</span>
                                            <span class="text-xs font-semibold text-darkbrown-500">/paket</span>
                                        </div>
                                        @if ($normalPrice > $price)
                                            <span class="text-xs font-medium text-darkbrown-500 line-through">
                                                Rp {{ number_format($normalPrice, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <a href="{{ $detailUrl }}" class="btn-tendaku-primary min-h-[44px] w-full px-3 text-xs">
                                Lihat Detail
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="pt-2">
                {{-- Pakai pagination gaya katalog bila view-nya tersedia; jika tidak, fallback ke default --}}
                @if (view()->exists('vendor.pagination.tendaku'))
                    {{ $packages->links('vendor.pagination.tendaku') }}
                @else
                    {{ $packages->links() }}
                @endif
            </div>
        @else
            {{-- Empty state --}}
            <div class="rounded-2xl border border-dashed border-wheat-300 px-5 py-10 text-center sm:px-8">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-wheat-100 text-darkbrown-600">
                    <i data-lucide="search-x" class="h-8 w-8" aria-hidden="true"></i>
                </div>
                <h3 class="mt-4 font-heading text-lg font-bold text-darkbrown-900">
                    {{ ($hasSearch || $hasVendor) ? 'Belum ada paket yang cocok' : 'Belum ada paket camping' }}
                </h3>
                <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-darkbrown-600">
                    @if ($hasSearch || $hasVendor)
                        Tidak ada paket yang cocok dengan filter Anda. Coba ubah kata kunci atau reset filter.
                    @else
                        Vendor belum menambahkan paket camping. Sementara itu, Anda tetap bisa menyewa alat satuan di katalog.
                    @endif
                </p>

                <div class="mt-6 flex flex-wrap justify-center gap-2">
                    @if ($hasSearch || $hasVendor)
                        <button type="button" wire:click="resetFilters" class="btn-tendaku-primary min-h-[44px] px-5 text-sm">
                            <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
                            Lihat Semua Paket
                        </button>
                        <a href="{{ route('customer.catalog.index') }}" class="btn-tendaku-secondary min-h-[44px] px-4 text-sm">
                            Lihat Katalog Alat
                        </a>
                    @else
                        <a href="{{ route('customer.catalog.index') }}" class="btn-tendaku-primary min-h-[44px] px-5 text-sm">
                            Lihat Katalog Alat
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </section>
</div>
