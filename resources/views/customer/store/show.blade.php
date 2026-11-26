<x-layouts.customer>
    <x-slot name="title">{{ $vendor->name }} - Profil Toko Rental Alat Camping Tendaku</x-slot>

    @php
        $vendorInitials = collect(explode(' ', $vendor->name))
            ->map(fn($part) => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->join('');
        $joinedDate = $vendor->approved_at
            ? $vendor->approved_at->translatedFormat('d M Y')
            : ($vendor->created_at ? $vendor->created_at->translatedFormat('d M Y') : 'Mitra Terpercaya');

        $slug = $vendor->slug ?? $vendor->id;

        // URL halaman ini dengan query yang sedang aktif; nilai null/kosong pada $overrides menghapus parameter.
        $storeUrl = function (array $overrides = []) use ($slug) {
            $query = array_merge(request()->except('page'), $overrides);

            return route('customer.stores.show', ['vendor' => $slug] + array_filter(
                $query,
                fn ($value) => $value !== null && $value !== '' && $value !== false
            ));
        };

        $imageOf = fn ($item) => $item->images->pluck('image_url')->filter()->first() ?: ($item->image_url ?: null);

        $isPackages = $tab === 'packages';
        $resultTotal = $isPackages ? $packages->total() : $items->total();
        $hasFilters = $search !== '' || $categoryId || $price || $inStock;
        $activeFilterCount = (int) ($search !== '') + (int) (bool) $categoryId + (int) (bool) $price + (int) $inStock;
        $resetUrl = route('customer.stores.show', ['vendor' => $slug] + ($isPackages ? ['tab' => 'packages'] : []));
        $maxBucket = max(1, ...array_values($priceCounts));

        // Posisi tiga foto pada visual header: tengah (utama), kiri, kanan.
        $tilePositions = [
            'left-1/2 top-0 z-10 w-44 -translate-x-1/2 aspect-[4/5] sm:w-52',
            'left-0 top-12 z-0 w-32 -rotate-6 aspect-[3/4] sm:w-40',
            'right-0 top-16 z-0 w-32 rotate-6 aspect-[3/4] sm:w-40',
        ];
        $featured = $spotlightItems->first();
    @endphp

    @guest
        <!-- Pop-up Login untuk Melanjutkan (Khusus Pengunjung Belum Login) -->
        <div x-data="{ showGuestLoginModal: {{ request()->has('skip_modal') ? 'false' : 'true' }} }"
             x-show="showGuestLoginModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="modal-store-login-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Backdrop Overlay Gelap Transparan -->
                <div x-show="showGuestLoginModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 transition-opacity bg-darkbrown-950/75 backdrop-blur-sm"
                     @click="showGuestLoginModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal Dialog Box -->
                <div x-show="showGuestLoginModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block w-full max-w-sm sm:max-w-md p-6 sm:p-8 my-8 text-center align-middle transition-all transform bg-white shadow-2xl rounded-3xl border border-wheat-200 sm:my-8 relative overflow-hidden">

                    <!-- Aksen Garis Emas di Atas -->
                    <div class="absolute top-0 inset-x-0 h-2 bg-gradient-to-r from-sunglow-300 via-avocado-500 to-goldenrod-400"></div>

                    <!-- Tombol Tutup X -->
                    <button type="button"
                            @click="showGuestLoginModal = false"
                            class="absolute top-4 right-4 text-darkbrown-400 hover:text-darkbrown-700 p-1.5 rounded-xl hover:bg-wheat-100 transition"
                            aria-label="Tutup modal">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>

                    <div class="w-16 h-16 mx-auto rounded-2xl bg-sunglow-100 text-goldenrod-800 flex items-center justify-center shadow-inner mt-1 mb-4">
                        <i data-lucide="store" class="w-8 h-8 text-goldenrod-700"></i>
                    </div>

                    <h3 id="modal-store-login-title" class="text-xl font-extrabold text-darkbrown-900 font-heading">
                        Lanjut sebagai Tamu atau Masuk
                    </h3>

                    <p class="text-xs sm:text-sm text-darkbrown-600 mt-2 leading-relaxed">
                        Anda bisa checkout langsung tanpa membuat akun di <strong>{{ $vendor->name }}</strong>. Masuk jika ingin menyimpan riwayat sewa dan memakai keranjang untuk booking berikutnya.
                    </p>

                    <div class="mt-6 flex flex-col gap-2.5">
                        <a href="{{ route('login') }}"
                           class="btn-tendaku-primary w-full py-3 px-4 rounded-xl text-xs sm:text-sm font-bold text-center shadow-md hover:shadow-lg transition flex items-center justify-center gap-2">
                            <i data-lucide="log-in" class="w-4 h-4"></i>
                            <span>Masuk</span>
                        </a>

                        <button type="button"
                                @click="showGuestLoginModal = false"
                                class="btn-tendaku-secondary w-full py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold text-center text-darkbrown-700 hover:bg-wheat-100 transition">
                            Lanjut Tanpa Akun
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endguest

    <div class="space-y-8">
        <!-- 1. HEADER TOKO: judul besar, info vendor, dan kolase alat pilihan -->
        <section class="relative isolate overflow-hidden rounded-[2rem] border border-wheat-200/80 bg-gradient-to-br from-wheat-100 via-[#FDFBF7] to-sunglow-100 px-5 py-6 shadow-tendaku-sm sm:px-8 sm:py-8 lg:px-10">
            <div class="pointer-events-none absolute -right-24 -top-24 -z-10 h-80 w-80 rounded-full bg-sunglow-300/30 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-28 -left-16 -z-10 h-72 w-72 rounded-full bg-avocado-300/20 blur-3xl" aria-hidden="true"></div>

            <!-- Breadcrumb -->
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-semibold text-darkbrown-500">
                <a href="{{ route('home') }}" class="transition hover:text-avocado-700">Beranda</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('customer.catalog.index') }}" class="transition hover:text-avocado-700">Katalog</a>
                <span aria-hidden="true">/</span>
                <span>Toko</span>
                <span aria-hidden="true">/</span>
                <span class="max-w-[14rem] truncate font-bold text-darkbrown-900" aria-current="page">{{ $vendor->name }}</span>
            </nav>

            <div class="mt-6 grid items-center gap-10 lg:grid-cols-12 lg:gap-6">
                <!-- Identitas & info toko -->
                <div class="min-w-0 lg:col-span-7">
                    <div class="flex items-center gap-3">
                        <div class="relative shrink-0" @if(!empty($vendor->logo_url)) x-data="{ failed: false }" @endif>
                            @if(!empty($vendor->logo_url))
                                <img src="{{ $vendor->logo_url }}"
                                     alt="Logo {{ $vendor->name }}"
                                     x-show="!failed"
                                     x-on:error="failed = true"
                                     class="h-12 w-12 rounded-2xl border-2 border-white bg-wheat-100 object-cover shadow-tendaku-sm" />
                                <div x-show="failed" x-cloak
                                     class="flex h-12 w-12 items-center justify-center rounded-2xl border-2 border-white bg-gradient-to-br from-avocado-600 to-avocado-800 text-sm font-extrabold tracking-wider text-white shadow-tendaku-sm">
                                    {{ $vendorInitials ?: 'TD' }}
                                </div>
                            @else
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl border-2 border-white bg-gradient-to-br from-avocado-600 to-avocado-800 text-sm font-extrabold tracking-wider text-white shadow-tendaku-sm">
                                    {{ $vendorInitials ?: 'TD' }}
                                </div>
                            @endif
                        </div>

                        <span class="inline-flex items-center gap-1.5 rounded-full border border-avocado-200 bg-avocado-100/80 px-3 py-1 text-[11px] font-bold text-avocado-800" title="Vendor Terverifikasi">
                            <i data-lucide="shield-check" class="h-3.5 w-3.5 text-avocado-600"></i>
                            <span>Official Vendor</span>
                        </span>
                    </div>

                    <h1 class="font-display font-normal tracking-tight mt-5 break-words text-4xl leading-[1.05] text-darkbrown-900 sm:text-5xl lg:text-6xl">
                        {{ $vendor->name }}
                    </h1>

                    <p class="mt-4 max-w-xl text-sm leading-relaxed text-darkbrown-600 line-clamp-3">
                        {{ $vendor->description ?: 'Penyedia terpercaya alat camping & outdoor berkualitas steril dan terawat. Jaminan KTP fisik tanpa deposit uang tunai.' }}
                    </p>

                    <!-- Lokasi & kontak -->
                    <ul class="mt-4 flex flex-col gap-1.5 text-xs text-darkbrown-600">
                        <li class="flex items-start gap-1.5">
                            <i data-lucide="map-pin" class="mt-0.5 h-4 w-4 shrink-0 text-goldenrod-600"></i>
                            <span class="line-clamp-2">
                                @if($vendor->city)
                                    <strong class="text-darkbrown-900">{{ $vendor->city }}</strong> &bull;
                                @endif
                                {{ $vendor->address ?: 'Alamat toko terdaftar di platform Tendaku' }}
                            </span>
                        </li>
                        @if($vendor->phone || $vendor->email)
                            <li class="flex flex-wrap items-center gap-x-4 gap-y-1">
                                @if($vendor->phone)
                                    <span class="inline-flex items-center gap-1.5">
                                        <i data-lucide="phone" class="h-3.5 w-3.5 text-goldenrod-600"></i>
                                        {{ $vendor->phone }}
                                    </span>
                                @endif
                                @if($vendor->email)
                                    <span class="inline-flex items-center gap-1.5">
                                        <i data-lucide="mail" class="h-3.5 w-3.5 text-goldenrod-600"></i>
                                        {{ $vendor->email }}
                                    </span>
                                @endif
                            </li>
                        @endif
                    </ul>

                    <!-- Statistik ringkas -->
                    <dl class="mt-6 grid max-w-xl grid-cols-3 gap-2 sm:gap-3">
                        <div class="rounded-2xl border border-wheat-200 bg-white/80 p-3 backdrop-blur">
                            <dt class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Total Alat Sewa</dt>
                            <dd class="mt-1 flex flex-col leading-tight">
                                <span class="text-xl font-extrabold text-darkbrown-900">{{ $vendor->items_count }}</span>
                                <span class="text-[11px] text-darkbrown-500">Unit Siap Sewa</span>
                            </dd>
                        </div>
                        <div class="rounded-2xl border border-wheat-200 bg-white/80 p-3 backdrop-blur">
                            <dt class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Paket Camping</dt>
                            <dd class="mt-1 flex flex-col leading-tight">
                                <span class="text-xl font-extrabold text-avocado-700">{{ $vendor->camping_packages_count }}</span>
                                <span class="text-[11px] text-darkbrown-500">Pilihan Paket</span>
                            </dd>
                        </div>
                        <div class="rounded-2xl border border-wheat-200 bg-white/80 p-3 backdrop-blur">
                            <dt class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Bergabung Sejak</dt>
                            <dd class="mt-1 flex flex-col leading-tight">
                                <span class="text-sm font-extrabold text-darkbrown-900">{{ $joinedDate }}</span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Kolase alat pilihan + kartu mengambang -->
                <div class="lg:col-span-5">
                    <div class="relative mx-auto h-72 w-full max-w-md sm:h-80">
                        @forelse($spotlightItems as $i => $spot)
                            <div class="absolute overflow-hidden rounded-3xl border-4 border-white bg-wheat-100 shadow-tendaku-lg {{ $tilePositions[$i] }}">
                                <img src="{{ $imageOf($spot) }}"
                                     alt="{{ $spot->name }}"
                                     loading="{{ $i === 0 ? 'eager' : 'lazy' }}"
                                     decoding="async"
                                     class="h-full w-full object-cover" />
                            </div>
                        @empty
                            <div class="absolute left-1/2 top-0 z-10 flex aspect-[4/5] w-44 -translate-x-1/2 flex-col items-center justify-center gap-3 overflow-hidden rounded-3xl border-4 border-white bg-gradient-to-br from-avocado-600 to-avocado-800 text-wheat-100 shadow-tendaku-lg sm:w-52">
                                <i data-lucide="tent" class="h-14 w-14 text-sunglow-300"></i>
                                <span class="text-lg font-extrabold tracking-wider">{{ $vendorInitials ?: 'TD' }}</span>
                            </div>
                        @endforelse

                        @if($featured)
                            <a href="{{ route('customer.product.show', $featured->id) }}"
                               class="group absolute -bottom-1 right-0 z-20 flex w-64 items-center gap-3 rounded-2xl border border-wheat-200 bg-white/95 p-2.5 pr-3 shadow-tendaku-lg backdrop-blur transition hover:-translate-y-0.5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500">
                                <img src="{{ $imageOf($featured) }}" alt="" class="h-12 w-12 shrink-0 rounded-xl bg-wheat-100 object-cover" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-goldenrod-700">Pilihan Toko</p>
                                    <p class="truncate text-sm font-bold text-darkbrown-900">{{ $featured->name }}</p>
                                    <p class="truncate text-[11px] text-darkbrown-500">{{ $featured->category?->name ?? 'Alat Outdoor' }}</p>
                                    <p class="text-xs font-extrabold text-darkbrown-900">Rp {{ number_format((float) $featured->daily_rate, 0, ',', '.') }}<span class="font-semibold text-darkbrown-500"> /hari</span></p>
                                </div>
                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-sunglow-300 text-darkbrown-900 transition group-hover:bg-goldenrod-400" aria-hidden="true">
                                    <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
                                </span>
                                <span class="sr-only">Lihat {{ $featured->name }}</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. FILTER + DAFTAR KATALOG -->
        <div class="grid gap-6 lg:grid-cols-[17rem_minmax(0,1fr)] lg:items-start">
            <!-- Sidebar filter -->
            <aside x-data="{ open: false }" class="lg:sticky lg:top-24" aria-label="Filter katalog toko">
                <button type="button"
                        @click="open = !open"
                        :aria-expanded="open.toString()"
                        class="flex w-full items-center justify-between rounded-2xl border border-wheat-200 bg-white px-4 py-3 text-sm font-bold text-darkbrown-900 shadow-tendaku-sm lg:hidden">
                    <span class="inline-flex items-center gap-2">
                        <i data-lucide="sliders-horizontal" class="h-4 w-4 text-avocado-600"></i>
                        Filter
                        @if($activeFilterCount > 0)
                            <span class="rounded-full bg-avocado-600 px-2 py-0.5 text-[10px] font-bold text-white">{{ $activeFilterCount }}</span>
                        @endif
                    </span>
                    <i data-lucide="chevron-down" class="h-4 w-4 text-darkbrown-500 transition" :class="open && 'rotate-180'"></i>
                </button>

                <div class="mt-3 hidden space-y-6 rounded-3xl border border-wheat-200/90 bg-white p-5 shadow-tendaku-sm lg:mt-0 lg:!block"
                     :class="{ '!block': open }">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-extrabold tracking-tight text-darkbrown-900">Filter</h2>
                        @if($hasFilters)
                            <a href="{{ $resetUrl }}" class="text-xs font-bold text-avocado-700 underline-offset-2 hover:underline">Reset</a>
                        @endif
                    </div>

                    <!-- Kategori (hanya alat satuan) -->
                    @if(! $isPackages && $categories->isNotEmpty())
                        <div>
                            <h3 class="text-xs font-semibold text-darkbrown-500">Kategori</h3>
                            <div class="mt-2.5 flex flex-wrap gap-2">
                                @foreach($categories as $cat)
                                    @php $isActive = $categoryId === $cat->id; @endphp
                                    <a href="{{ $storeUrl(['category_id' => $isActive ? null : $cat->id]) }}"
                                       aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                                       class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2 {{ $isActive ? 'border-darkbrown-800 bg-darkbrown-800 text-wheat-100' : 'border-wheat-300/80 bg-wheat-100 text-darkbrown-800 hover:bg-wheat-200' }}">
                                        {{ $cat->name }}
                                        <span class="text-[10px] {{ $isActive ? 'text-wheat-300' : 'text-darkbrown-400' }}">{{ $cat->items_count }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Harga -->
                    <div>
                        <h3 class="text-xs font-semibold text-darkbrown-500">{{ $isPackages ? 'Harga per paket' : 'Harga per hari' }}</h3>
                        <ul class="mt-2.5 space-y-1">
                            @foreach($priceBuckets as $key => $bucket)
                                @php
                                    $count = $priceCounts[$key] ?? 0;
                                    $isActive = $price === $key;
                                    $disabled = $count === 0 && ! $isActive;
                                    $width = $count > 0 ? max(8, (int) round($count / $maxBucket * 100)) : 0;
                                @endphp
                                <li>
                                    <a @if(! $disabled) href="{{ $storeUrl(['price' => $isActive ? null : $key]) }}" @endif
                                       @if($disabled) aria-disabled="true" @endif
                                       aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                                       class="flex items-center gap-2.5 rounded-xl px-2 py-1.5 text-xs transition {{ $disabled ? 'cursor-not-allowed opacity-45' : 'hover:bg-wheat-100' }} {{ $isActive ? 'bg-wheat-100 font-extrabold text-darkbrown-900' : 'font-medium text-darkbrown-700' }}">
                                        <span class="w-[7.25rem] shrink-0 leading-tight">{{ $bucket['label'] }}</span>
                                        <span class="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full bg-wheat-200" aria-hidden="true">
                                            <span class="block h-full rounded-full {{ $isActive ? 'bg-avocado-600' : 'bg-goldenrod-400' }}" style="width: {{ $width }}%"></span>
                                        </span>
                                        <span class="w-5 shrink-0 text-right text-[11px] text-darkbrown-500">{{ $count }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Ketersediaan (hanya alat satuan) -->
                    @if(! $isPackages)
                        <div>
                            <h3 class="text-xs font-semibold text-darkbrown-500">Ketersediaan</h3>
                            <a href="{{ $storeUrl(['stock' => $inStock ? null : 1]) }}"
                               role="switch"
                               aria-checked="{{ $inStock ? 'true' : 'false' }}"
                               class="mt-2.5 flex items-center justify-between gap-3 rounded-xl px-2 py-1.5 text-xs font-medium text-darkbrown-800 transition hover:bg-wheat-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500">
                                <span>Stok tersedia <span class="ml-1 text-[11px] text-darkbrown-400">{{ $stockCount }}</span></span>
                                <span class="relative h-6 w-11 shrink-0 rounded-full transition {{ $inStock ? 'bg-avocado-600' : 'bg-wheat-300' }}" aria-hidden="true">
                                    <span class="absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all {{ $inStock ? 'left-[22px]' : 'left-0.5' }}"></span>
                                </span>
                            </a>
                        </div>
                    @endif
                </div>
            </aside>

            <!-- Daftar produk -->
            <div class="min-w-0 space-y-5">
                @if($vendor->camping_packages_count > 0)
                    <!-- Tab: Alat Satuan vs Paket Camping -->
                    <div class="flex flex-wrap items-center gap-2" role="tablist">
                        <a href="{{ $storeUrl(['tab' => null, 'price' => null, 'category_id' => null, 'stock' => null]) }}"
                           role="tab" aria-selected="{{ $isPackages ? 'false' : 'true' }}"
                           class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2 {{ $isPackages ? 'bg-white text-darkbrown-700 shadow-tendaku-sm hover:bg-wheat-100' : 'bg-darkbrown-800 text-wheat-100 shadow-tendaku-sm' }}">
                            <i data-lucide="tent" class="h-4 w-4"></i>
                            Alat Satuan <span class="opacity-70">{{ $vendor->items_count }}</span>
                        </a>
                        <a href="{{ $storeUrl(['tab' => 'packages', 'price' => null, 'category_id' => null, 'stock' => null]) }}"
                           role="tab" aria-selected="{{ $isPackages ? 'true' : 'false' }}"
                           class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2 {{ $isPackages ? 'bg-darkbrown-800 text-wheat-100 shadow-tendaku-sm' : 'bg-white text-darkbrown-700 shadow-tendaku-sm hover:bg-wheat-100' }}">
                            <i data-lucide="package" class="h-4 w-4"></i>
                            Paket Camping <span class="opacity-70">{{ $vendor->camping_packages_count }}</span>
                        </a>
                    </div>
                @endif

                <!-- Toolbar: jumlah hasil, pencarian, urutan -->
                <form method="GET" action="{{ route('customer.stores.show', $slug) }}" class="flex flex-wrap items-center justify-between gap-3">
                    @if($isPackages)<input type="hidden" name="tab" value="packages">@endif
                    @if($categoryId)<input type="hidden" name="category_id" value="{{ $categoryId }}">@endif
                    @if($price)<input type="hidden" name="price" value="{{ $price }}">@endif
                    @if($inStock)<input type="hidden" name="stock" value="1">@endif

                    <p class="text-sm text-darkbrown-500">
                        <span class="font-bold text-darkbrown-900">{{ $resultTotal }}</span>
                        {{ $isPackages ? 'paket' : 'alat' }}
                        <span class="hidden sm:inline">dari {{ $vendor->name }}</span>
                    </p>

                    <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                        <div class="relative min-w-0 flex-1 sm:flex-none">
                            <i data-lucide="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-darkbrown-400"></i>
                            <input type="search"
                                   name="search"
                                   value="{{ $search }}"
                                   aria-label="Cari di toko {{ $vendor->name }}"
                                   placeholder="Cari di toko ini..."
                                   class="input-tendaku !rounded-full py-2 pl-10 pr-4 text-xs font-medium sm:w-56" />
                        </div>

                        <div class="inline-flex items-center gap-2 rounded-full border border-wheat-200 bg-white py-1 pl-4 pr-1 text-xs shadow-tendaku-sm">
                            <span class="text-darkbrown-500">Urutkan</span>
                            <x-dropdown-select
                                variant="inline"
                                mode="submit"
                                name="sort"
                                label="Urutkan"
                                :options="$sortOptions"
                                :value="$sort"
                            />
                        </div>

                        <noscript>
                            <button type="submit" class="btn-tendaku-primary !py-2 text-xs">Terapkan</button>
                        </noscript>
                    </div>
                </form>

                @if($isPackages)
                    <!-- DAFTAR PAKET CAMPING KHUSUS TOKO INI -->
                    @if($packages->count() > 0)
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach($packages as $package)
                                @php
                                    $pkgImg = $package->image_url ?: null;
                                    $savings = (float) $package->savings;
                                    $normalPrice = (float) $package->normal_price;
                                    $pkgMeta = collect([
                                        $package->duration_days.' hari',
                                        $package->total_items_count.' item',
                                        $package->max_people ? 'maks. '.$package->max_people.' orang' : null,
                                    ])->filter()->join(' · ');
                                @endphp
                                <article class="group flex flex-col rounded-3xl border border-wheat-200/90 bg-white p-2.5 shadow-tendaku-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-tendaku motion-reduce:transform-none">
                                    <a href="{{ route('customer.packages.show', $package->id) }}" class="relative block aspect-[4/3] overflow-hidden rounded-2xl bg-gradient-to-br from-wheat-100 to-sunglow-100">
                                        <span class="absolute inset-0 flex items-center justify-center text-darkbrown-300" aria-hidden="true">
                                            <i data-lucide="package" class="h-9 w-9"></i>
                                        </span>
                                        @if($pkgImg)
                                            <img src="{{ $pkgImg }}" alt="{{ $package->name }}" loading="lazy" decoding="async"
                                                 onerror="this.style.display='none'"
                                                 class="relative h-full w-full object-cover transition duration-500 group-hover:scale-105 motion-reduce:transform-none" />
                                        @endif
                                        @if($savings > 0)
                                            <span class="absolute left-2.5 top-2.5 rounded-full bg-sunglow-300 px-2.5 py-1 text-[11px] font-bold text-darkbrown-900 shadow-sm">
                                                Hemat Rp {{ number_format($savings, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </a>

                                    <div class="flex flex-1 flex-col px-1.5 pb-1 pt-3">
                                        <h3 class="line-clamp-2 text-sm font-bold leading-snug text-darkbrown-900">
                                            <a href="{{ route('customer.packages.show', $package->id) }}" class="transition-colors hover:text-avocado-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500">{{ $package->name }}</a>
                                        </h3>
                                        <p class="mt-0.5 truncate text-xs text-darkbrown-500">{{ $pkgMeta }}</p>

                                        <div class="mt-auto flex items-end justify-between gap-2 pt-3">
                                            <div class="min-w-0">
                                                <p class="text-base font-extrabold text-darkbrown-900">
                                                    Rp {{ number_format((float) $package->price, 0, ',', '.') }}
                                                    <span class="text-[11px] font-semibold text-darkbrown-500">/hari</span>
                                                </p>
                                                @if($normalPrice > (float) $package->price)
                                                    <p class="text-[11px] text-darkbrown-400 line-through">Rp {{ number_format($normalPrice, 0, ',', '.') }}</p>
                                                @endif
                                            </div>
                                            <a href="{{ route('customer.packages.booking', $package->id) }}"
                                               title="Sewa {{ $package->name }}"
                                               aria-label="Sewa paket {{ $package->name }}"
                                               class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-darkbrown-800 text-wheat-200 transition hover:bg-avocado-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2 active:scale-95">
                                                <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
                                            </a>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="pt-2">
                            {{ $packages->links() }}
                        </div>
                    @else
                        <!-- Empty State Paket -->
                        <div class="card-tendaku space-y-3 rounded-3xl bg-white p-12 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-wheat-100 text-darkbrown-600">
                                <i data-lucide="package-search" class="h-7 w-7"></i>
                            </div>
                            <h4 class="text-base font-extrabold text-darkbrown-900">Tidak Ada Paket Camping Ditemukan</h4>
                            <p class="mx-auto max-w-sm text-xs text-darkbrown-600">
                                Toko ini belum memiliki paket camping aktif yang cocok dengan filter atau kata kunci pencarian Anda.
                            </p>
                            @if($hasFilters)
                                <a href="{{ $resetUrl }}" class="btn-tendaku-secondary !px-4 !py-2.5 text-xs font-bold">
                                    <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                                    <span>Reset Filter Toko</span>
                                </a>
                            @endif
                        </div>
                    @endif
                @else
                    <!-- DAFTAR ALAT CAMPING SATUAN KHUSUS TOKO INI -->
                    @if($items->count() > 0)
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            @foreach($items as $item)
                                @php
                                    $formattedRate = number_format((float) $item->daily_rate, 0, ',', '.');
                                    $unitCount = (int) ($item->units_count ?? 0);
                                    $available = $unitCount > 0;
                                    $img = $imageOf($item);
                                    $detailUrl = route('customer.product.show', $item->id);
                                    $catName = $item->category?->name ?? 'Alat Outdoor';
                                @endphp

                                <article class="group flex flex-col rounded-3xl border border-wheat-200/90 bg-white p-2.5 shadow-tendaku-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-tendaku motion-reduce:transform-none">
                                    <a href="{{ $detailUrl }}" class="relative block aspect-[4/3] overflow-hidden rounded-2xl bg-gradient-to-br from-wheat-100 to-sunglow-100" tabindex="-1" aria-hidden="true">
                                        <span class="absolute inset-0 flex items-center justify-center text-darkbrown-300">
                                            <i data-lucide="image" class="h-8 w-8"></i>
                                        </span>
                                        @if($img)
                                            <img src="{{ $img }}" alt="" loading="lazy" decoding="async"
                                                 onerror="this.style.display='none'"
                                                 class="relative h-full w-full object-cover transition duration-500 group-hover:scale-105 motion-reduce:transform-none {{ $available ? '' : 'opacity-40 grayscale' }}" />
                                        @endif
                                        @unless($available)
                                            <span class="absolute inset-0" style="background-image: repeating-linear-gradient(135deg, rgba(43,34,2,.07) 0 2px, transparent 2px 8px)"></span>
                                        @endunless
                                    </a>

                                    <div class="flex flex-1 flex-col px-1.5 pb-1 pt-3 {{ $available ? '' : 'opacity-70' }}">
                                        <h3 class="line-clamp-2 text-sm font-bold leading-snug text-darkbrown-900">
                                            <a href="{{ $detailUrl }}" class="transition-colors hover:text-avocado-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500">{{ $item->name }}</a>
                                        </h3>
                                        <p class="mt-0.5 truncate text-xs text-darkbrown-500">{{ $catName }}, {{ $item->dominant_condition }}</p>

                                        <div class="mt-auto flex items-end justify-between gap-2 pt-3">
                                            <div class="min-w-0">
                                                <p class="text-base font-extrabold text-darkbrown-900">
                                                    Rp {{ $formattedRate }}
                                                    <span class="text-[11px] font-semibold text-darkbrown-500">/hari</span>
                                                </p>
                                                @if($available)
                                                    <p class="mt-0.5 inline-flex items-center gap-1.5 text-[11px] font-semibold text-avocado-700">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-avocado-500"></span>
                                                        Stok {{ $unitCount }} unit
                                                    </p>
                                                @else
                                                    <p class="mt-0.5 inline-flex items-center gap-1.5 text-[11px] font-semibold text-darkbrown-500">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-darkbrown-300"></span>
                                                        Stok habis
                                                    </p>
                                                @endif
                                            </div>

                                            @if($available)
                                                <a href="{{ $detailUrl }}"
                                                   title="Sewa {{ $item->name }} Sekarang"
                                                   aria-label="Sewa {{ $item->name }}"
                                                   class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-darkbrown-800 text-wheat-200 transition hover:bg-avocado-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2 active:scale-95">
                                                    <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
                                                </a>
                                            @else
                                                <span class="inline-flex h-10 w-10 shrink-0 rounded-full border-2 border-wheat-300" aria-hidden="true"></span>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="pt-2">
                            {{ $items->links() }}
                        </div>
                    @else
                        <!-- EMPTY STATE YANG RAPI TANPA MENYEBABKAN ERROR -->
                        <div class="card-tendaku space-y-4 rounded-3xl bg-white p-12 text-center sm:p-16">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl border border-wheat-300/60 bg-wheat-100 text-darkbrown-700 shadow-sm">
                                <i data-lucide="tent-tree" class="h-8 w-8 text-avocado-700"></i>
                            </div>
                            <div class="mx-auto max-w-md space-y-1.5">
                                <h4 class="text-lg font-extrabold text-darkbrown-900">
                                    Belum Ada Peralatan Disewakan
                                </h4>
                                <p class="text-xs leading-relaxed text-darkbrown-600">
                                    @if($hasFilters)
                                        Tidak ditemukan peralatan yang cocok dengan filter pencarian di toko ini. Coba reset filter atau gunakan kata kunci lain.
                                    @else
                                        Toko <strong>{{ $vendor->name }}</strong> saat ini belum memiliki peralatan camping aktif untuk disewakan atau stok sedang dalam proses pembaruan.
                                    @endif
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                                @if($hasFilters)
                                    <a href="{{ $resetUrl }}" class="btn-tendaku-secondary !px-4 !py-2.5 text-xs font-bold">
                                        <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                                        <span>Reset Filter Toko</span>
                                    </a>
                                @endif
                                <a href="{{ route('customer.catalog.index') }}" class="btn-tendaku-primary !px-5 !py-2.5 text-xs font-bold shadow-md">
                                    <i data-lucide="shopping-bag" class="h-3.5 w-3.5"></i>
                                    <span>Jelajahi Katalog Toko Lain</span>
                                </a>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-layouts.customer>
