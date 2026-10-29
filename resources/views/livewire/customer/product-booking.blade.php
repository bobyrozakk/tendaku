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

    <div class="p-6 space-y-5">

        {{-- VENDOR BADGE & UMUR BARANG --}}
        <div class="p-3.5 rounded-xl bg-wheat-50 border border-wheat-200 space-y-2">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
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
                </div>
                <span class="badge-avocado text-[10px] flex-shrink-0 inline-flex items-center gap-1">
                    <i data-lucide="badge-check" class="w-3 h-3"></i>
                    <span>Vendor Aktif</span>
                </span>
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

        {{-- FORM BOOKING --}}
        <form wire:submit="addToCart" id="booking-form" class="space-y-4">

            <div class="flex items-center justify-between">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-darkbrown-700 flex items-center gap-2">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-avocado-600"></i>
                    <span>Formulir Sewa</span>
                </h3>

                {{-- Indikator Tanggal Penuh --}}
                @if(count($this->fullyBookedDates) > 0)
                    <span class="text-[10px] text-red-600 font-semibold inline-flex items-center gap-1" title="Ada tanggal di mana unit habis total">
                        <i data-lucide="alert-triangle" class="w-3 h-3 text-red-500"></i>
                        {{ count($this->fullyBookedDates) }} tanggal stok habis
                    </span>
                @endif
            </div>

            {{-- Tanggal & Jam Pengambilan (Pickup) --}}
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-darkbrown-700">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-darkbrown-500 inline-block mr-1 align-sub"></i>
                    Tanggal & Jam Pengambilan (Pickup)
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <input
                            type="date"
                            id="pickup_date"
                            wire:model.live="pickup_date"
                            min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                            class="input-tendaku w-full text-xs py-2 px-3 rounded-xl transition-all
                                @error('pickup_date') border-red-400 ring-2 ring-red-200 focus:ring-red-400 @enderror"
                            required
                        />
                        @error('pickup_date')
                            <p class="mt-1 flex items-center gap-1 text-[11px] font-semibold text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                    <div>
                        <input
                            type="time"
                            id="pickup_time"
                            wire:model.live="pickup_time"
                            class="input-tendaku w-full text-xs py-2 px-3 rounded-xl transition-all
                                @error('pickup_time') border-red-400 ring-2 ring-red-200 focus:ring-red-400 @enderror"
                            required
                        />
                        @error('pickup_time')
                            <p class="mt-1 flex items-center gap-1 text-[11px] font-semibold text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Durasi Sewa (24 Jam Penuh per Hari) --}}
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-semibold text-darkbrown-700">
                        <i data-lucide="hourglass" class="w-3.5 h-3.5 text-darkbrown-500 inline-block mr-1 align-sub"></i>
                        Durasi Sewa
                    </label>
                    <span class="text-[10px] font-bold text-avocado-700 bg-avocado-50 border border-avocado-200 px-2 py-0.5 rounded-full">
                        1 Hari = 24 Jam Penuh
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        wire:click="$set('duration_days', {{ max(1, $duration_days - 1) }})"
                        class="w-10 h-10 rounded-xl border border-wheat-300 bg-wheat-100 hover:bg-wheat-200 text-darkbrown-800 font-extrabold flex items-center justify-center transition"
                    >
                        -
                    </button>
                    <div class="flex-1 relative">
                        <input
                            type="number"
                            id="duration_days"
                            wire:model.live="duration_days"
                            min="1"
                            class="input-tendaku w-full text-center font-bold text-sm py-2 px-3 rounded-xl border-wheat-300"
                            required
                        />
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-darkbrown-500 pointer-events-none">
                            Hari
                        </span>
                    </div>
                    <button
                        type="button"
                        wire:click="$set('duration_days', {{ $duration_days + 1 }})"
                        class="w-10 h-10 rounded-xl border border-wheat-300 bg-wheat-100 hover:bg-wheat-200 text-darkbrown-800 font-extrabold flex items-center justify-center transition"
                    >
                        +
                    </button>
                </div>
                @error('duration_days')
                    <p class="mt-1 flex items-center gap-1 text-[11px] font-semibold text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Tanggal & Waktu Selesai (Return Deadline) --}}
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-semibold text-darkbrown-700">
                        <i data-lucide="flag" class="w-3.5 h-3.5 text-darkbrown-500 inline-block mr-1 align-sub"></i>
                        Batas Waktu Pengembalian (Return)
                    </label>
                    <span class="text-[10px] text-darkbrown-500 font-medium">
                        Maks. {{ $duration_days * 24 }} Jam
                    </span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <input
                            type="date"
                            id="return_date"
                            wire:model.live="return_date"
                            min="{{ $pickup_date ?: \Carbon\Carbon::today()->format('Y-m-d') }}"
                            class="input-tendaku w-full text-xs py-2 px-3 rounded-xl transition-all
                                @error('return_date') border-red-400 ring-2 ring-red-200 focus:ring-red-400 @enderror"
                            required
                        />
                        @error('return_date')
                            <p class="mt-1 flex items-center gap-1 text-[11px] font-semibold text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                    <div>
                        <input
                            type="time"
                            id="return_time"
                            wire:model.live="return_time"
                            class="input-tendaku w-full text-xs py-2 px-3 rounded-xl transition-all
                                @error('return_time') border-red-400 ring-2 ring-red-200 focus:ring-red-400 @enderror"
                            required
                        />
                        @error('return_time')
                            <p class="mt-1 flex items-center gap-1 text-[11px] font-semibold text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Jumlah Unit (Quantity) --}}
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="quantity" class="text-xs font-semibold text-darkbrown-700">
                        <i data-lucide="package" class="w-3.5 h-3.5 text-darkbrown-500 inline-block mr-1 align-sub"></i>
                        Jumlah Unit
                    </label>
                    <span class="text-[11px] font-bold text-darkbrown-600">
                        Tersedia:
                        <strong class="{{ $this->availableStock > 0 ? 'text-avocado-700' : 'text-red-600' }}">
                            {{ $this->availableStock }} Unit
                        </strong>
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        wire:click="$set('quantity', {{ max(1, $quantity - 1) }})"
                        class="w-10 h-10 rounded-xl border border-wheat-300 bg-wheat-100 hover:bg-wheat-200 text-darkbrown-800 font-extrabold flex items-center justify-center transition"
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
                        class="w-10 h-10 rounded-xl border border-wheat-300 bg-wheat-100 hover:bg-wheat-200 text-darkbrown-800 font-extrabold flex items-center justify-center transition"
                    >
                        +
                    </button>
                </div>
                @error('quantity')
                    <p class="mt-1.5 flex items-center gap-1 text-[11px] font-semibold text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- RINGKASAN BIAYA & STOK REAL-TIME --}}
            @if($this->totalDays > 0)
                <div
                    class="rounded-xl overflow-hidden border {{ $this->availableStock >= $this->quantity ? 'border-avocado-200 bg-avocado-50/60' : 'border-red-200 bg-red-50/60' }} divide-y divide-wheat-200"
                    wire:loading.class="opacity-60"
                    wire:target="pickup_date,return_date,quantity"
                >
                    {{-- Status Ketersediaan --}}
                    @if($this->availableStock >= $this->quantity)
                        <div class="px-4 py-2 flex items-center gap-1.5 bg-avocado-600">
                            <i data-lucide="check" class="w-3.5 h-3.5 text-white"></i>
                            <span class="text-[11px] font-bold text-white tracking-wide">
                                Stok Tersedia ({{ $this->availableStock }} unit siap sewa)
                            </span>
                        </div>
                    @else
                        <div class="px-4 py-2 flex items-center gap-1.5 bg-red-600">
                            <i data-lucide="x" class="w-3.5 h-3.5 text-white"></i>
                            <span class="text-[11px] font-bold text-white tracking-wide">
                                Stok Kurang! Hanya sisa {{ $this->availableStock }} unit
                            </span>
                        </div>
                    @endif

                    <div class="px-4 py-3 space-y-2">
                        {{-- Periode --}}
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-darkbrown-600 font-medium">Periode Sewa</span>
                            <span class="font-bold text-darkbrown-900 text-right">
                                {{ \Carbon\Carbon::parse($pickup_date)->translatedFormat('d M Y') }} {{ $pickup_time }}
                                →
                                {{ \Carbon\Carbon::parse($return_date)->translatedFormat('d M Y') }} {{ $return_time }}
                            </span>
                        </div>

                        {{-- Durasi & Unit --}}
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-darkbrown-600 font-medium">Rincian Sewa</span>
                            <span class="font-extrabold text-darkbrown-900">
                                {{ $this->quantity }} Unit × {{ $this->totalDays }} Hari ({{ $this->totalDays * 24 }} Jam)
                            </span>
                        </div>

                        {{-- Tarif Harian --}}
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-darkbrown-600 font-medium">Tarif / Hari (24 Jam)</span>
                            <span class="font-semibold text-darkbrown-900">
                                Rp {{ number_format($this->product->daily_rate, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    {{-- Total Harga Instan --}}
                    <div class="px-4 py-3 flex justify-between items-baseline bg-white/70">
                        <span class="text-xs font-bold text-darkbrown-900">Total Estimasi</span>
                        <div class="text-right">
                            <span class="text-2xl font-extrabold text-avocado-800 font-heading">
                                Rp {{ number_format($this->totalPrice, 0, ',', '.') }}
                            </span>
                            <p class="text-[10px] text-darkbrown-500 font-medium">
                                ({{ $this->quantity }} unit × {{ $this->totalDays }} hari × Rp {{ number_format($this->product->daily_rate, 0, ',', '.') }})
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Loading Feedback --}}
            <div wire:loading wire:target="pickup_date,pickup_time,duration_days,return_date,return_time,quantity" class="flex items-center gap-2 text-xs text-darkbrown-500 font-medium justify-center py-1">
                <svg class="animate-spin h-3.5 w-3.5 text-avocado-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Memeriksa ketersediaan stok & menghitung total...
            </div>

            {{-- TOMBOL UTAMA: Sewa Sekarang --}}
            @php
                $canSubmit = $this->totalDays > 0 && $this->availableStock >= $this->quantity;
            @endphp
            <button
                type="submit"
                id="btn-masuk-keranjang"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-75 cursor-wait"
                @class([
                    'w-full py-3.5 px-6 rounded-xl font-extrabold text-sm shadow-md transition-all duration-200',
                    'flex items-center justify-center gap-2',
                    'btn-tendaku-primary hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0' => $canSubmit,
                    'bg-wheat-200 text-darkbrown-400 cursor-not-allowed opacity-60' => !$canSubmit,
                ])
                @disabled(!$canSubmit)
            >
                <span wire:loading.remove wire:target="addToCart" class="flex items-center gap-2">
                    @if($this->totalDays <= 0)
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        <span>Pilih Tanggal Dahulu</span>
                    @elseif($this->availableStock < $this->quantity)
                        <i data-lucide="alert-circle" class="w-4 h-4"></i>
                        <span>Stok Tidak Mencukupi</span>
                    @else
                        <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                        <span>Masukkan ke Keranjang</span>
                    @endif
                </span>

                <span wire:loading wire:target="addToCart" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Menambahkan...
                </span>
            </button>
        </form>

        {{-- Tombol Lanjut ke Checkout (muncul jika produk ini sudah ada di keranjang) --}}
        @if($inCart)
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
