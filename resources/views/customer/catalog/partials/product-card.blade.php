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
    class="group flex h-full flex-col overflow-hidden rounded-2xl border border-wheat-200/90 bg-white transition duration-300 hover:-translate-y-1 hover:border-avocado-300 hover:shadow-tendaku-lg focus-within:ring-2 focus-within:ring-avocado-500/30 motion-reduce:transform-none motion-reduce:transition-none"
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
        </div>
    </div>

    <div class="flex flex-1 flex-col gap-2.5 p-4">
        @if ($item->vendor)
            <a
                href="{{ route('customer.stores.show', $item->vendor->slug ?? $item->vendor_id) }}"
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
                {{ $item->name }}
            </a>
        </h3>

        <p class="line-clamp-2 text-xs leading-relaxed text-darkbrown-600">
            {{ $item->description ?? 'Peralatan outdoor kualitas standar pendakian gunung, steril & siap pakai.' }}
        </p>

        @php
            $dominantCondition = $item->dominant_condition;
            $itemAge = $item->item_age;
        @endphp
        <div class="mt-auto flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] font-medium text-darkbrown-500">
            @if ($dominantCondition)
                <span class="inline-flex items-center gap-1">
                    <i data-lucide="shield-check" class="h-3 w-3 text-avocado-600" aria-hidden="true"></i>
                    {{ $dominantCondition }}
                </span>
            @endif
            @if ($itemAge)
                <span class="text-wheat-400" aria-hidden="true">•</span>
                <span>{{ $itemAge }}</span>
            @endif
        </div>
    </div>

    <div class="border-t border-wheat-100 p-4 pt-3">
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
