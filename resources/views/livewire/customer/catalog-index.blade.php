<div class="space-y-8 py-2">
    <!-- Section Filter & Search Bar -->
    <section class="card-tendaku p-6 rounded-2xl bg-white shadow-tendaku border border-wheat-200">
        <div class="space-y-5">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <!-- Input Real-time Search -->
                <div class="lg:col-span-8 relative">
                    <label for="search" class="block text-xs font-bold uppercase text-darkbrown-600 mb-1.5 tracking-wider">
                        🔍 Cari Alat Camping Real-Time
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="search" 
                            wire:model.live.debounce.300ms="search"
                            placeholder="Cari nama alat (contoh: Tenda Consina, SB Eiger, Kompor, Carrier)..." 
                            class="input-tendaku w-full pl-10 pr-10 py-3 rounded-xl text-sm font-medium focus:ring-2 focus:ring-avocado-500 border-wheat-300"
                        />
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-wheat-500">
                            <svg class="w-4 h-4 text-darkbrown-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        
                        <!-- Clear Search Icon Button -->
                        @if(!empty($search))
                            <button 
                                type="button" 
                                wire:click="$set('search', '')" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-darkbrown-400 hover:text-darkbrown-700 transition"
                                title="Clear Search"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Action Button & Reset Filter -->
                <div class="lg:col-span-4 flex items-end gap-2">
                    <div class="flex-1 flex items-center gap-2">
                        @if(!empty($search) || !is_null($categoryId))
                            <button 
                                type="button" 
                                wire:click="resetFilters" 
                                class="btn-tendaku-secondary w-full py-3 px-4 rounded-xl text-sm font-semibold flex items-center justify-center gap-1.5 hover:border-red-300 hover:text-red-700 transition"
                                title="Reset Filter"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span>Reset Filter</span>
                            </button>
                        @else
                            <div class="w-full py-3 px-4 rounded-xl bg-wheat-50 border border-wheat-200 text-xs font-semibold text-darkbrown-600 flex items-center justify-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-avocado-500 animate-ping"></span>
                                <span>Pencarian Real-Time Aktif</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Filter Kategori Tabs (Pills) -->
            <div>
                <label class="block text-xs font-bold uppercase text-darkbrown-600 mb-2 tracking-wider">
                    🏷️ Filter Kategori:
                </label>
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <button 
                        type="button"
                        wire:click="selectCategory(null)" 
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-all border {{ is_null($categoryId) ? 'bg-darkbrown-800 text-wheat-200 border-darkbrown-900 shadow-md scale-[1.02]' : 'bg-wheat-100 text-darkbrown-800 border-wheat-300 hover:bg-wheat-200' }}"
                    >
                        ✨ Semua Kategori
                    </button>
                    @foreach($categories as $cat)
                        @php
                            $isActive = $categoryId === $cat->id;
                        @endphp
                        <button 
                            type="button"
                            wire:click="selectCategory({{ $cat->id }})"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all border flex items-center gap-1.5 {{ $isActive ? 'bg-avocado-600 text-white border-avocado-700 shadow-md scale-[1.02]' : 'bg-white text-darkbrown-700 border-wheat-300 hover:border-avocado-400 hover:bg-wheat-50' }}"
                        >
                            <span>{{ $cat->icon ?? '⛺' }}</span>
                            <span>{{ $cat->name }}</span>
                            @if(isset($cat->items_count))
                                <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] {{ $isActive ? 'bg-avocado-800 text-white' : 'bg-wheat-200 text-darkbrown-800' }}">
                                    {{ $cat->items_count }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- Product Grid Header & Status Summary -->
    <div class="flex items-center justify-between border-b border-wheat-200 pb-3">
        <div>
            <h2 class="text-xl font-extrabold text-darkbrown-900 font-heading flex items-center gap-2">
                <span>Daftar Peralatan Tersedia</span>
                <span wire:loading class="text-xs font-semibold text-avocado-700 bg-avocado-50 border border-avocado-200 px-2.5 py-0.5 rounded-full animate-pulse">
                    Memuat data...
                </span>
            </h2>
            <p class="text-xs text-darkbrown-600 mt-0.5">
                @if($items->total() > 0)
                    Menampilkan {{ $items->firstItem() }}-{{ $items->lastItem() }} dari {{ $items->total() }} unit siap sewa untuk camping kamu.
                @else
                    Tidak ada peralatan yang ditemukan.
                @endif
            </p>
        </div>
        <div class="hidden sm:flex items-center gap-2 text-xs text-darkbrown-600 font-medium bg-wheat-100 px-3 py-1.5 rounded-lg border border-wheat-200">
            <span>🛡️ Jaminan: <strong>KTP Asli saat Pickup</strong></span>
        </div>
    </div>

    <!-- Grid Card Produk -->
    @if($items->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($items as $item)
                @php
                    $rate = is_numeric($item->daily_rate) ? $item->daily_rate : 35000;
                    $formattedRate = number_format($rate, 0, ',', '.');
                    $unitCount = isset($item->units) ? $item->units->count() : 3;
                    $img = $item->image_url ?? 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80';
                    $catName = $item->category?->name ?? 'Alat Outdoor';
                    $vendorName = $item->vendor?->store_name ?? 'Tendaku Store Central';
                @endphp
                <article class="card-tendaku rounded-2xl overflow-hidden bg-white border border-wheat-200 flex flex-col justify-between hover:shadow-tendaku-glow transition-all duration-300 group">
                    <div>
                        <!-- Thumbnail Image Container -->
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

                        <a href="{{ route('customer.product.show', $item->id) }}" 
                           class="btn-tendaku-primary w-full py-2.5 px-4 rounded-xl text-xs font-bold text-center block shadow-md hover:shadow-lg transition-all">
                            Lihat Detail & Sewa
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

        <!-- Pagination Links -->
        <div class="mt-8">
            {{ $items->links() }}
        </div>
    @else
        <!-- Empty State -->
        <div class="card-tendaku-warm p-12 text-center rounded-2xl border border-wheat-300 my-8">
            <div class="w-16 h-16 bg-wheat-200 text-darkbrown-700 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                🔍
            </div>
            <h3 class="text-lg font-bold text-darkbrown-900 font-heading">Peralatan Tidak Ditemukan</h3>
            <p class="text-sm text-darkbrown-600 max-w-md mx-auto mt-2">
                Maaf, tidak ada alat camping yang cocok dengan kata kunci 
                @if(!empty($search))
                    "<span class="font-bold text-darkbrown-900">{{ $search }}</span>"
                @endif
                @if(!is_null($categoryId))
                    atau kategori yang dipilih
                @endif
                saat ini.
            </p>
            <div class="mt-6">
                <button 
                    type="button" 
                    wire:click="resetFilters" 
                    class="btn-tendaku-primary py-2.5 px-6 rounded-xl text-sm font-bold inline-block"
                >
                    Lihat Semua Peralatan
                </button>
            </div>
        </div>
    @endif
</div>
