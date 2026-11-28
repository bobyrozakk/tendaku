<section class="py-12 sm:py-20 lg:py-24 w-full bg-[#FDFBF7]" id="shop" aria-labelledby="shop-heading">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6 sm:mb-10" data-sr>
            <div>
                <h2 id="shop-heading" class="font-display text-3xl sm:text-4xl text-darkbrown-900 mt-1">Peralatan Terpopuler</h2>
            </div>
            <a href="{{ route('customer.catalog.index') }}"
               class="text-sm font-semibold text-darkbrown-700 hover:text-avocado-700 inline-flex items-center gap-1 min-h-[44px] transition-colors shrink-0">
                Lihat Semua Produk
                <i data-lucide="arrow-right" class="w-4 h-4" aria-hidden="true"></i>
            </a>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between mb-6">
            <form method="GET" action="{{ route('home') }}#shop" class="flex flex-col sm:flex-row sm:flex-wrap sm:items-end gap-3 w-full sm:w-auto">
                <label class="text-xs font-semibold text-darkbrown-700 w-full sm:w-auto">
                    Mulai sewa
                    <input type="date" name="start_date" value="{{ request('start_date', $dateRange['start_date'] ?? '') }}" min="{{ now()->toDateString() }}" class="input-tendaku block min-h-[44px]" required />
                </label>
                <label class="text-xs font-semibold text-darkbrown-700 w-full sm:w-auto">
                    Selesai sewa
                    <input type="date" name="end_date" value="{{ request('end_date', $dateRange['end_date'] ?? '') }}" min="{{ now()->toDateString() }}" class="input-tendaku block min-h-[44px]" required />
                </label>
                <button type="submit" class="btn-tendaku-primary text-xs min-h-[44px] w-full sm:w-auto">Cek Ketersediaan</button>
            </form>
            <x-stock-period :date-range="$dateRange" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            @forelse($popularItems as $i => $product)
                @php
                    $detailUrl = route('customer.product.show', ['id' => $product->id] + ($dateRange ?? request()->only('start_date', 'end_date')));
                @endphp
                <div class="ld-prod-card group" data-sr data-sr-delay="{{ $i * 90 }}">
                    <a href="{{ $detailUrl }}" aria-label="Lihat detail {{ $product->name }}">
                        <div class="ld-prod-wrap">
                            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-1.5 text-darkbrown-400" aria-hidden="true">
                                <i data-lucide="image" class="w-8 h-8"></i>
                                <span class="text-[11px] font-semibold">Foto belum tersedia</span>
                            </div>
                            <img src="{{ $product->image_url ?: 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80' }}" alt="{{ $product->name }}" loading="lazy" width="600" height="800" class="relative" onerror="this.style.display='none'">
                            <div class="absolute top-3 left-3 z-10 max-w-[calc(100%-1.5rem)] sm:max-w-[76%]">
                                <x-stock-status :stock="$dateStockMap[$product->id]" />
                            </div>
                            <div class="ld-sewa-btn">
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="shopping-bag" class="w-3.5 h-3.5" aria-hidden="true"></i>
                                    Sewa Sekarang
                                </span>
                            </div>
                        </div>
                    </a>
                    <div class="mt-3 px-0.5">
                        <span class="text-[11px] font-semibold text-avocado-700 flex items-center gap-1">
                            <i data-lucide="store" class="w-3 h-3" aria-hidden="true"></i>
                            {{ $product->vendor?->name }}
                        </span>
                        <a href="{{ $detailUrl }}"
                           class="text-sm font-bold text-darkbrown-800 hover:text-avocado-700 transition-colors duration-200 block leading-snug mt-1">
                            {{ $product->name }}
                        </a>
                        <span class="text-sm font-extrabold text-darkbrown-900 tabular-nums mt-1 block">
                            Rp {{ number_format($product->daily_rate, 0, ',', '.') }}<span class="text-xs font-normal text-darkbrown-400">/hari</span>
                        </span>
                    </div>
                </div>
            @empty
                <p class="col-span-full text-sm text-darkbrown-600">Belum ada peralatan yang ditampilkan.</p>
            @endforelse
        </div>
    </div>
</section>
