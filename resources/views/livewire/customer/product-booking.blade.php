<div class="card-tendaku rounded-2xl bg-white border border-wheat-200 shadow-tendaku-lg sticky top-6 overflow-hidden">

    {{-- HEADER: Harga & Tagline "Tanpa Deposit" --}}
    <div class="bg-gradient-to-br from-darkbrown-900 to-darkbrown-800 px-6 pt-6 pb-5">
        <span class="inline-block text-[10px] font-bold uppercase tracking-widest text-wheat-400 mb-1">
            Tarif Sewa Peralatan
        </span>
        <div class="flex items-baseline gap-2">
            <span class="text-3xl md:text-4xl font-extrabold text-white font-heading">
                Rp {{ number_format($this->product->daily_rate, 0, ',', '.') }}
            </span>
            <span class="text-sm font-semibold text-wheat-400">/ hari</span>
        </div>
        <div class="flex items-center gap-1.5 mt-2">
            <span class="w-2 h-2 rounded-full bg-avocado-400 animate-pulse flex-shrink-0"></span>
            <span class="text-[11px] text-avocado-300 font-semibold">
                Jaminan KTP Asli — Tanpa Deposit Uang
            </span>
        </div>
    </div>

    <div class="p-6 space-y-5">        {{-- VENDOR BADGE & UMUR BARANG --}}
        <div class="p-3.5 rounded-xl bg-wheat-50 border border-wheat-200 space-y-2">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    @if($this->product->vendor)
                        @auth
                            <a href="{{ route('customer.stores.show', $this->product->vendor->slug ?? $this->product->vendor_id) }}" 
                               class="w-9 h-9 rounded-lg bg-darkbrown-800 text-sunglow-300 flex items-center justify-center flex-shrink-0 hover:bg-darkbrown-900 transition"
                               title="Kunjungi Profil Toko {{ $this->product->vendor->name }}">
                                <i data-lucide="store" class="w-4 h-4"></i>
                            </a>
                        @else
                            <button type="button" 
                                    @click="$dispatch('open-guest-store-modal')" 
                                    class="w-9 h-9 rounded-lg bg-darkbrown-800 text-sunglow-300 flex items-center justify-center flex-shrink-0 hover:bg-darkbrown-900 transition cursor-pointer"
                                    title="Kunjungi Profil Toko {{ $this->product->vendor->name }}">
                                <i data-lucide="store" class="w-4 h-4"></i>
                            </button>
                        @endauth
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-darkbrown-900 truncate">
                                @auth
                                    <a href="{{ route('customer.stores.show', $this->product->vendor->slug ?? $this->product->vendor_id) }}" 
                                       class="hover:text-avocado-700 transition underline-offset-2 hover:underline">
                                        {{ $this->product->vendor->name }}
                                    </a>
                                @else
                                    <button type="button" 
                                            @click="$dispatch('open-guest-store-modal')" 
                                            class="hover:text-avocado-700 transition underline-offset-2 hover:underline cursor-pointer font-bold text-left truncate block max-w-full">
                                        {{ $this->product->vendor->name }}
                                    </button>
                                @endauth
                            </h4>
                            <p class="text-[10px] text-darkbrown-500 font-medium truncate">
                                {{ $this->product->vendor->city ?? 'Penyedia Resmi' }}
                            </p>
                        </div>
                    @else
                        <div class="w-9 h-9 rounded-lg bg-darkbrown-800 text-sunglow-300 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="store" class="w-4 h-4"></i>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-darkbrown-900 truncate">
                                {{ $this->product->vendor?->name ?? 'Tendaku Store' }}
                            </h4>
                            <p class="text-[10px] text-darkbrown-500 font-medium truncate">
                                {{ $this->product->vendor?->city ?? 'Penyedia Resmi' }}
                            </p>
                        </div>
                    @endif
                </div>
                @if($this->product->vendor)
                    @auth
                        <a href="{{ route('customer.stores.show', $this->product->vendor->slug ?? $this->product->vendor_id) }}" 
                           class="badge-avocado text-[10px] flex-shrink-0 inline-flex items-center gap-1 hover:bg-avocado-200 transition"
                           title="Kunjungi Profil Toko">
                            <i data-lucide="store" class="w-3 h-3"></i>
                            <span>Kunjungi Toko</span>
                        </a>
                    @else
                        <button type="button" 
                                @click="$dispatch('open-guest-store-modal')" 
                                class="badge-avocado text-[10px] flex-shrink-0 inline-flex items-center gap-1 hover:bg-avocado-200 transition cursor-pointer"
                                title="Kunjungi Profil Toko">
                            <i data-lucide="store" class="w-3 h-3"></i>
                            <span>Kunjungi Toko</span>
                        </button>
                    @endauth
                @else
                    <span class="badge-avocado text-[10px] flex-shrink-0 inline-flex items-center gap-1">
                        <i data-lucide="badge-check" class="w-3 h-3"></i>
                        <span>Vendor Aktif</span>
                    </span>
                @endif
            </div>

            <div class="pt-2 border-t border-wheat-200 flex items-center justify-between text-[11px] text-darkbrown-600 font-medium">
                <span class="inline-flex items-center gap-1">
                    <i data-lucide="clock" class="w-3 h-3 text-darkbrown-500"></i>
                    {{ $this->product->item_age }}
                </span>
                <span class="font-semibold text-avocado-800 inline-flex items-center gap-1">
                    <i data-lucide="shield" class="w-3 h-3 text-avocado-600"></i>
                    {{ $this->product->dominant_condition }}
                </span>
            </div>
        </div>

        {{-- AKSI SEWA & KUANTITAS (FORM SEWA PINDAH KE HALAMAN BOOKING) --}}
        @php
            $isGuest = auth()->guest();
            $canSubmit = $this->availableStock >= $this->quantity && $this->availableStock > 0;
        @endphp
        <div id="booking-action-panel" class="space-y-4">

            {{-- Jumlah Unit (Quantity) --}}
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label for="quantity" class="text-xs font-bold text-darkbrown-800 uppercase tracking-wider flex items-center gap-1.5">
                        <i data-lucide="package" class="w-4 h-4 text-avocado-600"></i>
                        <span>Jumlah Unit</span>
                    </label>
                    <span class="text-xs font-semibold">
                        Stok:
                        <strong class="{{ $this->availableStock > 0 ? 'text-avocado-700' : 'text-red-600' }}">
                            {{ $this->availableStock > 0 ? $this->availableStock . ' Unit' : 'Stok Habis' }}
                        </strong>
                    </span>
                </div>

                @if($this->availableStock > 0)
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            wire:click="$set('quantity', {{ max(1, $quantity - 1) }})"
                            @disabled($quantity <= 1)
                            class="w-10 h-10 rounded-xl border border-wheat-300 bg-wheat-100 hover:bg-wheat-200 text-darkbrown-800 font-extrabold flex items-center justify-center transition disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                            title="Kurangi unit"
                        >
                            -
                        </button>
                        <input
                            type="number"
                            id="quantity"
                            wire:model.live="quantity"
                            min="1"
                            max="{{ max(1, $this->availableStock) }}"
                            class="input-tendaku flex-1 text-center font-bold text-sm py-2 px-3 rounded-xl border-wheat-300"
                            required
                        />
                        <button
                            type="button"
                            wire:click="$set('quantity', {{ $quantity + 1 }})"
                            @disabled($quantity >= $this->availableStock)
                            class="w-10 h-10 rounded-xl border border-wheat-300 bg-wheat-100 hover:bg-wheat-200 text-darkbrown-800 font-extrabold flex items-center justify-center transition disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                            title="Tambah unit"
                        >
                            +
                        </button>
                    </div>
                @else
                    <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-semibold flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-red-500 shrink-0"></i>
                        <span>Maaf, stok peralatan ini sedang tidak tersedia untuk saat ini.</span>
                    </div>
                @endif

                @error('quantity')
                    <p class="mt-1 flex items-center gap-1 text-[11px] font-semibold text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Keterangan Alur Booking --}}
            <div class="p-3.5 rounded-xl bg-wheat-50 border border-wheat-200 text-darkbrown-700 flex items-start gap-2.5">
                <i data-lucide="calendar" class="w-4 h-4 text-avocado-600 shrink-0 mt-0.5"></i>
                <div class="text-xs leading-relaxed">
                    <span class="font-bold text-darkbrown-900">Jadwal & Pengambilan</span>
                    <p class="text-[11px] text-darkbrown-600 mt-0.5">
                        Tanggal mulai sewa, durasi hari, dan metode serah terima dipilih langsung di halaman <strong>Booking</strong>.
                    </p>
                </div>
            </div>

            {{-- TOMBOL UTAMA: Sewa Sekarang / Checkout / Masuk Keranjang --}}
            @if($isGuest)
                <div class="space-y-2 pt-1">
                    <button
                        type="button"
                        wire:click="checkoutNow"
                        id="btn-checkout"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-wait"
                        @class([
                            'w-full py-3.5 px-6 rounded-xl font-extrabold text-sm shadow-md transition-all duration-200',
                            'flex items-center justify-center gap-2',
                            'btn-tendaku-primary hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 cursor-pointer' => $canSubmit,
                            'bg-wheat-200 text-darkbrown-400 cursor-not-allowed opacity-60' => !$canSubmit,
                        ])
                        @disabled(!$canSubmit)
                    >
                        <span wire:loading.remove wire:target="checkoutNow" class="flex items-center gap-2">
                            @if(!$canSubmit)
                                <i data-lucide="alert-circle" class="w-4 h-4"></i>
                                <span>Stok Tidak Mencukupi</span>
                            @else
                                <i data-lucide="arrow-right-circle" class="w-4 h-4"></i>
                                <span>Checkout Sekarang</span>
                            @endif
                        </span>

                        <span wire:loading wire:target="checkoutNow" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span>Menyiapkan checkout...</span>
                        </span>
                    </button>

                    <p class="text-[11px] leading-relaxed text-darkbrown-500 text-center">
                        Langsung checkout tanpa akun. Isi jadwal sewa dan data diri di halaman Booking.
                    </p>
                </div>
            @else
                <div class="space-y-2 pt-1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <button
                            type="button"
                            wire:click="addToCart"
                            id="btn-masuk-keranjang"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-75 cursor-wait"
                            @class([
                                'w-full py-3 px-3 rounded-xl font-bold text-xs shadow-sm transition-all duration-150',
                                'flex items-center justify-center gap-1.5',
                                'btn-tendaku-secondary hover:border-avocado-500 hover:text-avocado-700 cursor-pointer' => $canSubmit,
                                'bg-wheat-200 text-darkbrown-400 cursor-not-allowed opacity-60' => !$canSubmit,
                            ])
                            @disabled(!$canSubmit)
                        >
                            <span wire:loading.remove wire:target="addToCart" class="flex items-center gap-1.5">
                                <i data-lucide="shopping-cart" class="w-4 h-4 text-avocado-700"></i>
                                <span>Masukkan ke Keranjang</span>
                            </span>
                            <span wire:loading wire:target="addToCart" class="flex items-center gap-1.5">
                                <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span>Menyimpan...</span>
                            </span>
                        </button>

                        <button
                            type="button"
                            wire:click="checkoutNow"
                            id="btn-sewa-sekarang"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-75 cursor-wait"
                            @class([
                                'w-full py-3 px-3 rounded-xl font-extrabold text-xs shadow-md transition-all duration-150',
                                'flex items-center justify-center gap-1.5',
                                'btn-tendaku-primary hover:shadow-lg cursor-pointer' => $canSubmit,
                                'bg-wheat-200 text-darkbrown-400 cursor-not-allowed opacity-60' => !$canSubmit,
                            ])
                            @disabled(!$canSubmit)
                        >
                            <span wire:loading.remove wire:target="checkoutNow" class="flex items-center gap-1.5">
                                <i data-lucide="arrow-right-circle" class="w-4 h-4"></i>
                                <span>Sewa Sekarang</span>
                            </span>
                            <span wire:loading wire:target="checkoutNow" class="flex items-center gap-1.5">
                                <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span>Menyiapkan...</span>
                            </span>
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- Tombol Lanjut ke Checkout (khusus anggota, muncul jika produk ini sudah ada di keranjang) --}}
        @if($inCart && ! $isGuest)
            <div class="mt-3 p-3.5 rounded-xl bg-avocado-50 border border-avocado-200 flex flex-col gap-2">
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

        {{-- TRUST BADGES --}}
        <div class="pt-4 border-t border-wheat-200 space-y-2.5">
            <div class="flex items-start gap-2.5 text-[11px] text-darkbrown-600">
                <i data-lucide="credit-card" class="w-4 h-4 text-avocado-600 shrink-0 mt-0.5"></i>
                <span>Cukup bawa <strong class="text-darkbrown-800">KTP Asli</strong> saat pickup barang di toko vendor.</span>
            </div>
            <div class="flex items-start gap-2.5 text-[11px] text-darkbrown-600">
                <i data-lucide="shield-check" class="w-4 h-4 text-avocado-600 shrink-0 mt-0.5"></i>
                <span>Transaksi aman tanpa biaya tersembunyi. Pembayaran lunas di awal.</span>
            </div>
            <div class="flex items-start gap-2.5 text-[11px] text-darkbrown-600">
                <i data-lucide="sparkles" class="w-4 h-4 text-avocado-600 shrink-0 mt-0.5"></i>
                <span>Unit steril, higienis & selalu diperiksa sebelum serah terima.</span>
            </div>
        </div>

    </div>
</div>
