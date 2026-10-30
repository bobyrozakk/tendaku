<x-layouts.customer>
    <x-slot name="title">{{ $vendor->name }} - Profil Toko Rental Alat Camping Tendaku</x-slot>

    <!-- Header Breadcrumbs -->
    <x-slot name="header">
        <div class="flex items-center gap-2 text-xs font-semibold text-darkbrown-600 mb-2">
            <a href="{{ route('home') }}" class="hover:text-avocado-700 transition flex items-center gap-1">
                <i data-lucide="home" class="w-3.5 h-3.5"></i>
                <span>Beranda</span>
            </a>
            <span>/</span>
            <a href="{{ route('customer.catalog.index') }}" class="hover:text-avocado-700 transition">
                <span>Katalog</span>
            </a>
            <span>/</span>
            <span class="text-darkbrown-500 font-medium">Toko</span>
            <span>/</span>
            <span class="text-darkbrown-900 font-bold truncate">{{ $vendor->name }}</span>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="font-extrabold text-2xl md:text-3xl text-darkbrown-900 tracking-tight font-heading flex items-center gap-2.5">
                    <i data-lucide="store" class="w-7 h-7 text-darkbrown-800"></i>
                    <span>Profil Toko &amp; Katalog Sewa</span>
                </h1>
                <p class="text-sm text-darkbrown-600 mt-1">
                    Sewa perlengkapan camping dan paket outdoor resmi langsung dari <strong>{{ $vendor->name }}</strong>.
                </p>
            </div>
            <a href="{{ route('customer.catalog.index') }}" 
               class="btn-tendaku-secondary inline-flex items-center gap-1.5 !py-2 !px-3.5 text-xs font-bold self-start sm:self-auto">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali ke Katalog Semua Toko</span>
            </a>
        </div>
    </x-slot>

    @php
        $vendorInitials = collect(explode(' ', $vendor->name))
            ->map(fn($part) => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->join('');
        $joinedDate = $vendor->approved_at 
            ? $vendor->approved_at->translatedFormat('d M Y') 
            : ($vendor->created_at ? $vendor->created_at->translatedFormat('d M Y') : 'Mitra Terpercaya');
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
                        Login untuk Melanjutkan
                    </h3>

                    <p class="text-xs sm:text-sm text-darkbrown-600 mt-2 leading-relaxed">
                        Masuk ke akun Anda untuk menyewa alat camping di <strong>{{ $vendor->name }}</strong>, menyimpan ke keranjang, dan menikmati kemudahan booking tanpa deposit uang.
                    </p>

                    <div class="mt-6 flex flex-col gap-2.5">
                        <a href="{{ route('login') }}" 
                           class="btn-tendaku-primary w-full py-3 px-4 rounded-xl text-xs sm:text-sm font-bold text-center shadow-md hover:shadow-lg transition flex items-center justify-center gap-2">
                            <i data-lucide="log-in" class="w-4 h-4"></i>
                            <span>Login</span>
                        </a>

                        <button type="button" 
                                @click="showGuestLoginModal = false" 
                                class="btn-tendaku-secondary w-full py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold text-center text-darkbrown-700 hover:bg-wheat-100 transition">
                            Lewati
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endguest

    <div class="space-y-8 py-2">
        <!-- 1. SHOPEE-STYLE STORE HEADER PROFILE -->
        <section class="card-tendaku rounded-3xl bg-gradient-to-br from-darkbrown-900 via-darkbrown-800 to-darkbrown-900 border border-darkbrown-700 p-6 sm:p-8 text-wheat-100 shadow-tendaku-lg relative overflow-hidden">
            <!-- Decorative Ambient Glows -->
            <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-avocado-600/15 blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-sunglow-400/10 blur-3xl pointer-events-none"></div>

            <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                <!-- Kolom Kiri: Identitas & Info Toko (7 cols) -->
                <div class="lg:col-span-7 flex flex-col sm:flex-row items-start sm:items-center gap-5">
                    <!-- Foto Profil / Avatar Toko (dengan fallback) -->
                    <div class="relative shrink-0">
                        @if(!empty($vendor->logo_url))
                            <img src="{{ $vendor->logo_url }}" 
                                 alt="{{ $vendor->name }}" 
                                 class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl object-cover border-2 border-wheat-300/40 shadow-xl bg-wheat-100"
                                 onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-gradient-to-br from-avocado-600 to-avocado-800 text-white font-extrabold text-2xl flex items-center justify-center border-2 border-wheat-300/40 shadow-xl font-heading\'>{{ $vendorInitials }}</div>';"
                            />
                        @else
                            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-gradient-to-br from-avocado-600 to-avocado-800 text-white font-extrabold text-2xl flex items-center justify-center border-2 border-wheat-300/40 shadow-xl font-heading tracking-wider">
                                {{ $vendorInitials ?: 'TD' }}
                            </div>
                        @endif
                        <span class="absolute -bottom-1.5 -right-1.5 w-6 h-6 rounded-full bg-avocado-500 border-2 border-darkbrown-900 flex items-center justify-center text-white" title="Vendor Terverifikasi">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        </span>
                    </div>

                    <!-- Detail & Bio Toko -->
                    <div class="space-y-2 min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight font-heading truncate">
                                {{ $vendor->name }}
                            </h2>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-avocado-500/20 text-avocado-300 border border-avocado-400/30">
                                <i data-lucide="shield-check" class="w-3.5 h-3.5 text-avocado-400"></i>
                                <span>Official Vendor</span>
                            </span>
                        </div>

                        <!-- Lokasi / Alamat Singkat -->
                        <div class="flex items-start gap-1.5 text-xs text-wheat-300">
                            <i data-lucide="map-pin" class="w-4 h-4 text-sunglow-300 shrink-0 mt-0.5"></i>
                            <span class="line-clamp-2">
                                @if($vendor->city)
                                    <strong class="text-white">{{ $vendor->city }}</strong> &bull;
                                @endif
                                {{ $vendor->address ?: 'Alamat toko terdaftar di platform Tendaku' }}
                            </span>
                        </div>

                        <!-- Deskripsi Toko -->
                        <p class="text-xs text-wheat-400 leading-relaxed line-clamp-2">
                            {{ $vendor->description ?: 'Penyedia terpercaya alat camping & outdoor berkualitas steril dan terawat. Jaminan KTP fisik tanpa deposit uang tunai.' }}
                        </p>

                        <!-- Kontak & Jaminan -->
                        <div class="pt-1 flex flex-wrap items-center gap-2 text-[11px]">
                            @if($vendor->phone)
                                <span class="inline-flex items-center gap-1 text-wheat-300">
                                    <i data-lucide="phone" class="w-3 h-3 text-sunglow-300"></i>
                                    {{ $vendor->phone }}
                                </span>
                            @endif
                            @if($vendor->email)
                                <span class="text-wheat-500">&bull;</span>
                                <span class="inline-flex items-center gap-1 text-wheat-300">
                                    <i data-lucide="mail" class="w-3 h-3 text-sunglow-300"></i>
                                    {{ $vendor->email }}
                                </span>
                            @endif
                            <span class="text-wheat-500">&bull;</span>
                            <span class="text-avocado-300 font-semibold inline-flex items-center gap-1">
                                <i data-lucide="sparkles" class="w-3 h-3 text-avocado-400"></i>
                                Bebas Deposit Uang
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Statistik Ringkas ala Shopee (5 cols) -->
                <div class="lg:col-span-5 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-2 gap-3 border-t lg:border-t-0 lg:border-l border-wheat-200/20 pt-4 lg:pt-0 lg:pl-6">
                    <div class="bg-darkbrown-800/80 rounded-xl p-3 border border-darkbrown-700/80">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-wheat-400">Total Alat Sewa</span>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <span class="text-xl font-extrabold text-white font-heading">{{ $vendor->items_count }}</span>
                            <span class="text-[11px] text-wheat-400">Unit Siap Sewa</span>
                        </div>
                    </div>

                    <div class="bg-darkbrown-800/80 rounded-xl p-3 border border-darkbrown-700/80">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-wheat-400">Paket Camping</span>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <span class="text-xl font-extrabold text-sunglow-300 font-heading">{{ $vendor->camping_packages_count }}</span>
                            <span class="text-[11px] text-wheat-400">Pilihan Paket</span>
                        </div>
                    </div>

                    <div class="bg-darkbrown-800/80 rounded-xl p-3 border border-darkbrown-700/80 sm:col-span-1 lg:col-span-2">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-wheat-400">Bergabung Sejak</span>
                        <div class="flex items-center gap-1.5 mt-0.5 text-xs font-semibold text-white">
                            <i data-lucide="calendar-check" class="w-3.5 h-3.5 text-avocado-400"></i>
                            <span>{{ $joinedDate }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. TABS & FILTER PENCARIAN TOKO -->
        <section class="card-tendaku rounded-2xl bg-white p-5 border border-wheat-200 shadow-tendaku space-y-4">
            <!-- Tabs Navigasi: Alat vs Paket Camping -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-wheat-200 pb-3">
                <div class="flex items-center gap-2">
                    <a href="{{ route('customer.stores.show', array_merge(['vendor' => $vendor->slug ?? $vendor->id], request()->except('tab'), ['tab' => 'items'])) }}" 
                       class="px-4 py-2 rounded-xl text-xs font-extrabold transition-all border flex items-center gap-1.5 {{ $tab !== 'packages' ? 'bg-darkbrown-800 text-wheat-200 border-darkbrown-900 shadow-sm' : 'bg-wheat-100 text-darkbrown-700 border-wheat-300 hover:bg-wheat-200' }}">
                        <i data-lucide="tent" class="w-4 h-4"></i>
                        <span>Katalog Alat Satuan ({{ $vendor->items_count }})</span>
                    </a>

                    @if($vendor->camping_packages_count > 0)
                        <a href="{{ route('customer.stores.show', array_merge(['vendor' => $vendor->slug ?? $vendor->id], request()->except('tab'), ['tab' => 'packages'])) }}" 
                           class="px-4 py-2 rounded-xl text-xs font-extrabold transition-all border flex items-center gap-1.5 {{ $tab === 'packages' ? 'bg-darkbrown-800 text-wheat-200 border-darkbrown-900 shadow-sm' : 'bg-wheat-100 text-darkbrown-700 border-wheat-300 hover:bg-wheat-200' }}">
                            <i data-lucide="package" class="w-4 h-4"></i>
                            <span>Paket Camping ({{ $vendor->camping_packages_count }})</span>
                        </a>
                    @endif
                </div>

                <div class="text-xs text-darkbrown-500 font-medium">
                    Hanya menampilkan perlengkapan resmi dari <span class="font-bold text-darkbrown-800">{{ $vendor->name }}</span>
                </div>
            </div>

            <!-- Form Pencarian & Kategori Toko -->
            <form method="GET" action="{{ route('customer.stores.show', $vendor->slug ?? $vendor->id) }}" class="space-y-3">
                <input type="hidden" name="tab" value="{{ $tab }}">

                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <i data-lucide="search" class="w-4 h-4 text-darkbrown-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input type="text" 
                               name="search" 
                               value="{{ $search }}" 
                               placeholder="Cari alat di toko {{ $vendor->name }}..." 
                               class="input-tendaku w-full pl-9 pr-4 py-2.5 rounded-xl text-xs font-medium border-wheat-300"
                        />
                    </div>

                    @if($categories->isNotEmpty() && $tab !== 'packages')
                        <select name="category_id" 
                                class="input-tendaku sm:w-56 py-2.5 px-3 rounded-xl text-xs font-medium border-wheat-300">
                            <option value="">Semua Kategori Toko</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected($categoryId === $cat->id)>
                                    {{ $cat->name }} ({{ $cat->items_count }})
                                </option>
                            @endforeach
                        </select>
                    @endif

                    <div class="flex items-center gap-2">
                        <button type="submit" class="btn-tendaku-primary !py-2.5 !px-4 text-xs font-bold">
                            Cari
                        </button>
                        @if($search !== '' || !empty($categoryId))
                            <a href="{{ route('customer.stores.show', ['vendor' => $vendor->slug ?? $vendor->id, 'tab' => $tab]) }}" 
                               class="btn-tendaku-secondary !py-2.5 !px-3 text-xs font-bold"
                               title="Reset filter toko">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </section>

        <!-- 3. DAFTAR KATALOG KHUSUS TOKO INI -->
        @if($tab === 'packages' && $vendor->camping_packages_count > 0)
            <!-- DAFTAR PAKET CAMPING KHUSUS TOKO INI -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-wheat-200 pb-3">
                    <h3 class="text-lg font-extrabold text-darkbrown-900 font-heading flex items-center gap-2">
                        <i data-lucide="package" class="w-5 h-5 text-avocado-600"></i>
                        <span>Paket Camping Siap Sewa dari {{ $vendor->name }}</span>
                    </h3>
                    <span class="text-xs text-darkbrown-500 font-semibold">{{ $packages->total() }} Paket Ditemukan</span>
                </div>

                @if($packages->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($packages as $package)
                            @php
                                $pkgImg = $package->image_url ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80';
                                $savings = (float) $package->savings;
                                $normalPrice = (float) $package->normal_price;
                            @endphp
                            <article class="card-tendaku group flex h-full flex-col overflow-hidden rounded-2xl border border-wheat-200 bg-white transition-all duration-300 hover:shadow-tendaku-glow">
                                <div class="flex flex-1 flex-col">
                                    <div class="relative aspect-[4/3] bg-wheat-100 overflow-hidden">
                                        <img src="{{ $pkgImg }}" alt="{{ $package->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy" />
                                        <div class="absolute top-3 left-3 right-3 flex items-start justify-between gap-2 pointer-events-none">
                                            <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-darkbrown-900/80 text-wheat-200 backdrop-blur-md">
                                                {{ $package->duration_days }} Hari
                                            </span>
                                            @if ($savings > 0)
                                                <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-sunglow-300 text-darkbrown-900 shadow-md">
                                                    Hemat Rp {{ number_format($savings, 0, ',', '.') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="p-5 space-y-3">
                                        <h4 class="font-extrabold text-base text-darkbrown-900 line-clamp-2 leading-snug group-hover:text-avocado-700 transition-colors font-heading">
                                            {{ $package->name }}
                                        </h4>
                                        <p class="text-xs text-darkbrown-600 line-clamp-2 leading-relaxed">
                                            {{ $package->description ?: 'Paket camping lengkap siap sewa dari vendor resmi.' }}
                                        </p>
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">
                                                <i data-lucide="package" class="w-3 h-3"></i>
                                                {{ $package->total_items_count }} item
                                            </span>
                                            @if ($package->max_people)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-wheat-100 text-darkbrown-800 border border-wheat-300">
                                                    <i data-lucide="users" class="w-3 h-3"></i>
                                                    {{ $package->max_people }} orang
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="p-5 pt-0 mt-auto border-t border-wheat-100">
                                    <div class="flex items-end justify-between py-3">
                                        <div>
                                            <span class="text-xs text-darkbrown-500 block font-medium">Harga Paket</span>
                                            <div class="flex items-baseline gap-1">
                                                <span class="text-lg font-extrabold text-darkbrown-900 font-heading">
                                                    Rp {{ number_format((float) $package->price, 0, ',', '.') }}
                                                </span>
                                                <span class="text-xs text-darkbrown-500 font-medium">/ paket</span>
                                            </div>
                                        </div>
                                        @if ($normalPrice > (float) $package->price)
                                            <span class="text-[11px] text-darkbrown-400 line-through">
                                                Rp {{ number_format($normalPrice, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <a href="{{ route('customer.packages.show', $package->id) }}" 
                                           class="py-2.5 px-3 rounded-xl text-xs font-bold text-center text-darkbrown-800 bg-wheat-100 hover:bg-wheat-200 transition">
                                            Lihat Detail
                                        </a>
                                        <a href="{{ route('customer.packages.checkout', $package->id) }}" 
                                           class="btn-tendaku-primary py-2.5 px-3 rounded-xl text-xs font-bold text-center shadow-md hover:shadow-lg transition-all">
                                            Sewa Paket
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="pt-4">
                        {{ $packages->links() }}
                    </div>
                @else
                    <!-- Empty State Paket -->
                    <div class="card-tendaku rounded-2xl bg-white p-12 text-center border border-wheat-200 shadow-tendaku space-y-3">
                        <div class="w-14 h-14 mx-auto rounded-2xl bg-wheat-100 text-darkbrown-600 flex items-center justify-center">
                            <i data-lucide="package-search" class="w-7 h-7"></i>
                        </div>
                        <h4 class="text-base font-extrabold text-darkbrown-900 font-heading">
                            Tidak Ada Paket Camping Ditemukan
                        </h4>
                        <p class="text-xs text-darkbrown-600 max-w-sm mx-auto">
                            Toko ini belum memiliki paket camping aktif yang cocok dengan kata kunci pencarian Anda.
                        </p>
                    </div>
                @endif
            </div>

        @else
            <!-- DAFTAR ALAT CAMPING SATUAN KHUSUS TOKO INI -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-wheat-200 pb-3">
                    <h3 class="text-lg font-extrabold text-darkbrown-900 font-heading flex items-center gap-2">
                        <i data-lucide="tent" class="w-5 h-5 text-avocado-600"></i>
                        <span>Katalog Alat Camping dari {{ $vendor->name }}</span>
                    </h3>
                    <span class="text-xs text-darkbrown-500 font-semibold">{{ $items->total() }} Alat Tersedia</span>
                </div>

                @if($items->count() > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        @foreach($items as $item)
                            @php
                                $rate = (float) $item->daily_rate;
                                $formattedRate = number_format($rate, 0, ',', '.');
                                $unitCount = $item->units_count ?? 0;
                                $itemImages = $item->images->pluck('image_url')->filter()->values();
                                if ($itemImages->isEmpty() && $item->image_url) {
                                    $itemImages = collect([$item->image_url]);
                                }
                                $img = $itemImages->first() ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80';
                                $catName = $item->category?->name ?? 'Alat Outdoor';
                            @endphp

                            <article class="card-tendaku group flex h-full flex-col overflow-hidden rounded-2xl border border-wheat-200 bg-white transition-all duration-300 hover:shadow-tendaku-glow">
                                <div class="flex flex-1 flex-col">
                                    <!-- Thumbnail Image Container -->
                                    <div class="relative aspect-[4/3] bg-wheat-100 overflow-hidden">
                                        <a href="{{ route('customer.product.show', $item->id) }}" class="block w-full h-full">
                                            <img src="{{ $img }}" 
                                                 alt="{{ $item->name }}" 
                                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" 
                                                 loading="lazy" 
                                            />
                                        </a>
                                        <!-- Top Badges -->
                                        <div class="absolute top-3 left-3 right-3 flex items-center justify-between pointer-events-none">
                                            <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-darkbrown-900/80 text-wheat-200 backdrop-blur-md border border-wheat-300/30">
                                                {{ $catName }}
                                            </span>
                                            <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-avocado-600 text-white shadow-md">
                                                Tanpa Deposit
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Content Info -->
                                    <div class="p-5 space-y-2.5 flex-1 flex flex-col justify-between">
                                        <div>
                                            <div class="flex items-center gap-1.5 text-xs font-semibold text-goldenrod-800 mb-1">
                                                <i data-lucide="store" class="w-3.5 h-3.5 text-goldenrod-600 shrink-0"></i>
                                                <span class="truncate">{{ $vendor->name }}</span>
                                            </div>

                                            <h4 class="font-extrabold text-base text-darkbrown-900 line-clamp-2 leading-snug group-hover:text-avocado-700 transition-colors font-heading">
                                                <a href="{{ route('customer.product.show', $item->id) }}">
                                                    {{ $item->name }}
                                                </a>
                                            </h4>

                                            <p class="text-xs text-darkbrown-600 line-clamp-2 leading-relaxed mt-1">
                                                {{ $item->description ?: 'Peralatan outdoor kualitas standar pendakian gunung, steril & siap pakai.' }}
                                            </p>
                                        </div>

                                        <div class="pt-2 flex flex-wrap items-center gap-1.5">
                                            @if($unitCount > 0)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-avocado-500"></span>
                                                    Stok: {{ $unitCount }} Unit
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-red-100 text-red-800 border border-red-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                    Stok Habis
                                                </span>
                                            @endif
                                            <span class="text-[11px] text-darkbrown-500 font-medium">| {{ $item->dominant_condition }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Footer Price & Booking Action -->
                                <div class="mt-auto border-t border-wheat-100 p-5 pt-0">
                                    <div class="flex items-baseline justify-between py-3">
                                        <div>
                                            <span class="text-xs text-darkbrown-500 block font-medium">Tarif Sewa</span>
                                            <div class="flex items-baseline gap-1">
                                                <span class="text-lg font-extrabold text-darkbrown-900 font-heading">Rp {{ $formattedRate }}</span>
                                                <span class="text-xs text-darkbrown-500 font-medium">/hari</span>
                                            </div>
                                        </div>
                                        <span class="text-[10px] text-wheat-500">{{ $item->item_age }}</span>
                                    </div>

                                    @if($unitCount > 0)
                                        <div class="grid grid-cols-2 gap-2">
                                            <a href="{{ route('customer.product.show', $item->id) }}" 
                                               class="py-2.5 px-3 rounded-xl text-xs font-bold text-center text-darkbrown-800 bg-wheat-100 hover:bg-wheat-200 transition">
                                                Lihat Detail
                                            </a>
                                            <a href="{{ route('customer.product.show', $item->id) }}" 
                                               class="btn-tendaku-primary py-2.5 px-3 rounded-xl text-xs font-bold text-center shadow-md hover:shadow-lg transition-all"
                                               title="Sewa {{ $item->name }} Sekarang">
                                                Sewa Sekarang
                                            </a>
                                        </div>
                                    @else
                                        <div class="grid grid-cols-2 gap-2">
                                            <a href="{{ route('customer.product.show', $item->id) }}" 
                                               class="py-2.5 px-3 rounded-xl text-xs font-bold text-center text-darkbrown-800 bg-wheat-100 hover:bg-wheat-200 transition">
                                                Lihat Detail
                                            </a>
                                            <button type="button" 
                                                    disabled 
                                                    class="py-2.5 px-3 rounded-xl text-xs font-bold text-center bg-wheat-200 text-darkbrown-400 cursor-not-allowed">
                                                Stok Habis
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="pt-4">
                        {{ $items->links() }}
                    </div>
                @else
                    <!-- 4. EMPTY STATE YANG RAPI TANPA MENYEBABKAN ERROR -->
                    <div class="card-tendaku rounded-3xl bg-white p-12 sm:p-16 text-center border border-wheat-200 shadow-tendaku space-y-4">
                        <div class="w-16 h-16 mx-auto rounded-3xl bg-wheat-100 text-darkbrown-700 flex items-center justify-center border border-wheat-300/60 shadow-sm">
                            <i data-lucide="tent-tree" class="w-8 h-8 text-avocado-700"></i>
                        </div>
                        <div class="space-y-1.5 max-w-md mx-auto">
                            <h4 class="text-lg font-extrabold text-darkbrown-900 font-heading">
                                Belum Ada Peralatan Disewakan
                            </h4>
                            <p class="text-xs text-darkbrown-600 leading-relaxed">
                                @if($search !== '' || !empty($categoryId))
                                    Tidak ditemukan peralatan yang cocok dengan filter pencarian di toko ini. Coba reset filter atau gunakan kata kunci lain.
                                @else
                                    Toko <strong>{{ $vendor->name }}</strong> saat ini belum memiliki peralatan camping aktif untuk disewakan atau stok sedang dalam proses pembaruan.
                                @endif
                            </p>
                        </div>
                        <div class="pt-2 flex flex-wrap items-center justify-center gap-3">
                            @if($search !== '' || !empty($categoryId))
                                <a href="{{ route('customer.stores.show', ['vendor' => $vendor->slug ?? $vendor->id, 'tab' => $tab]) }}" 
                                   class="btn-tendaku-secondary !py-2.5 !px-4 text-xs font-bold">
                                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                    <span>Reset Filter Toko</span>
                                </a>
                            @endif
                            <a href="{{ route('customer.catalog.index') }}" 
                               class="btn-tendaku-primary !py-2.5 !px-5 text-xs font-bold shadow-md">
                                <i data-lucide="shopping-bag" class="w-3.5 h-3.5"></i>
                                <span>Jelajahi Katalog Toko Lain</span>
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-layouts.customer>
