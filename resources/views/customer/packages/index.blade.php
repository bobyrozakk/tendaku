<x-layouts.customer>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between lg:gap-10">
            <div class="min-w-0 max-w-2xl">
                <h1 class="font-heading text-2xl font-extrabold tracking-tight text-darkbrown-900 sm:text-3xl">
                    Paket Camping
                </h1>

                <p class="mt-0.5 text-sm font-semibold text-avocado-800 sm:text-base">
                    Perlengkapan lengkap dalam satu paket hemat.
                </p>

                <p class="mt-2 max-w-xl text-sm leading-relaxed text-darkbrown-600">
                    Pilih paket dari vendor terverifikasi, tentukan tanggal, lalu langsung sewa. Semua unit dicek sebelum diserahkan, dengan jaminan KTP tanpa deposit uang.
                </p>
            </div>
        </div>
    </x-slot>

    @php
        $hasSearch = filled($search);
        $hasVendor = filled($vendorId);
        $hasSort = $sort && $sort !== 'latest';
        $hasActiveFilters = $hasSearch || $hasVendor || $hasSort;
    @endphp

    <div class="space-y-5">
        {{-- Toolbar pencarian & filter --}}
        <section aria-labelledby="package-filter-heading">
            <h2 id="package-filter-heading" class="sr-only">Pencarian dan filter paket</h2>

            <form method="GET" action="{{ route('customer.packages.index') }}" role="search"
                  class="grid grid-cols-2 gap-2.5 lg:flex lg:items-center">
                <div class="relative col-span-2 min-w-0 lg:flex-1">
                    <label for="search" class="sr-only">Cari paket camping</label>
                    <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-darkbrown-400" aria-hidden="true"></i>
                    <input
                        type="search"
                        id="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Cari paket, mis. Paket 2 Hari 1 Malam..."
                        autocomplete="off"
                        class="input-tendaku h-11 w-full pl-11 pr-4 text-sm font-medium [&::-webkit-search-cancel-button]:appearance-none"
                    />
                </div>

                <div class="min-w-0 lg:w-48 lg:shrink-0">
                    <label for="vendor_id" class="sr-only">Vendor</label>
                    <select id="vendor_id" name="vendor_id"
                            class="input-tendaku h-11 w-full px-3 text-sm font-medium">
                        <option value="">Semua Vendor</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected((string) $vendorId === (string) $vendor->id)>
                                {{ $vendor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="min-w-0 lg:w-44 lg:shrink-0">
                    <label for="sort" class="sr-only">Urutkan paket</label>
                    <select id="sort" name="sort"
                            class="input-tendaku h-11 w-full px-3 text-sm font-medium">
                        <option value="latest" @selected($sort === 'latest')>Terbaru</option>
                        <option value="price_asc" @selected($sort === 'price_asc')>Harga: Termurah</option>
                        <option value="price_desc" @selected($sort === 'price_desc')>Harga: Termahal</option>
                        <option value="duration_asc" @selected($sort === 'duration_asc')>Durasi Terpendek</option>
                    </select>
                </div>

                <div class="col-span-2 flex items-center gap-2 lg:col-span-1 lg:shrink-0">
                    <button type="submit" class="btn-tendaku-primary h-11 flex-1 px-5 text-sm lg:flex-none">
                        Terapkan
                    </button>
                    @if ($hasActiveFilters)
                        <a href="{{ route('customer.packages.index') }}"
                           class="btn-tendaku-secondary h-11 px-4 text-sm"
                           title="Reset filter">
                            <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </section>

        {{-- Hasil --}}
        <section aria-labelledby="package-results-heading" class="space-y-3">
            <div class="min-w-0">
                <h2 id="package-results-heading" class="font-heading text-xl font-extrabold tracking-tight text-darkbrown-900">
                    Daftar Paket Tersedia
                </h2>
                <p class="mt-1 text-xs text-darkbrown-600" role="status" aria-live="polite">
                    @if ($packages->total() > 0)
                        Menampilkan {{ $packages->firstItem() }}–{{ $packages->lastItem() }} dari {{ $packages->total() }} paket siap sewa untuk camping kamu.
                    @else
                        Tidak ada paket yang ditemukan.
                    @endif
                </p>
            </div>

            @if ($packages->count() > 0)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
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
                            class="group flex h-full flex-col overflow-hidden rounded-2xl border border-wheat-200/90 bg-white transition duration-300 hover:-translate-y-1 hover:border-avocado-300 hover:shadow-tendaku-lg focus-within:ring-2 focus-within:ring-avocado-500/30 motion-reduce:transform-none motion-reduce:transition-none"
                        >
                            {{-- Foto --}}
                            <div class="relative aspect-[4/3] overflow-hidden bg-gradient-to-br from-wheat-100 via-white to-sunglow-100">
                                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-1.5 text-darkbrown-500" aria-hidden="true">
                                    <i data-lucide="image" class="h-8 w-8"></i>
                                    <span class="text-[11px] font-semibold">Foto belum tersedia</span>
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
                                            Hemat Rp {{ number_format($savings, 0, ',', '.') }}
                                        </x-badge>
                                    @endif
                                </div>
                            </div>

                            {{-- Info --}}
                            <div class="flex flex-1 flex-col gap-2.5 p-4">
                                @if ($package->vendor)
                                    <a
                                        href="{{ route('customer.stores.show', $package->vendor->slug ?? $package->vendor_id) }}"
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
                                        {{ $package->name }}
                                    </a>
                                </h3>

                                <p class="line-clamp-2 text-xs leading-relaxed text-darkbrown-600">
                                    {{ $package->description ?: 'Paket camping lengkap, siap pakai untuk petualangan akhir pekan Anda.' }}
                                </p>

                                <div class="mt-auto flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] font-medium text-darkbrown-500">
                                    <span class="inline-flex items-center gap-1">
                                        <i data-lucide="package" class="h-3 w-3 text-avocado-600" aria-hidden="true"></i>
                                        {{ $package->total_items_count }} item
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
                                        <span class="block text-[11px] font-semibold uppercase tracking-wider text-darkbrown-500">Harga paket</span>
                                        <div class="flex flex-wrap items-baseline gap-x-1.5">
                                            <div class="flex items-baseline gap-1">
                                                <span class="font-heading text-lg font-extrabold tracking-tight text-darkbrown-900 sm:text-xl">Rp {{ number_format($price, 0, ',', '.') }}</span>
                                                <span class="text-[11px] font-semibold text-darkbrown-500">/paket</span>
                                            </div>
                                            @if ($normalPrice > $price)
                                                <span class="text-[11px] font-medium text-darkbrown-400 line-through">
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
                            <a href="{{ route('customer.packages.index') }}" class="btn-tendaku-primary min-h-[44px] px-5 text-sm">
                                <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
                                Lihat Semua Paket
                            </a>
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
</x-layouts.customer>
