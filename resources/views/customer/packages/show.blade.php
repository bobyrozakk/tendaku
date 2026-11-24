<x-layouts.customer>
    <x-slot name="title">{{ $package->name }} - Paket Camping Tendaku</x-slot>

    @php
        $image = $package->image_url ?: null;
        $price = (float) $package->price;
        $normalPrice = (float) $package->normal_price;
        $savings = (float) $package->savings;
        $vendorName = $package->vendor?->name ?? 'Tendaku Vendor';
        $formattedPrice = number_format($price, 0, ',', '.');
        $checkoutUrl = route('customer.packages.checkout', ['id' => $package->id]);

        $imageOf = fn ($item) => $item?->images?->pluck('image_url')->filter()->first() ?: ($item?->image_url ?: null);
    @endphp

    <div class="space-y-8 pb-24 lg:pb-0">
        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-semibold text-darkbrown-500">
            <a href="{{ route('customer.packages.index') }}" class="inline-flex items-center gap-1 transition hover:text-avocado-700">
                <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                <span>Kembali ke Paket Camping</span>
            </a>
            <span aria-hidden="true">/</span>
            <span class="max-w-[16rem] truncate font-bold text-darkbrown-900" aria-current="page">{{ $package->name }}</span>
        </nav>

        <div class="grid items-start gap-6 lg:grid-cols-12">
            <!-- Foto paket -->
            <div class="lg:col-span-7">
                <div class="rounded-[2rem] border border-wheat-200/90 bg-white p-2.5 shadow-tendaku-sm">
                    <div class="relative aspect-[4/3] overflow-hidden rounded-3xl bg-gradient-to-br from-wheat-100 to-sunglow-100">
                        <span class="absolute inset-0 flex items-center justify-center text-darkbrown-300" aria-hidden="true">
                            <i data-lucide="tent" class="h-14 w-14"></i>
                        </span>
                        @if ($image)
                            <img src="{{ $image }}" alt="{{ $package->name }}"
                                 decoding="async"
                                 onerror="this.style.display='none'"
                                 class="relative h-full w-full object-cover" />
                        @endif

                        <div class="pointer-events-none absolute inset-x-4 top-4 flex flex-wrap items-start gap-2">
                            <span class="rounded-full bg-darkbrown-900/85 px-3.5 py-1.5 text-xs font-extrabold text-wheat-200 backdrop-blur-md">
                                Paket {{ $package->duration_days }} Hari
                            </span>
                            @if ($savings > 0)
                                <span class="rounded-full bg-sunglow-300 px-3.5 py-1.5 text-xs font-extrabold text-darkbrown-900 shadow-md">
                                    Hemat Rp {{ number_format($savings, 0, ',', '.') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info paket & panel sewa -->
            <div class="lg:sticky lg:top-24 lg:col-span-5">
                <div class="space-y-6 rounded-[2rem] border border-wheat-200/90 bg-gradient-to-br from-wheat-100 via-[#FDFBF7] to-sunglow-100 p-6 shadow-tendaku-sm sm:p-7">
                    <div class="space-y-3">
                        @if ($package->vendor)
                            <a href="{{ route('customer.stores.show', $package->vendor->slug ?? $package->vendor_id) }}"
                               class="group inline-flex max-w-full items-center gap-2 rounded-full border border-wheat-200 bg-white/80 py-1 pl-3 pr-1 text-xs font-semibold text-goldenrod-800 backdrop-blur transition hover:border-avocado-300 hover:text-avocado-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500"
                               title="Kunjungi Toko {{ $vendorName }}">
                                <i data-lucide="store" class="h-3.5 w-3.5 shrink-0 text-goldenrod-600 group-hover:text-avocado-600"></i>
                                <span class="truncate">{{ $vendorName }}</span>
                                <span class="shrink-0 rounded-full bg-avocado-100 px-2.5 py-1 text-[10px] font-bold text-avocado-800">Kunjungi Toko</span>
                            </a>
                        @else
                            <div class="inline-flex items-center gap-1.5 text-xs font-semibold text-goldenrod-800">
                                <i data-lucide="store" class="h-3.5 w-3.5"></i>
                                <span>{{ $vendorName }}</span>
                            </div>
                        @endif

                        <h1 class="break-words text-3xl font-extrabold leading-[1.08] tracking-tighter text-darkbrown-900 sm:text-4xl">
                            {{ $package->name }}
                        </h1>

                        <p class="text-sm leading-relaxed text-darkbrown-600">
                            {{ $package->description ?: 'Paket camping lengkap berisi perlengkapan pilihan vendor, siap pakai untuk petualangan Anda.' }}
                        </p>
                    </div>

                    <!-- Spesifikasi singkat -->
                    <dl class="grid grid-cols-3 gap-2">
                        <div class="rounded-2xl border border-wheat-200 bg-white/80 p-3 backdrop-blur">
                            <i data-lucide="calendar-days" class="h-4 w-4 text-avocado-600"></i>
                            <dt class="mt-2 text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Durasi Sewa</dt>
                            <dd class="text-sm font-extrabold text-darkbrown-900">{{ $package->duration_days }} hari</dd>
                        </div>
                        <div class="rounded-2xl border border-wheat-200 bg-white/80 p-3 backdrop-blur">
                            <i data-lucide="package" class="h-4 w-4 text-avocado-600"></i>
                            <dt class="mt-2 text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Isi Paket</dt>
                            <dd class="text-sm font-extrabold text-darkbrown-900">{{ $package->total_items_count }} item</dd>
                        </div>
                        <div class="rounded-2xl border border-wheat-200 bg-white/80 p-3 backdrop-blur">
                            <i data-lucide="users" class="h-4 w-4 text-avocado-600"></i>
                            <dt class="mt-2 text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Kapasitas</dt>
                            <dd class="text-sm font-extrabold text-darkbrown-900">{{ $package->max_people ? $package->max_people.' orang' : 'Fleksibel' }}</dd>
                        </div>
                    </dl>

                    <!-- Harga & aksi -->
                    <div class="space-y-4 rounded-3xl border border-wheat-200 bg-white p-5 shadow-tendaku-sm">
                        <div>
                            <p class="text-xs font-medium text-darkbrown-500">Harga Paket per Hari</p>
                            <div class="mt-1 flex flex-wrap items-baseline gap-x-2 gap-y-1">
                                <span class="text-3xl font-extrabold tracking-tight text-darkbrown-900">Rp {{ $formattedPrice }}</span>
                                <span class="text-xs font-semibold text-darkbrown-500">/ hari</span>
                            </div>
                            @if ($normalPrice > $price)
                                <p class="mt-1.5 text-[11px] text-darkbrown-400">
                                    Harga normal bila disewa satuan:
                                    <span class="line-through">Rp {{ number_format($normalPrice, 0, ',', '.') }}</span>
                                </p>
                            @endif
                        </div>

                        <p class="text-xs leading-relaxed text-darkbrown-600">
                            Pilih jadwal sewa dan metode pengambilan di halaman booking paket.
                        </p>

                        <a href="{{ $checkoutUrl }}"
                           class="btn-tendaku-primary w-full rounded-2xl py-3.5 text-sm font-bold shadow-md hover:shadow-lg">Sewa Paket</a>

                        <ul class="space-y-2 border-t border-wheat-100 pt-4 text-[11px] text-darkbrown-600">
                            <li class="flex items-center gap-2">
                                <i data-lucide="shield-check" class="h-3.5 w-3.5 shrink-0 text-avocado-600"></i>
                                Jaminan KTP asli saat pengambilan, tanpa deposit uang.
                            </li>
                            <li class="flex items-center gap-2">
                                <i data-lucide="clock" class="h-3.5 w-3.5 shrink-0 text-avocado-600"></i>
                                Batas pembayaran 12 jam setelah pemesanan dibuat.
                            </li>
                            <li class="flex items-center gap-2">
                                <i data-lucide="package-check" class="h-3.5 w-3.5 shrink-0 text-avocado-600"></i>
                                Seluruh item dalam paket dipinjamkan sekaligus.
                            </li>
                        </ul>
                    </div>

                    <a href="{{ route('customer.catalog.index') }}"
                       class="block rounded-2xl border border-avocado-200 bg-avocado-50 py-2.5 text-center text-xs font-bold text-avocado-700 transition hover:bg-avocado-100 hover:text-avocado-800">
                        Ingin menyewa alat satuan? Lihat Katalog
                    </a>
                </div>
            </div>
        </div>

        <!-- Isi paket -->
        <section aria-labelledby="package-items-title" class="space-y-4">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h2 id="package-items-title" class="text-2xl font-extrabold tracking-tight text-darkbrown-900">Perlengkapan Dalam Paket</h2>
                    <p class="mt-1 text-xs text-darkbrown-500">Ketersediaan unit dikonfirmasi otomatis saat Anda menyelesaikan pemesanan paket.</p>
                </div>
                <span class="text-sm text-darkbrown-500">
                    <span class="font-bold text-darkbrown-900">{{ $package->total_items_count }}</span> item
                </span>
            </div>

            <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($package->packageItems as $packageItem)
                    @php
                        $item = $packageItem->masterItem;
                        $quantity = (int) $packageItem->quantity;
                        $availableUnits = $item
                            ? $item->units->where('status', 'available')->whereNotIn('condition', ['damaged', 'lost'])->count()
                            : 0;
                        $itemImage = $imageOf($item);
                        $stockState = $availableUnits >= $quantity ? 'ok' : ($availableUnits > 0 ? 'low' : 'out');
                    @endphp
                    <li class="flex flex-col rounded-3xl border border-wheat-200/90 bg-white p-2.5 shadow-tendaku-sm {{ $stockState === 'out' ? 'opacity-80' : '' }}">
                        <div class="relative aspect-[4/3] overflow-hidden rounded-2xl bg-gradient-to-br from-wheat-100 to-sunglow-100">
                            <span class="absolute inset-0 flex items-center justify-center text-darkbrown-300" aria-hidden="true">
                                <i data-lucide="image" class="h-8 w-8"></i>
                            </span>
                            @if ($itemImage)
                                <img src="{{ $itemImage }}" alt="{{ $item?->name }}" loading="lazy" decoding="async"
                                     onerror="this.style.display='none'"
                                     class="relative h-full w-full object-cover {{ $stockState === 'out' ? 'opacity-40 grayscale' : '' }}" />
                            @endif
                            @if ($stockState === 'out')
                                <span class="absolute inset-0" style="background-image: repeating-linear-gradient(135deg, rgba(43,34,2,.07) 0 2px, transparent 2px 8px)"></span>
                            @endif
                            <span class="absolute left-2.5 top-2.5 rounded-full bg-darkbrown-900/85 px-3 py-1 text-xs font-extrabold text-wheat-200 backdrop-blur-md">
                                {{ $quantity }}x
                            </span>
                        </div>

                        <div class="flex flex-1 flex-col px-1.5 pb-1 pt-3">
                            <h3 class="line-clamp-2 text-sm font-bold leading-snug text-darkbrown-900">
                                @if ($item)
                                    <a href="{{ route('customer.product.show', $item->id) }}" class="transition-colors hover:text-avocado-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500">{{ $item->name }}</a>
                                @else
                                    Produk tidak tersedia
                                @endif
                            </h3>
                            <p class="mt-0.5 truncate text-xs text-darkbrown-500">
                                {{ $item?->category?->name ?? 'Alat Outdoor' }}
                                @if ($item)
                                    &middot; Rp {{ number_format((float) $item->daily_rate, 0, ',', '.') }}/hari
                                @endif
                            </p>

                            <p class="mt-auto inline-flex items-center gap-1.5 pt-3 text-[11px] font-semibold {{ $stockState === 'ok' ? 'text-avocado-700' : ($stockState === 'low' ? 'text-goldenrod-700' : 'text-red-600') }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $stockState === 'ok' ? 'bg-avocado-500' : ($stockState === 'low' ? 'bg-goldenrod-500' : 'bg-red-500') }}"></span>
                                @if ($stockState === 'ok')
                                    Stok tersedia
                                @elseif ($stockState === 'low')
                                    Stok terbatas ({{ $availableUnits }} unit)
                                @else
                                    Stok habis
                                @endif
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <!-- Bar sewa tetap di layar kecil -->
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-wheat-200 bg-white/95 px-4 py-3 shadow-[0_-8px_24px_-12px_rgba(43,34,2,0.25)] backdrop-blur lg:hidden">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[11px] text-darkbrown-500">Harga per hari</p>
                <p class="truncate text-lg font-extrabold leading-tight text-darkbrown-900">Rp {{ $formattedPrice }}</p>
            </div>
            <a href="{{ $checkoutUrl }}" class="btn-tendaku-primary shrink-0 rounded-2xl px-6 py-3 text-sm font-bold shadow-md">Sewa Paket</a>
        </div>
    </div>
</x-layouts.customer>
