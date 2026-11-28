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
