<div class="card-tendaku rounded-2xl bg-white border border-wheat-200 shadow-tendaku-lg sticky top-6 overflow-hidden">

    {{-- ═══════════════════════════════════════════════════════════
         HEADER: Harga & Tagline "Tanpa Deposit"
    ══════════════════════════════════════════════════════════════ --}}
    <div class="bg-gradient-to-br from-darkbrown-900 to-darkbrown-800 px-6 pt-6 pb-5">
        <span class="inline-block text-[10px] font-bold uppercase tracking-widest text-wheat-400 mb-1">
            Tarif Sewa Peralatan
        </span>
        <div class="flex items-baseline gap-2">
            <span class="text-4xl font-extrabold text-white font-heading">
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

        {{-- ═══════════════════════════════════════════════════════
             VENDOR BADGE
        ════════════════════════════════════════════════════════ --}}
        <div class="flex items-center justify-between gap-3 p-3.5 rounded-xl bg-wheat-50 border border-wheat-200">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-9 h-9 rounded-lg bg-darkbrown-800 text-sunglow-300 flex items-center justify-center text-base flex-shrink-0">
                    🏬
                </div>
                <div class="min-w-0">
                    <h4 class="text-xs font-bold text-darkbrown-900 truncate">
                        {{ $this->product->vendor?->store_name
                            ?? $this->product->vendor?->name
                            ?? 'Tendaku Store Central' }}
                    </h4>
                    <p class="text-[10px] text-darkbrown-500 font-medium">Penyedia Terverifikasi Tendaku</p>
                </div>
            </div>
            <span class="badge-avocado text-[10px] flex-shrink-0">✓ Aktif</span>
        </div>

        {{-- ═══════════════════════════════════════════════════════
             FORM: Pilihan Tanggal Sewa dengan wire:model.live
        ════════════════════════════════════════════════════════ --}}
        <form wire:submit="proceedToBooking" class="space-y-4">

            <h3 class="text-xs font-extrabold uppercase tracking-wider text-darkbrown-700 flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-avocado-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>Pilih Tanggal Sewa</span>
            </h3>

            {{-- Tanggal Mulai (Pickup) --}}
            <div>
                <label for="pickup_date" class="block text-xs font-semibold text-darkbrown-700 mb-1.5">
                    📅 Tanggal Mulai (Pickup)
                </label>
                <input
                    type="date"
                    id="pickup_date"
                    wire:model.live="pickup_date"
                    min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                    class="input-tendaku w-full text-sm py-2.5 px-3 rounded-xl transition-all
                        @error('pickup_date') border-red-400 ring-2 ring-red-200 focus:ring-red-400 @enderror"
                    required
                />
                @error('pickup_date')
                    <p class="mt-1.5 flex items-center gap-1 text-[11px] font-semibold text-red-600">
                        <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Tanggal Selesai (Return) --}}
            <div>
                <label for="return_date" class="block text-xs font-semibold text-darkbrown-700 mb-1.5">
                    🏁 Tanggal Selesai (Return)
                </label>
                <input
                    type="date"
                    id="return_date"
                    wire:model.live="return_date"
                    min="{{ $pickup_date ?: \Carbon\Carbon::today()->format('Y-m-d') }}"
                    class="input-tendaku w-full text-sm py-2.5 px-3 rounded-xl transition-all
                        @error('return_date') border-red-400 ring-2 ring-red-200 focus:ring-red-400 @enderror"
                    required
                />
                @error('return_date')
                    <p class="mt-1.5 flex items-center gap-1 text-[11px] font-semibold text-red-600">
                        <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- ═══════════════════════════════════════════════════
                 RINGKASAN BIAYA REAL-TIME
                 Muncul instan saat pickup_date & return_date terisi
            ════════════════════════════════════════════════════ --}}
            @if($this->totalDays > 0)
                <div
                    class="rounded-xl overflow-hidden border border-avocado-200 bg-avocado-50/60 divide-y divide-avocado-100"
                    wire:loading.class="opacity-60"
                    wire:target="pickup_date,return_date"
                >
                    {{-- Label status tersedia --}}
                    <div class="px-4 py-2 flex items-center gap-1.5 bg-avocado-600">
                        <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-[11px] font-bold text-white tracking-wide">Unit Tersedia pada Tanggal Ini</span>
                    </div>

                    <div class="px-4 py-3 space-y-2">
                        {{-- Baris: Tanggal --}}
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-darkbrown-600 font-medium">Periode Sewa</span>
                            <span class="font-bold text-darkbrown-900 text-right">
                                {{ \Carbon\Carbon::parse($pickup_date)->translatedFormat('d M Y') }}
                                →
                                {{ \Carbon\Carbon::parse($return_date)->translatedFormat('d M Y') }}
                            </span>
                        </div>

                        {{-- Baris: Durasi --}}
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-darkbrown-600 font-medium">Durasi Sewa</span>
                            <span class="font-extrabold text-darkbrown-900 flex items-center gap-1">
                                <span class="px-2 py-0.5 rounded-full bg-avocado-100 text-avocado-800 border border-avocado-200 text-[11px]">
                                    {{ $this->totalDays }} Hari
                                </span>
                            </span>
                        </div>

                        {{-- Baris: Tarif Harian --}}
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-darkbrown-600 font-medium">Tarif / Hari</span>
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
                                ({{ $this->totalDays }} hari × Rp {{ number_format($this->product->daily_rate, 0, ',', '.') }})
                            </p>
                        </div>
                    </div>
                </div>
            @else
                {{-- Placeholder saat tanggal belum dipilih --}}
                <div class="p-4 rounded-xl border border-dashed border-wheat-300 bg-wheat-50 text-center">
                    <span class="text-2xl block mb-1">📅</span>
                    <p class="text-xs text-darkbrown-500 font-medium leading-relaxed">
                        Pilih tanggal mulai & selesai untuk melihat<br>estimasi durasi dan biaya sewa.
                    </p>
                </div>
            @endif

            {{-- Loading skeleton saat Livewire sedang proses --}}
            <div wire:loading wire:target="pickup_date,return_date" class="flex items-center gap-2 text-xs text-darkbrown-500 font-medium justify-center py-1">
                <svg class="animate-spin h-3.5 w-3.5 text-avocado-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Menghitung biaya sewa...
            </div>

            {{-- ═══════════════════════════════════════════════════
                 TOMBOL UTAMA: Sewa Sekarang
            ════════════════════════════════════════════════════ --}}
            <button
                type="submit"
                id="btn-sewa-sekarang"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-75 cursor-wait"
                @class([
                    'w-full py-3.5 px-6 rounded-xl font-extrabold text-sm shadow-md transition-all duration-200',
                    'flex items-center justify-center gap-2',
                    'btn-tendaku-primary hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0' => $this->totalDays > 0,
                    'bg-wheat-200 text-darkbrown-400 cursor-not-allowed opacity-60' => $this->totalDays <= 0,
                ])
                @disabled($this->totalDays <= 0)
            >
                {{-- Default state --}}
                <span wire:loading.remove wire:target="proceedToBooking" class="flex items-center gap-2">
                    @if($this->totalDays > 0)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-16H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Sewa Sekarang
                    @else
                        Pilih Tanggal Dahulu
                    @endif
                </span>

                {{-- Loading state --}}
                <span wire:loading wire:target="proceedToBooking" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Memproses Pemesanan...
                </span>
            </button>
        </form>

        {{-- ═══════════════════════════════════════════════════════
             TRUST BADGES
        ════════════════════════════════════════════════════════ --}}
        <div class="pt-4 border-t border-wheat-200 space-y-2">
            <div class="flex items-start gap-2 text-[11px] text-darkbrown-600">
                <span class="text-base leading-none">🪪</span>
                <span>Cukup bawa <strong class="text-darkbrown-800">KTP Asli</strong> saat pickup barang di lokasi vendor.</span>
            </div>
            <div class="flex items-start gap-2 text-[11px] text-darkbrown-600">
                <span class="text-base leading-none">🔒</span>
                <span>Data pemesanan disimpan aman. Tidak ada biaya tersembunyi.</span>
            </div>
            <div class="flex items-start gap-2 text-[11px] text-darkbrown-600">
                <span class="text-base leading-none">✨</span>
                <span>Peralatan dicuci & disinfeksi pasca setiap penyewaan.</span>
            </div>
        </div>

    </div>{{-- /p-6 --}}
</div>
