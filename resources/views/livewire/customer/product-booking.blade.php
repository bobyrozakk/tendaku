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
    $fallbackPlaceholder = 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=1000&q=80';
    $mainImage = $itemImages->first() ?: $fallbackPlaceholder;
    $galleryImages = $itemImages->isNotEmpty() ? $itemImages->all() : [$fallbackPlaceholder];

    $storeUrl = $vendor ? route('customer.stores.show', $vendor->slug ?? $item->vendor_id) : null;
    $visitStoreClass = 'btn-tendaku-secondary !py-2 !px-3.5 text-xs font-bold shrink-0 hover:border-avocado-500 hover:text-avocado-700 cursor-pointer';
    $sectionHeadingClass = 'text-base font-extrabold text-darkbrown-900 font-heading';
@endphp

{{--
    Layout 3 kolom (desktop): foto | informasi produk | kartu beli (sticky).
    Urutan DOM = urutan mobile: foto, judul & harga, kartu beli, detail.
--}}
<div class="grid grid-cols-1 lg:grid-cols-12 gap-x-8 gap-y-6 items-start">

    {{-- Foto produk --}}
    <div x-data="{ activeImage: '{{ $mainImage }}' }"
         class="lg:col-start-1 lg:col-span-4 lg:row-start-1 lg:row-span-2 lg:sticky lg:top-24 space-y-3">
        <div class="aspect-square rounded-2xl overflow-hidden bg-wheat-100 border border-wheat-200">
            <img src="{{ $mainImage }}"
                 :src="activeImage"
                 alt="{{ $item->name }}"
                 class="w-full h-full object-cover transition-all duration-300"
                 id="mainProductImage"
            />
        </div>

        @if (count($galleryImages) > 1)
            <div class="flex flex-wrap gap-2">
                @foreach($galleryImages as $index => $imgUrl)
                    <button type="button"
                            @click="activeImage = '{{ $imgUrl }}'"
                            :class="activeImage === '{{ $imgUrl }}' ? 'ring-2 ring-avocado-500 border-transparent' : 'opacity-70 hover:opacity-100 border-wheat-200'"
                            class="relative w-16 h-16 rounded-lg overflow-hidden border bg-wheat-50 transition-all">
                        <img src="{{ $imgUrl }}" alt="Thumbnail {{ $index + 1 }}" class="w-full h-full object-cover"/>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Nama, harga sewa, jaminan --}}
    <div class="lg:col-start-5 lg:col-span-5 lg:row-start-1 space-y-4">
        <h1 class="font-heading font-extrabold text-2xl md:text-3xl leading-tight tracking-tight text-darkbrown-900">
            {{ $item->name }}
        </h1>

        <div>
            <p class="text-[11px] font-bold uppercase tracking-widest text-darkbrown-500">Tarif Sewa Peralatan</p>
            <p class="mt-1 flex items-baseline gap-2">
                <span class="text-3xl md:text-4xl font-extrabold text-darkbrown-900 font-heading">
                    Rp {{ number_format($item->daily_rate, 0, ',', '.') }}
                </span>
                <span class="text-sm font-semibold text-darkbrown-500">/ hari</span>
            </p>
        </div>
    </div>

    {{-- Kartu beli: ketersediaan, jumlah, CTA --}}
    <aside id="booking-action-panel"
           class="lg:col-start-10 lg:col-span-3 lg:row-start-1 lg:row-span-2 lg:sticky lg:top-24 card-tendaku p-5 space-y-5"
           aria-labelledby="ketersediaan-peralatan">
        <div class="space-y-3">
            <h2 id="ketersediaan-peralatan" class="{{ $sectionHeadingClass }}">Ketersediaan</h2>

            <x-stock-period :date-range="$dateRange" />

            <div class="space-y-2">
                <label for="quantity" class="text-xs font-bold text-darkbrown-800 uppercase tracking-wider flex items-center gap-1.5">
                    <i data-lucide="package" class="w-4 h-4 text-avocado-600"></i>
                    <span>Jumlah Unit</span>
                </label>

                @if($this->availableStock > 0)
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="$set('quantity', {{ max(1, $quantity - 1) }})"
                            @disabled($quantity <= 1)
                            class="w-10 h-10 rounded-xl border border-wheat-300 bg-wheat-100 hover:bg-wheat-200 text-darkbrown-800 font-extrabold flex items-center justify-center transition disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                            title="Kurangi unit"
                        >
                            <i data-lucide="minus" class="w-4 h-4"></i>
                        </button>
                        <input
                            type="number"
                            id="quantity"
                            wire:model.live="quantity"
                            min="1"
                            max="{{ max(1, $this->availableStock) }}"
                            class="input-tendaku min-w-0 flex-1 text-center font-bold text-sm py-2 px-2 rounded-xl border-wheat-300"
                            required
                        />
                        <button
                            type="button"
                            wire:click="$set('quantity', {{ $quantity + 1 }})"
                            @disabled($quantity >= $this->availableStock)
                            class="w-10 h-10 rounded-xl border border-wheat-300 bg-wheat-100 hover:bg-wheat-200 text-darkbrown-800 font-extrabold flex items-center justify-center transition disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                            title="Tambah unit"
                        >
                            <i data-lucide="plus" class="w-4 h-4"></i>
                        </button>
                    </div>
                @else
                    <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-semibold flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-red-500 shrink-0"></i>
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

        <div class="space-y-3">
            {{-- Khusus anggota: muncul jika produk ini sudah ada di keranjang --}}
            @if($inCart && ! $isGuest)
                <div class="p-3.5 rounded-xl bg-avocado-50 border border-avocado-200 flex flex-col gap-2">
                    <p class="text-xs font-semibold text-avocado-900 flex items-center gap-1.5">
                        <i data-lucide="shopping-cart" class="w-3.5 h-3.5"></i>
                        Produk ini sudah ada di keranjangmu!
                    </p>
                    <button
                        type="button"
                        wire:click="proceedToBooking"
                        wire:loading.attr="disabled"
                        class="btn-tendaku-primary w-full py-2.5 px-4 rounded-xl text-xs font-bold text-center inline-flex items-center justify-center gap-1.5 shadow-md hover:shadow-lg transition-all"
                    >
                        <i data-lucide="arrow-right-circle" class="w-4 h-4"></i>
                        <span>Lanjut ke Checkout</span>
                    </button>
                </div>
            @endif

            @include('livewire.customer.partials.product-booking-actions', ['idSuffix' => ''])

            @if($isGuest)
                <p class="text-[11px] leading-relaxed text-darkbrown-500 text-center">
                    Langsung checkout tanpa akun. Isi jadwal sewa dan data diri di halaman Booking.
                </p>
            @endif
        </div>
    </aside>

    {{-- Detail: deskripsi, informasi, vendor, jadwal --}}
    <div class="lg:col-start-5 lg:col-span-5 lg:row-start-2 divide-y divide-wheat-200 border-t border-wheat-200">

        <section class="py-6" aria-labelledby="deskripsi-peralatan">
            <h2 id="deskripsi-peralatan" class="{{ $sectionHeadingClass }}">Deskripsi Peralatan</h2>
            <p class="mt-3 text-sm text-darkbrown-700 leading-relaxed">
                {{ $item->description ?: 'Peralatan outdoor kualitas standar pendakian gunung, steril & siap pakai.' }}
            </p>
        </section>

        <section class="py-6" aria-labelledby="informasi-peralatan">
            <h2 id="informasi-peralatan" class="{{ $sectionHeadingClass }}">Informasi Peralatan</h2>
            <dl class="mt-3 text-sm space-y-2">
                <div class="flex gap-4">
                    <dt class="w-44 shrink-0 text-darkbrown-600">Kategori Alat</dt>
                    <dd class="font-semibold text-darkbrown-900">{{ $categoryName }}</dd>
                </div>
                <div class="flex gap-4">
                    <dt class="w-44 shrink-0 text-darkbrown-600">Kondisi Unit Fisik</dt>
                    <dd class="font-semibold text-avocado-800">{{ $dominantCondition }}</dd>
                </div>
                <div class="flex gap-4">
                    <dt class="w-44 shrink-0 text-darkbrown-600">Umur / Masa Pakai</dt>
                    <dd class="font-semibold text-darkbrown-900">{{ $itemAge }}</dd>
                </div>
                <div class="flex gap-4">
                    <dt class="w-44 shrink-0 text-darkbrown-600">Denda Keterlambatan</dt>
                    <dd class="font-semibold text-red-700">Rp {{ number_format($item->late_fee_per_day, 0, ',', '.') }} / hari</dd>
                </div>
            </dl>
        </section>

        @if($vendor)
            <section class="py-6" aria-labelledby="vendor-peralatan">
                <h2 id="vendor-peralatan" class="sr-only">Vendor</h2>
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5 min-w-0">
                        @if(! empty($vendor->logo_url))
                            <img src="{{ $vendor->logo_url }}" alt="{{ $vendorName }}" class="w-12 h-12 rounded-xl object-cover border border-wheat-200 shrink-0" />
                        @else
                            <div class="w-12 h-12 rounded-xl bg-darkbrown-800 text-sunglow-300 font-extrabold flex items-center justify-center font-heading text-sm shrink-0">
                                {{ strtoupper(substr($vendorName, 0, 2)) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-extrabold text-sm text-darkbrown-900 font-heading truncate">{{ $vendorName }}</h3>
                                <span class="badge-avocado text-[10px]">Official</span>
                            </div>
                            <p class="mt-0.5 text-xs text-darkbrown-500 flex items-center gap-1">
                                <i data-lucide="map-pin" class="w-3 h-3 text-sunglow-500"></i>
                                <span>{{ $vendor->city ?: 'Kota Penyedia' }}</span>
                                @if($vendor->items_count ?? false)
                                    <span>&bull; {{ $vendor->items_count }} alat disewakan</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    @auth
                        <a href="{{ $storeUrl }}" class="{{ $visitStoreClass }}">
                            <i data-lucide="store" class="w-3.5 h-3.5 text-darkbrown-600"></i>
                            <span>Kunjungi Toko</span>
                        </a>
                    @else
                        <button type="button" @click="$dispatch('open-guest-store-modal')" class="{{ $visitStoreClass }}">
                            <i data-lucide="store" class="w-3.5 h-3.5 text-darkbrown-600"></i>
                            <span>Kunjungi Toko</span>
                        </button>
                    @endauth
                </div>
            </section>
        @endif

        {{-- Syarat dan Ketentuan (dropdown) --}}
        <section class="py-6" x-data="{ open: false }">
            <h2 class="{{ $sectionHeadingClass }}">
                <button type="button"
                        @click="open = !open"
                        :aria-expanded="open"
                        aria-controls="syarat-ketentuan"
                        class="flex w-full items-center justify-between gap-3 text-left cursor-pointer">
                    <span>Syarat dan Ketentuan</span>
                    <span class="shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open }">
                        <i data-lucide="chevron-down" class="w-5 h-5 text-darkbrown-500"></i>
                    </span>
                </button>
            </h2>
            <ul id="syarat-ketentuan" x-show="open" x-cloak class="mt-4 space-y-3 text-sm text-darkbrown-600 leading-relaxed">
                <li class="flex items-start gap-2.5">
                    <i data-lucide="credit-card" class="w-4 h-4 text-avocado-600 shrink-0 mt-0.5"></i>
                    <p><span class="font-bold text-darkbrown-900">Jaminan KTP Asli.</span> Bawa <strong class="text-darkbrown-800">KTP Asli</strong> saat pickup barang di toko vendor.</p>
                </li>
                <li class="flex items-start gap-2.5">
                    <i data-lucide="wallet" class="w-4 h-4 text-avocado-600 shrink-0 mt-0.5"></i>
                    <p><span class="font-bold text-darkbrown-900">Pembayaran.</span> Pembayaran dapat lunas di awal atau dp.</p>
                </li>
                <li class="flex items-start gap-2.5">
                    <i data-lucide="shield-check" class="w-4 h-4 text-avocado-600 shrink-0 mt-0.5"></i>
                    <p><span class="font-bold text-darkbrown-900">Biaya.</span> Transaksi aman, tidak ada biaya tersembunyi.</p>
                </li>
                <li class="flex items-start gap-2.5">
                    <i data-lucide="sparkles" class="w-4 h-4 text-avocado-600 shrink-0 mt-0.5"></i>
                    <p><span class="font-bold text-darkbrown-900">Kondisi Unit.</span> Unit steril dan diperiksa sebelum serah terima.</p>
                </li>
                <li class="flex items-start gap-2.5">
                    <i data-lucide="calendar" class="w-4 h-4 text-avocado-600 shrink-0 mt-0.5"></i>
                    <p><span class="font-bold text-darkbrown-900">Jadwal &amp; Pengambilan.</span> Tanggal mulai sewa, durasi, dan metode serah terima dipilih di halaman <strong class="text-darkbrown-800">Booking</strong>.</p>
                </li>
            </ul>
        </section>
    </div>
</div>
