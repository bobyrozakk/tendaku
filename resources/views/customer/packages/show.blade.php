<x-layouts.customer>
    <x-slot name="title">{{ $package->name }} - Paket Camping Tendaku</x-slot>

    @push('styles')
        <style>
            @media (max-width: 1023px) {
                body {
                    padding-bottom: calc(3.5rem + max(0.75rem, env(safe-area-inset-bottom)));
                }
            }
        </style>
    @endpush

    @php
        $image = $package->image_url ?: null;
        $price = (float) $package->price;
        $normalPrice = (float) $package->normal_price;
        $savings = (float) $package->savings;
        $vendor = $package->vendor;
        $vendorName = $vendor?->name ?? 'Tendaku Vendor';
        $storeUrl = $vendor ? route('customer.stores.show', $vendor->slug ?? $package->vendor_id) : null;
        $formattedPrice = number_format($price, 0, ',', '.');
        $duration = max(1, (int) $package->duration_days);
        $defaultTotal = $price * $duration;
        $unitCount = (int) $package->total_items_count;
        $typeCount = (int) $package->item_types_count;
        $rupiah = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
        $checkoutUrl = route('customer.packages.booking', ['id' => $package->id]);
        // Banding "hemat" HANYA ditampilkan kalau paket memang lebih murah dari sewa satuan.
        $isSaving = $savings > 0;
        $savingsPercent = $normalPrice > 0 ? (int) round($savings / $normalPrice * 100) : 0;

        $imageOf = fn ($item) => $item?->images?->pluck('image_url')->filter()->first() ?: ($item?->image_url ?: null);

        // Paket cuma punya 1 foto sendiri, jadi galerinya: foto paket + 1 foto tiap barang isi paket (maks. 5).
        $galleryImages = collect([$image])
            ->merge($package->packageItems->map(fn ($packageItem) => $imageOf($packageItem->masterItem)))
            ->filter()->unique()->values()->take(5)->all();

        $sectionHeadingClass = 'font-display text-xl font-normal text-darkbrown-900 sm:text-2xl';
        $visitStorePillClass = 'inline-flex min-h-[44px] shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-full bg-white/95 px-3.5 py-2 text-xs font-bold text-darkbrown-900 shadow-md backdrop-blur transition hover:bg-white hover:text-avocado-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-darkbrown-900';
    @endphp

    <div class="space-y-6">
        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-semibold text-darkbrown-500">
            <a href="{{ route('customer.packages.index') }}" class="inline-flex min-h-[44px] items-center gap-1 py-2 transition hover:text-avocado-700">
                <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                <span>Kembali ke Paket Camping</span>
            </a>
            <span aria-hidden="true">/</span>
            <span class="max-w-[16rem] truncate font-bold text-darkbrown-900" aria-current="page">{{ $package->name }}</span>
        </nav>

        {{--
            Layout (desktop): foto + detail di kiri, panel sewa (sticky) di kanan.
            Urutan DOM = urutan mobile: foto, panel sewa, detail.
        --}}
        <div class="grid grid-cols-1 items-start gap-x-6 gap-y-6 lg:grid-cols-12">

            <!-- Foto paket (carousel) + header toko -->
            <div class="lg:col-span-7 lg:col-start-1 lg:row-start-1">
                <x-photo-carousel :images="$galleryImages" :alt="$package->name">
                @if ($vendor)
                    {{-- Gradasi gelap di atas foto supaya teks header terbaca --}}
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-24 sm:h-32 bg-gradient-to-b from-darkbrown-900/80 via-darkbrown-900/30 to-transparent" aria-hidden="true"></div>

                    {{-- Header toko: identitas + lokasi di kiri, tombol pill di kanan --}}
                    <div class="absolute inset-x-0 top-0 flex items-start justify-between gap-3 p-3.5 sm:p-4">
                        <div class="flex min-w-0 items-center gap-3 text-white">
                            @if (! empty($vendor->logo_url))
                                <img src="{{ $vendor->logo_url }}" alt="{{ $vendorName }}" class="h-11 w-11 shrink-0 rounded-2xl border-2 border-white/80 bg-white object-cover" />
                            @else
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border-2 border-white/80 bg-gradient-to-br from-avocado-600 to-avocado-800 text-sm font-extrabold">
                                    {{ strtoupper(substr($vendorName, 0, 2)) }}
                                </div>
                            @endif

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <p class="min-w-0 truncate text-sm font-extrabold leading-tight drop-shadow">{{ $vendorName }}</p>
                                    <span class="rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-bold backdrop-blur">Official</span>
                                </div>
                                <p class="mt-0.5 flex items-center gap-1 text-xs text-white/90">
                                    <i data-lucide="map-pin" class="h-3 w-3 shrink-0"></i>
                                    <span class="truncate">{{ $vendor->city ?: 'Kota Penyedia' }}</span>
                                </p>
                            </div>
                        </div>

                        <a href="{{ $storeUrl }}" class="{{ $visitStorePillClass }}" title="Kunjungi Toko {{ $vendorName }}">
                            <i data-lucide="store" class="h-3.5 w-3.5"></i>
                            <span>Kunjungi Toko</span>
                        </a>
                    </div>
                @endif

                {{-- Label paket di kiri bawah --}}
                <div class="pointer-events-none absolute inset-x-4 flex flex-wrap items-center gap-2" style="bottom:1.75rem">
                    <span class="rounded-full bg-darkbrown-900/85 px-3.5 py-1.5 text-xs font-extrabold text-wheat-200 backdrop-blur-md">
                        Paket {{ $package->duration_days }} Hari
                    </span>
                    @if ($savings > 0)
                        <span class="rounded-full bg-sunglow-300 px-3.5 py-1.5 text-xs font-extrabold text-darkbrown-900 shadow-md">
                            Hemat Rp {{ number_format($savings, 0, ',', '.') }}/hari
                        </span>
                    @endif
                </div>
                </x-photo-carousel>
            </div>

            <!-- Panel sewa: nama, harga, ketersediaan, CTA -->
            <aside class="space-y-5 rounded-[2rem] border border-wheat-200/90 bg-gradient-to-br from-wheat-100 via-[#FDFBF7] to-sunglow-100 p-6 shadow-tendaku-sm sm:p-7 lg:sticky lg:top-24 lg:col-span-5 lg:col-start-8 lg:row-span-2 lg:row-start-1">
                <div class="space-y-4">
                    <h1 class="font-display font-normal tracking-tight break-words text-3xl leading-[1.08] text-darkbrown-900 sm:text-4xl">
                        {{ $package->name }}
                    </h1>

                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-widest text-darkbrown-500">Harga paket per hari</p>
                        <p class="mt-1 flex flex-wrap items-baseline gap-x-2">
                            <span class="text-3xl font-extrabold tracking-tight text-darkbrown-900 sm:text-4xl">Rp {{ $formattedPrice }}</span>
                            <span class="text-sm font-semibold text-darkbrown-500">/ hari</span>
                            @if ($isSaving)
                                <span class="text-sm font-semibold text-darkbrown-400 line-through" title="Harga bila semua alat disewa satuan">{{ $rupiah($normalPrice) }}</span>
                            @endif
                        </p>

                        @if ($isSaving)
                            <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-avocado-100 px-3 py-1 text-xs font-extrabold text-avocado-900">
                                <i data-lucide="tag" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                Hemat {{ $rupiah($savings) }}/hari{{ $savingsPercent >= 5 ? ' ('.$savingsPercent.'%)' : '' }}
                            </p>
                        @endif
                    </div>

                    <ul class="space-y-2 text-xs font-semibold text-darkbrown-800">
                        <li class="flex items-center gap-2">
                            <i data-lucide="package" class="h-4 w-4 shrink-0 text-avocado-700" aria-hidden="true"></i>
                            <span>{{ $unitCount }} alat sudah lengkap, tinggal berangkat</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="zap" class="h-4 w-4 shrink-0 text-avocado-700" aria-hidden="true"></i>
                            <span>Satu kali booking untuk semua alat</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <i data-lucide="sparkles" class="h-4 w-4 shrink-0 text-avocado-700" aria-hidden="true"></i>
                            <span>Steril, bersih, dan siap pakai</span>
                        </li>
                    </ul>
                </div>

                <div class="space-y-5 rounded-3xl border border-wheat-200 bg-white p-5 shadow-tendaku-sm">
                    <div class="space-y-3">
                        <h2 class="{{ $sectionHeadingClass }}">Ketersediaan</h2>
                        @include('livewire.customer.partials.availability-calendar', ['calendar' => $availabilityCalendar, 'embedded' => true, 'unitLabel' => 'paket'])
                    </div>

                    {{-- Desktop saja. Di mobile/tablet tombol ada di sticky bar bawah layar. --}}
                    <div class="hidden space-y-3 border-t border-wheat-100 pt-5 lg:block">
                        <a href="{{ $checkoutUrl }}"
                           class="btn-tendaku-primary w-full rounded-2xl py-3.5 text-sm font-bold shadow-md hover:shadow-lg">Sewa Paket</a>

                        <p class="text-center text-[11px] leading-relaxed text-darkbrown-500">
                            Tanggal dan cara pengambilan dipilih di langkah berikutnya.
                        </p>
                    </div>
                </div>

                <a href="{{ route('customer.catalog.index') }}"
                   class="flex min-h-[44px] items-center justify-center rounded-2xl border border-avocado-200 bg-avocado-50 px-4 py-2.5 text-center text-xs font-bold text-avocado-700 transition hover:bg-avocado-100 hover:text-avocado-800">
                    Ingin menyewa alat satuan? Lihat Katalog
                </a>
            </aside>

            <!-- Detail: deskripsi, informasi, isi paket -->
            <div class="space-y-6 lg:col-span-7 lg:col-start-1 lg:row-start-2">
                <section class="rounded-3xl border border-wheat-200/90 bg-white p-6 shadow-tendaku-sm" aria-labelledby="deskripsi-paket">
                    <h2 id="deskripsi-paket" class="{{ $sectionHeadingClass }}">
                        Deskripsi Paket
                    </h2>

                    <p class="mt-3 text-sm leading-relaxed text-darkbrown-700">
                        {{ $package->description ?: 'Satu paket, semua perlengkapan camping sudah lengkap. Tinggal pilih tanggal, bawa ransel, dan berangkat.' }}
                    </p>

                    <div class="mt-2 mb-2 my-5 border-t border-wheat-100"></div>
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 id="package-items-title" class="{{ $sectionHeadingClass }}">
                            Apa Saja yang Anda Dapat
                        </h2>

                        <span class="text-xs text-darkbrown-500">
                            <span class="font-bold text-darkbrown-900">{{ $unitCount }}</span> unit
                            @if ($typeCount !== $unitCount)
                                &middot; {{ $typeCount }} jenis alat
                            @endif
                        </span>
                    </div>

                    <ul class="mt-3 divide-y divide-wheat-100">
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
                            <li class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                                <div class="relative h-14 w-14 shrink-0 overflow-hidden rounded-2xl bg-gradient-to-br from-wheat-100 to-sunglow-100">
                                    <span class="absolute inset-0 flex items-center justify-center text-darkbrown-300" aria-hidden="true">
                                        <i data-lucide="image" class="h-5 w-5"></i>
                                    </span>
                                    @if ($itemImage)
                                        <img src="{{ $itemImage }}" alt="{{ $item?->name }}" loading="lazy" decoding="async"
                                             onerror="this.style.display='none'"
                                             class="relative h-full w-full object-cover {{ $stockState === 'out' ? 'opacity-40 grayscale' : '' }}" />
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <h3 class="truncate text-sm font-bold text-darkbrown-900">
                                        @if ($item)
                                            <a href="{{ route('customer.product.show', $item->id) }}" class="inline-flex min-h-[44px] sm:min-h-0 max-w-full items-center transition-colors hover:text-avocado-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500"><span class="truncate">{{ $item->name }}</span></a>
                                        @else
                                            Produk tidak tersedia
                                        @endif
                                    </h3>
                                    <p class="mt-0.5 truncate text-xs text-darkbrown-500">
                                        {{ $item?->category?->name ?? 'Alat Outdoor' }}
                                        @if ($item && $isSaving)
                                            &middot; <span class="line-through">Rp {{ number_format((float) $item->daily_rate, 0, ',', '.') }}/hari</span>
                                        @endif
                                    </p>
                                </div>

                                <div class="shrink-0 text-right">
                                    <span class="inline-block rounded-full bg-wheat-100 px-2.5 py-0.5 text-xs font-extrabold text-darkbrown-900">{{ $quantity }}x</span>
                                    <p class="mt-1 inline-flex items-center justify-end gap-1.5 text-[11px] font-semibold {{ $stockState === 'ok' ? 'text-avocado-700' : ($stockState === 'low' ? 'text-goldenrod-700' : 'text-red-600') }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $stockState === 'ok' ? 'bg-avocado-500' : ($stockState === 'low' ? 'bg-goldenrod-500' : 'bg-red-500') }}"></span>
                                        {{ $stockState === 'ok' ? 'Tersedia' : ($stockState === 'low' ? 'Terbatas' : 'Habis') }}
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <p class="mt-4 text-xs leading-relaxed text-darkbrown-500">
                        Stok dicek otomatis saat Anda checkout, jadi tidak ada kejutan di hari pengambilan.
                    </p>
                </section>

                <section class="rounded-3xl border border-wheat-200/90 bg-white p-6 shadow-tendaku-sm" aria-labelledby="informasi-paket">
                    <h2 id="informasi-paket" class="{{ $sectionHeadingClass }}">Ringkasan Paket</h2>
                    <dl class="mt-3 divide-y divide-wheat-100 text-sm">
                        <div class="py-3 first:pt-0">
                            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                                <dt class="text-darkbrown-600">Isi paket</dt>
                                <dd class="font-semibold text-darkbrown-900">{{ $unitCount }} unit{{ $typeCount !== $unitCount ? ' ('.$typeCount.' jenis)' : '' }}</dd>
                            </div>
                            <p class="mt-1 text-xs leading-relaxed text-darkbrown-500">
                                Semua sudah termasuk, tidak perlu menyewa terpisah.
                            </p>
                        </div>
                        <div class="py-3">
                            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                                <dt class="text-darkbrown-600">Cocok untuk</dt>
                                <dd class="font-semibold text-darkbrown-900">{{ $package->max_people ? 'hingga '.$package->max_people.' orang' : 'Fleksibel' }}</dd>
                            </div>
                        </div>
                        <div class="py-3">
                            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                                <dt class="text-darkbrown-600">Durasi yang disarankan</dt>
                                <dd class="font-semibold text-darkbrown-900">{{ $duration }} hari</dd>
                            </div>
                            <p class="mt-1 text-xs leading-relaxed text-darkbrown-500">
                                Boleh lebih singkat atau lebih lama, harga menyesuaikan.
                            </p>
                        </div>
                        <div class="py-3 last:pb-0">
                            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                                <dt class="text-darkbrown-600">Harga paket</dt>
                                <dd class="font-semibold text-darkbrown-900">{{ $rupiah($price) }} / hari</dd>
                            </div>
                            @if ($isSaving)
                                <p class="mt-1 text-xs leading-relaxed text-avocado-700">
                                    Sewa satuan: <span class="line-through">{{ $rupiah($normalPrice) }}</span>.
                                    Dengan paket Anda hemat <strong>{{ $rupiah($savings) }} per hari</strong>
                                    ({{ $rupiah($savings * $duration) }} untuk {{ $duration }} hari).
                                </p>
                            @else
                                <p class="mt-1 text-xs leading-relaxed text-darkbrown-500">
                                    Sudah mencakup semua alat.
                                </p>
                            @endif
                        </div>
                    </dl>
                </section>
            </div>
        </div>
    </div>

    <!-- Bar sewa tetap di layar kecil -->
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-wheat-200 bg-white/95 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] shadow-[0_-8px_24px_-12px_rgba(43,34,2,0.25)] backdrop-blur lg:hidden">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
            <div class="min-w-0">
                <p class="truncate text-[11px] text-darkbrown-500">
                    Harga paket / hari
                    @if ($isSaving)
                        <span class="font-bold text-avocado-700">&middot; hemat {{ $rupiah($savings) }}</span>
                    @endif
                </p>
                <p class="truncate text-lg font-extrabold leading-tight text-darkbrown-900">Rp {{ $formattedPrice }}</p>
            </div>
            <a href="{{ $checkoutUrl }}" class="btn-tendaku-primary shrink-0 rounded-2xl px-6 py-3 text-sm font-bold shadow-md">Sewa Paket</a>
        </div>
    </div>
</x-layouts.customer>
