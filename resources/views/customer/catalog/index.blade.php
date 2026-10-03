<x-layouts.customer>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="font-extrabold text-2xl md:text-3xl text-darkbrown-900 tracking-tight font-heading">
                    ⛺ Katalog Peralatan Camping & Outdoor
                </h1>
                <p class="text-sm text-darkbrown-600 mt-1">
                    Temukan tenda, sleeping bag, kompor, & alat outdoor terawat dengan <span class="font-bold text-avocado-700">Jaminan KTP (Tanpa Deposit Uang)</span>.
                </p>
            </div>
            <div class="flex items-center gap-2 bg-avocado-50 border border-avocado-200 px-3.5 py-2 rounded-xl text-xs font-semibold text-avocado-900 shadow-sm self-start md:self-auto">
                <span class="w-2 h-2 rounded-full bg-avocado-500 animate-pulse"></span>
                <span>Unit Terverifikasi & Higienis</span>
            </div>
        </div>
    </x-slot>

    @php
        // Fallback Mock Items jika DB belum di-seed
        $mockCategories = [
            (object)['id' => 1, 'name' => 'Tenda & Canopy', 'slug' => 'tenda', 'icon' => '⛺', 'items_count' => 8],
            (object)['id' => 2, 'name' => 'Sleeping Gear', 'slug' => 'sleeping-gear', 'icon' => '🛌', 'items_count' => 6],
            (object)['id' => 3, 'name' => 'Kompor & Cooking', 'slug' => 'cooking', 'icon' => '🍳', 'items_count' => 5],
            (object)['id' => 4, 'name' => 'Carrier & Tas', 'slug' => 'carrier', 'icon' => '🎒', 'items_count' => 7],
            (object)['id' => 5, 'name' => 'Lampu & Penerangan', 'slug' => 'lighting', 'icon' => '🔦', 'items_count' => 4],
        ];

        $categoriesList = (isset($categories) && count($categories) > 0) ? $categories : $mockCategories;

        $mockItems = collect([
            (object)[
                'id' => 1,
                'name' => 'Tenda Consina Magnum 4 Person Ultra',
                'description' => 'Tenda kapasitas 4 orang dengan double layer waterproof pu3000mm, tahan angin kencang dan hujan lebat.',
                'daily_rate' => 45000,
                'image_url' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80',
                'category' => (object)['name' => 'Tenda & Canopy'],
                'vendor' => (object)['store_name' => 'Tendaku Store Central'],
                'units' => collect([1, 2, 3]),
            ],
            (object)[
                'id' => 2,
                'name' => 'Sleeping Bag Eiger Dome Comfort -5°C',
                'description' => 'Sleeping bag hangat berbahan polar fleece tebal, cocok untuk pendakian gunung tinggi.',
                'daily_rate' => 20000,
                'image_url' => 'https://images.unsplash.com/photo-1510312305653-8ed496efae75?auto=format&fit=crop&w=800&q=80',
                'category' => (object)['name' => 'Sleeping Gear'],
                'vendor' => (object)['store_name' => 'MountCamp Outdoor'],
                'units' => collect([1, 2, 3, 4]),
            ],
            (object)[
                'id' => 3,
                'name' => 'Kompor Portable Kovar Windproof + Adaptor',
                'description' => 'Kompor bungsu pelindung angin built-in. Dilengkapi pemantik otomatis & kabel pemanas.',
                'daily_rate' => 15000,
                'image_url' => 'https://images.unsplash.com/photo-1526772662000-3f88f10405ff?auto=format&fit=crop&w=800&q=80',
                'category' => (object)['name' => 'Kompor & Cooking'],
                'vendor' => (object)['store_name' => 'Tendaku Store Central'],
                'units' => collect([1, 2]),
            ],
            (object)[
                'id' => 4,
                'name' => 'Carrier Deuter Aircontact 65+10L',
                'description' => 'Tas gunung kapasitas besar ergonomis dengan sistem sirkulasi udara di bagian punggung.',
                'daily_rate' => 35000,
                'image_url' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80',
                'category' => (object)['name' => 'Carrier & Tas'],
                'vendor' => (object)['store_name' => 'Peak Gear Rental'],
                'units' => collect([1, 2, 3, 4, 5]),
            ],
            (object)[
                'id' => 5,
                'name' => 'Lentera Camping LED Rechargeable 1000 Lumens',
                'description' => 'Lampu tenda super terang 3 mode pencahayaan dengan powerbank portabel 5000mAh.',
                'daily_rate' => 12000,
                'image_url' => 'https://images.unsplash.com/photo-1517824806704-9040b037703b?auto=format&fit=crop&w=800&q=80',
                'category' => (object)['name' => 'Lampu & Penerangan'],
                'vendor' => (object)['store_name' => 'Tendaku Store Central'],
                'units' => collect([1, 2, 3]),
            ],
            (object)[
                'id' => 6,
                'name' => 'Nesting Cooking Set DS-308 (4 In 1)',
                'description' => 'Set panci & wajan almunium anodized anti lengket untuk 3-4 orang lengkap dengan teko air.',
                'daily_rate' => 18000,
                'image_url' => 'https://images.unsplash.com/photo-1534447677768-be436bb09401?auto=format&fit=crop&w=800&q=80',
                'category' => (object)['name' => 'Kompor & Cooking'],
                'vendor' => (object)['store_name' => 'MountCamp Outdoor'],
                'units' => collect([1, 2]),
            ]
        ]);

        $hasRealItems = isset($items) && method_exists($items, 'count') && $items->count() > 0;
        $displayItems = $hasRealItems ? $items : $mockItems;

        // Filter mock jika tak ada real DB data
        if (!$hasRealItems) {
            if (!empty($search)) {
                $displayItems = $displayItems->filter(function($i) use ($search) {
                    return stripos($i->name, $search) !== false || stripos($i->description, $search) !== false;
                });
            }
            if (!empty($categoryId)) {
                $displayItems = $displayItems->filter(function($i) use ($categoryId) {
                    return $i->id == $categoryId || (isset($i->category_id) && $i->category_id == $categoryId);
                });
            }
        }
    @endphp

    <div class="space-y-8 py-2">
        <!-- Section Filter & Search Bar -->
        <section class="card-tendaku p-6 rounded-2xl bg-white shadow-tendaku border border-wheat-200">
            <form action="{{ route('customer.catalog.index') }}" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                    <!-- Input Search -->
                    <div class="lg:col-span-8 relative">
                        <label for="search" class="block text-xs font-bold uppercase text-darkbrown-600 mb-1.5 tracking-wider">
                            🔍 Cari Alat Camping
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                id="search" 
                                name="search" 
                                value="{{ request('search') }}" 
                                placeholder="Cari nama alat (contoh: Tenda Consina, SB Eiger, Kompor)..." 
                                class="input-tendaku w-full pl-10 pr-4 py-3 rounded-xl text-sm font-medium focus:ring-2 focus:ring-avocado-500 border-wheat-300"
                            />
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-wheat-500">
                                <svg class="w-4 h-4 text-darkbrown-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi Search & Reset -->
                    <div class="lg:col-span-4 flex items-end gap-2">
                        <button type="submit" class="btn-tendaku-primary flex-1 py-3 px-5 rounded-xl font-bold text-sm shadow-md flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span>Cari Peralatan</span>
                        </button>

                        @if(request('search') || request('category_id'))
                            <a href="{{ route('customer.catalog.index') }}" class="btn-tendaku-secondary py-3 px-4 rounded-xl text-sm font-semibold flex items-center justify-center gap-1.5" title="Reset Filter">
                                <span>↺ Reset</span>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Filter Kategori Tabs (Pills) -->
                <div>
                    <label class="block text-xs font-bold uppercase text-darkbrown-600 mb-2 tracking-wider">
                        🏷️ Filter Kategori:
                    </label>
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <a href="{{ route('customer.catalog.index', array_filter(['search' => request('search')])) }}" 
                           class="px-4 py-2 rounded-xl text-xs font-bold transition-all border {{ !request('category_id') ? 'bg-darkbrown-800 text-wheat-200 border-darkbrown-900 shadow-md scale-[1.02]' : 'bg-wheat-100 text-darkbrown-800 border-wheat-300 hover:bg-wheat-200' }}">
                            ✨ Semua Kategori
                        </a>
                        @foreach($categoriesList as $cat)
                            @php
                                $catId = $cat->id ?? $loop->iteration;
                                $isActive = request('category_id') == $catId;
                            @endphp
                            <a href="{{ route('customer.catalog.index', array_filter(['category_id' => $catId, 'search' => request('search')])) }}"
                               class="px-4 py-2 rounded-xl text-xs font-bold transition-all border flex items-center gap-1.5 {{ $isActive ? 'bg-avocado-600 text-white border-avocado-700 shadow-md scale-[1.02]' : 'bg-white text-darkbrown-700 border-wheat-300 hover:border-avocado-400 hover:bg-wheat-50' }}">
                                <span>{{ $cat->icon ?? '⛺' }}</span>
                                <span>{{ $cat->name }}</span>
                                @if(isset($cat->items_count))
                                    <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] {{ $isActive ? 'bg-avocado-800 text-white' : 'bg-wheat-200 text-darkbrown-800' }}">{{ $cat->items_count }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </form>
        </section>

        <!-- Product Grid Header & Status Summary -->
        <div class="flex items-center justify-between border-b border-wheat-200 pb-3">
            <div>
                <h2 class="text-xl font-extrabold text-darkbrown-900 font-heading">
                    Daftar Peralatan Tersedia
                </h2>
                <p class="text-xs text-darkbrown-600">
                    Menampilkan {{ count($displayItems) }} unit siap sewa untuk camping kamu.
                </p>
            </div>
            <div class="hidden sm:flex items-center gap-2 text-xs text-darkbrown-600 font-medium bg-wheat-100 px-3 py-1.5 rounded-lg border border-wheat-200">
                <span>🛡️ Jaminan: <strong>KTP Asli saat Pickup</strong></span>
            </div>
        </div>

        <!-- Grid Card Produk -->
        @if(count($displayItems) > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($displayItems as $item)
                    @php
                        $itemId = $item->id ?? $loop->iteration;
                        $rate = is_numeric($item->daily_rate) ? $item->daily_rate : 35000;
                        $formattedRate = number_format($rate, 0, ',', '.');
                        $unitCount = isset($item->units) ? (is_countable($item->units) ? count($item->units) : 3) : 3;
                        $img = $item->image_url ?? 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80';
                        $catName = is_object($item->category) ? $item->category->name : ($item->category ?? 'Alat Outdoor');
                        $vendorName = is_object($item->vendor) ? $item->vendor->store_name : 'Tendaku Official Vendor';
                    @endphp
                    <article class="card-tendaku rounded-2xl overflow-hidden bg-white border border-wheat-200 flex flex-col justify-between hover:shadow-tendaku-glow transition-all duration-300 group">
                        <div>
                            <!-- Thumbnail Image container -->
                            <div class="relative aspect-[4/3] bg-wheat-100 overflow-hidden">
                                <img 
                                    src="{{ $img }}" 
                                    alt="{{ $item->name }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    loading="lazy"
                                />
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
                            <div class="p-5 space-y-3">
                                <div class="flex items-center gap-1.5 text-xs font-semibold text-goldenrod-800">
                                    <svg class="w-3.5 h-3.5 text-goldenrod-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l1.293 1.293a1 1 0 001.414-1.414l-7-7z"/>
                                    </svg>
                                    <span class="truncate">{{ $vendorName }}</span>
                                </div>

                                <h3 class="font-extrabold text-base text-darkbrown-900 line-clamp-2 leading-snug group-hover:text-avocado-700 transition-colors font-heading">
                                    {{ $item->name }}
                                </h3>

                                <p class="text-xs text-darkbrown-600 line-clamp-2 leading-relaxed">
                                    {{ $item->description ?? 'Peralatan outdoor kualitas standar pendakian gunung, steril & siap pakai.' }}
                                </p>

                                <div class="pt-1 flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-avocado-500"></span>
                                        Stok {{ $unitCount }} Unit
                                    </span>
                                    <span class="text-[11px] text-wheat-600 font-medium">| Kondisi Prima</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer Price & Action -->
                        <div class="p-5 pt-0 border-t border-wheat-100 mt-2">
                            <div class="flex items-baseline justify-between py-3">
                                <div>
                                    <span class="text-xs text-darkbrown-500 block font-medium">Harga Sewa</span>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-lg font-extrabold text-darkbrown-900 font-heading">Rp {{ $formattedRate }}</span>
                                        <span class="text-xs text-darkbrown-500 font-medium">/hari</span>
                                    </div>
                                </div>
                            </div>

                            <a href="{{ route('customer.product.show', $itemId) }}" 
                               class="btn-tendaku-primary w-full py-2.5 px-4 rounded-xl text-xs font-bold text-center block shadow-md hover:shadow-lg transition-all">
                                Lihat Detail & Sewa
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <!-- Pagination if DB items present -->
            @if($hasRealItems && method_exists($items, 'links'))
                <div class="mt-8">
                    {{ $items->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="card-tendaku-warm p-12 text-center rounded-2xl border border-wheat-300 my-8">
                <div class="w-16 h-16 bg-wheat-200 text-darkbrown-700 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                    🔍
                </div>
                <h3 class="text-lg font-bold text-darkbrown-900 font-heading">Peralatan Tidak Ditemukan</h3>
                <p class="text-sm text-darkbrown-600 max-w-md mx-auto mt-2">
                    Maaf, tidak ada alat camping yang cocok dengan kata kunci atau filter pencarian Anda saat ini.
                </p>
                <div class="mt-6">
                    <a href="{{ route('customer.catalog.index') }}" class="btn-tendaku-primary py-2.5 px-6 rounded-xl text-sm font-bold inline-block">
                        Lihat Semua Peralatan
                    </a>
                </div>
            </div>
        @endif
    </div>
</x-layouts.customer>
