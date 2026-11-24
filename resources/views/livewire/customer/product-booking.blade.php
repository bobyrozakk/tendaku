@php
    $item = $this->product;
    $vendor = $item->vendor;
    $vendorName = $vendor?->name ?? 'Tendaku Vendor';
    $categoryName = $item->category?->name ?? 'Alat Outdoor';
    $dominantCondition = $item->dominant_condition;
    $itemAge = $item->item_age;

    $isGuest = auth()->guest();
    $canSubmit = $this->availableStock >= $this->quantity && $this->availableStock > 0;

    $itemImages = $item->images->pluck('image_url')->filter()->values();
    if ($itemImages->isEmpty() && $item->image_url) {
        $itemImages = collect([$item->image_url]);
    }
    $mainImage = $itemImages->first();
    $galleryImages = $itemImages->all();

    $storeUrl = $vendor ? route('customer.stores.show', $vendor->slug ?? $item->vendor_id) : null;
    $visitStoreClass = 'inline-flex shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-full border border-wheat-300/80 bg-wheat-100 px-3.5 py-2 text-xs font-bold text-darkbrown-800 transition hover:border-avocado-500 hover:bg-wheat-200 hover:text-avocado-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2';
    $sectionHeadingClass = 'text-lg font-extrabold tracking-tight text-darkbrown-900';
    $stepperClass = 'flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-full border border-wheat-300 bg-wheat-100 font-extrabold text-darkbrown-800 transition hover:bg-wheat-200 disabled:cursor-not-allowed disabled:opacity-40';
@endphp

{{--
    Layout (desktop): foto + detail di kiri, panel beli (sticky) di kanan.
    Urutan DOM = urutan mobile: foto, panel beli, detail.
--}}
<div class="grid grid-cols-1 items-start gap-x-6 gap-y-6 lg:grid-cols-12">

    {{-- Foto produk --}}
    <div @if($mainImage) x-data="{ activeImage: @js($mainImage) }" @endif
         class="lg:col-span-7 lg:col-start-1 lg:row-start-1">
        <div class="rounded-[2rem] border border-wheat-200/90 bg-white p-2.5 shadow-tendaku-sm">
            <div class="relative aspect-[4/3] overflow-hidden rounded-3xl bg-gradient-to-br from-wheat-100 to-sunglow-100">
                <span class="absolute inset-0 flex items-center justify-center text-darkbrown-300" aria-hidden="true">
                    <i data-lucide="tent" class="h-14 w-14"></i>
                </span>

                @if($mainImage)
                    <img src="{{ $mainImage }}"
                         :src="activeImage"
                         alt="{{ $item->name }}"
                         decoding="async"
                         onerror="this.style.display='none'"
                         class="relative h-full w-full object-cover transition-all duration-300"
                         id="mainProductImage"
                    />
                @endif
            </div>

            @if (count($galleryImages) > 1)
                <div class="flex flex-wrap gap-2 px-1 pb-1 pt-3">
                    @foreach($galleryImages as $index => $imgUrl)
                        <button type="button"
                                @click="activeImage = @js($imgUrl)"
                                :class="activeImage === @js($imgUrl) ? 'ring-2 ring-avocado-500 ring-offset-2 border-transparent' : 'opacity-70 hover:opacity-100 border-wheat-200'"
                                aria-label="Tampilkan foto {{ $index + 1 }}"
                                class="relative h-16 w-16 overflow-hidden rounded-2xl border bg-wheat-50 transition-all">
                            <img src="{{ $imgUrl }}" alt="Thumbnail {{ $index + 1 }}" loading="lazy" class="h-full w-full object-cover"/>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Panel beli: nama, harga sewa, ketersediaan, jumlah, CTA --}}
    <aside id="booking-action-panel"
           class="space-y-5 rounded-[2rem] border border-wheat-200/90 bg-gradient-to-br from-wheat-100 via-[#FDFBF7] to-sunglow-100 p-6 shadow-tendaku-sm sm:p-7 lg:col-span-5 lg:col-start-8 lg:row-span-2 lg:row-start-1 lg:sticky lg:top-24"
           aria-labelledby="ketersediaan-peralatan">
        <div class="space-y-4">
            <h1 class="break-words text-3xl font-extrabold leading-[1.08] tracking-tighter text-darkbrown-900 sm:text-4xl">
                {{ $item->name }}
            </h1>

            <div>
                <p class="text-[11px] font-bold uppercase tracking-widest text-darkbrown-500">Tarif Sewa Peralatan</p>
                <p class="mt-1 flex flex-wrap items-baseline gap-x-2">
                    <span class="text-3xl font-extrabold tracking-tight text-darkbrown-900 sm:text-4xl">
                        Rp {{ number_format($item->daily_rate, 0, ',', '.') }}
                    </span>
                    <span class="text-sm font-semibold text-darkbrown-500">/ hari</span>
                </p>
            </div>
        </div>

        <div class="space-y-5 rounded-3xl border border-wheat-200 bg-white p-5 shadow-tendaku-sm">
            <div class="space-y-3">
                <h2 id="ketersediaan-peralatan" class="{{ $sectionHeadingClass }}">Ketersediaan</h2>

                <x-stock-period :date-range="$dateRange" />

                <div class="space-y-2">
                    <label for="quantity" class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-darkbrown-800">
                        <i data-lucide="package" class="h-4 w-4 text-avocado-600"></i>
                        <span>Jumlah Unit</span>
                    </label>

                    @if($this->availableStock > 0)
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                wire:click="$set('quantity', {{ max(1, $quantity - 1) }})"
                                @disabled($quantity <= 1)
                                class="{{ $stepperClass }}"
                                title="Kurangi unit"
                            >
                                <i data-lucide="minus" class="h-4 w-4"></i>
                            </button>
                            <input
                                type="number"
                                id="quantity"
                                wire:model.live="quantity"
                                min="1"
                                max="{{ max(1, $this->availableStock) }}"
                                class="input-tendaku min-w-0 flex-1 rounded-full border-wheat-300 px-2 py-2 text-center text-sm font-bold"
                                required
                            />
                            <button
                                type="button"
                                wire:click="$set('quantity', {{ $quantity + 1 }})"
                                @disabled($quantity >= $this->availableStock)
                                class="{{ $stepperClass }}"
                                title="Tambah unit"
                            >
                                <i data-lucide="plus" class="h-4 w-4"></i>
                            </button>
                        </div>
                    @else
                        <div class="flex items-center gap-2 rounded-2xl border border-red-200 bg-red-50 p-3 text-xs font-semibold text-red-700">
                            <i data-lucide="alert-triangle" class="h-4 w-4 shrink-0 text-red-500"></i>
                            <span>{{ $this->availableStock === null ? 'Ketersediaan belum dapat diperiksa. Periksa jadwal atau coba lagi.' : 'Tidak tersedia pada periode ini. Silakan pilih jadwal lain.' }}</span>
                        </div>
                    @endif

                    <x-stock-status :stock="$this->availableStock" />

                    @error('quantity')
                        <p class="flex items-center gap-1 text-[11px] font-semibold text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <p class="text-xs leading-relaxed text-darkbrown-500">
                    Stok mengikuti periode acuan dan belum merupakan reservasi.
                </p>
            </div>

            <div class="space-y-3 border-t border-wheat-100 pt-5">
                {{-- Khusus anggota: muncul jika produk ini sudah ada di keranjang --}}
                @if($inCart && ! $isGuest)
                    <div class="flex flex-col gap-2 rounded-2xl border border-avocado-200 bg-avocado-50 p-3.5">
                        <p class="flex items-center gap-1.5 text-xs font-semibold text-avocado-900">
                            <i data-lucide="shopping-cart" class="h-3.5 w-3.5"></i>
                            Produk ini sudah ada di keranjangmu!
                        </p>
                        <button
                            type="button"
                            wire:click="proceedToBooking"
                            wire:loading.attr="disabled"
                            class="btn-tendaku-primary inline-flex w-full items-center justify-center gap-1.5 rounded-2xl px-4 py-2.5 text-center text-xs font-bold shadow-md transition-all hover:shadow-lg"
                        >
                            <i data-lucide="arrow-right-circle" class="h-4 w-4"></i>
                            <span>Lanjut ke Checkout</span>
                        </button>
                    </div>
                @endif

                @include('livewire.customer.partials.product-booking-actions', ['idSuffix' => ''])

                @if($isGuest)
                    <p class="text-center text-[11px] leading-relaxed text-darkbrown-500">
                        Langsung checkout tanpa akun. Isi jadwal sewa dan data diri di halaman Booking.
                    </p>
                @endif
            </div>
        </div>
    </aside>

    {{-- Detail: deskripsi, informasi, vendor, syarat & ketentuan --}}
    <div class="space-y-6 lg:col-span-7 lg:col-start-1 lg:row-start-2">

        <section class="rounded-3xl border border-wheat-200/90 bg-white p-6 shadow-tendaku-sm" aria-labelledby="deskripsi-peralatan">
            <h2 id="deskripsi-peralatan" class="{{ $sectionHeadingClass }}">Deskripsi Peralatan</h2>
            <p class="mt-3 text-sm leading-relaxed text-darkbrown-700">
                {{ $item->description ?: 'Peralatan outdoor kualitas standar pendakian gunung, steril & siap pakai.' }}
            </p>
        </section>

        <section class="rounded-3xl border border-wheat-200/90 bg-white p-6 shadow-tendaku-sm" aria-labelledby="informasi-peralatan">
            <h2 id="informasi-peralatan" class="{{ $sectionHeadingClass }}">Informasi Peralatan</h2>
            <dl class="mt-3 divide-y divide-wheat-100 text-sm">
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-3 first:pt-0">
                    <dt class="text-darkbrown-600">Kategori Alat</dt>
                    <dd class="font-semibold text-darkbrown-900">{{ $categoryName }}</dd>
                </div>
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-3">
                    <dt class="text-darkbrown-600">Kondisi Unit Fisik</dt>
                    <dd class="font-semibold text-avocado-800">{{ $dominantCondition }}</dd>
                </div>
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-3">
                    <dt class="text-darkbrown-600">Umur / Masa Pakai</dt>
                    <dd class="font-semibold text-darkbrown-900">{{ $itemAge }}</dd>
                </div>
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 py-3 last:pb-0">
                    <dt class="text-darkbrown-600">Denda Keterlambatan</dt>
                    <dd class="font-semibold text-red-700">Rp {{ number_format($item->late_fee_per_day, 0, ',', '.') }} / hari</dd>
                </div>
            </dl>
        </section>

        @if($vendor)
            <section class="rounded-3xl border border-wheat-200/90 bg-white p-5 shadow-tendaku-sm sm:p-6" aria-labelledby="vendor-peralatan">
                <h2 id="vendor-peralatan" class="sr-only">Vendor</h2>
                <div class="flex items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-3.5">
                        @if(! empty($vendor->logo_url))
                            <img src="{{ $vendor->logo_url }}" alt="{{ $vendorName }}" class="h-12 w-12 shrink-0 rounded-2xl border border-wheat-200 object-cover" />
                        @else
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-avocado-600 to-avocado-800 text-sm font-extrabold text-white">
                                {{ strtoupper(substr($vendorName, 0, 2)) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="truncate text-sm font-extrabold text-darkbrown-900">{{ $vendorName }}</h3>
                                <span class="badge-avocado text-[10px]">Official</span>
                            </div>
                            <p class="mt-0.5 flex items-center gap-1 text-xs text-darkbrown-500">
                                <i data-lucide="map-pin" class="h-3 w-3 text-goldenrod-600"></i>
                                <span>{{ $vendor->city ?: 'Kota Penyedia' }}</span>
                                @if($vendor->items_count ?? false)
                                    <span>&bull; {{ $vendor->items_count }} alat disewakan</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    @auth
                        <a href="{{ $storeUrl }}" class="{{ $visitStoreClass }}">
                            <i data-lucide="store" class="h-3.5 w-3.5 text-darkbrown-600"></i>
                            <span>Kunjungi Toko</span>
                        </a>
                    @else
                        <button type="button" @click="$dispatch('open-guest-store-modal')" class="{{ $visitStoreClass }}">
                            <i data-lucide="store" class="h-3.5 w-3.5 text-darkbrown-600"></i>
                            <span>Kunjungi Toko</span>
                        </button>
                    @endauth
                </div>
            </section>
        @endif

        {{-- Syarat dan Ketentuan (dropdown) --}}
        <section class="rounded-3xl border border-wheat-200/90 bg-white p-6 shadow-tendaku-sm" x-data="{ open: false }">
            <h2 class="{{ $sectionHeadingClass }}">
                <button type="button"
                        @click="open = !open"
                        :aria-expanded="open"
                        aria-controls="syarat-ketentuan"
                        class="flex w-full cursor-pointer items-center justify-between gap-3 text-left">
                    <span>Syarat dan Ketentuan</span>
                    <span class="shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open }">
                        <i data-lucide="chevron-down" class="h-5 w-5 text-darkbrown-500"></i>
                    </span>
                </button>
            </h2>
            <ul id="syarat-ketentuan" x-show="open" x-cloak class="mt-4 space-y-3 text-sm leading-relaxed text-darkbrown-600">
                <li class="flex items-start gap-2.5">
                    <i data-lucide="credit-card" class="mt-0.5 h-4 w-4 shrink-0 text-avocado-600"></i>
                    <p><span class="font-bold text-darkbrown-900">Jaminan KTP Asli.</span> Bawa <strong class="text-darkbrown-800">KTP Asli</strong> saat pickup barang di toko vendor.</p>
                </li>
                <li class="flex items-start gap-2.5">
                    <i data-lucide="wallet" class="mt-0.5 h-4 w-4 shrink-0 text-avocado-600"></i>
                    <p><span class="font-bold text-darkbrown-900">Pembayaran.</span> Pembayaran dapat lunas di awal atau dp.</p>
                </li>
                <li class="flex items-start gap-2.5">
                    <i data-lucide="shield-check" class="mt-0.5 h-4 w-4 shrink-0 text-avocado-600"></i>
                    <p><span class="font-bold text-darkbrown-900">Biaya.</span> Transaksi aman, tidak ada biaya tersembunyi.</p>
                </li>
                <li class="flex items-start gap-2.5">
                    <i data-lucide="sparkles" class="mt-0.5 h-4 w-4 shrink-0 text-avocado-600"></i>
                    <p><span class="font-bold text-darkbrown-900">Kondisi Unit.</span> Unit steril dan diperiksa sebelum serah terima.</p>
                </li>
                <li class="flex items-start gap-2.5">
                    <i data-lucide="calendar" class="mt-0.5 h-4 w-4 shrink-0 text-avocado-600"></i>
                    <p><span class="font-bold text-darkbrown-900">Jadwal &amp; Pengambilan.</span> Tanggal mulai sewa, durasi, dan metode serah terima dipilih di halaman <strong class="text-darkbrown-800">Booking</strong>.</p>
                </li>
            </ul>
        </section>
    </div>
</div>
