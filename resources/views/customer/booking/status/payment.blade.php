@if($status === 'pending')
    <div x-show="celebrate" x-cloak x-transition.opacity.duration.300ms
         class="absolute inset-0 z-10 bg-white/95 flex flex-col items-center justify-center gap-2" role="status">
        <svg class="w-16 h-16 text-avocado-600" viewBox="0 0 56 56" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
            <circle class="pay-ring" cx="28" cy="28" r="26.5" />
            <path class="pay-tick" d="M16 29l8 8 16-17" />
        </svg>
        <span class="font-extrabold text-darkbrown-900">Pembayaran diterima</span>
    </div>

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <div class="text-sm text-darkbrown-500">{{ $payTypeLabel }}</div>
            <div class="text-2xl font-extrabold text-darkbrown-900 font-heading tracking-tight">{{ $rupiah($payAmount) }}</div>
        </div>
        @if($onlinePayment?->order_id)
            <div class="text-right">
                <div class="text-xs text-darkbrown-500">ID pesanan</div>
                <div class="font-mono text-sm font-semibold text-darkbrown-800" x-text="orderId">{{ $onlinePayment->order_id }}</div>
            </div>
        @endif
    </div>

    <div class="flex items-center gap-2 rounded-xl bg-sunglow-100/70 border border-sunglow-300/70 px-3.5 py-2.5 text-sm text-darkbrown-800">
        <svg class="w-4 h-4 text-goldenrod-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span x-show="!expired">
            Bayar sebelum <strong>{{ $fmtDateTime($rental->expires_at) }}</strong>
            <span x-show="remaining" x-cloak> · sisa <span class="font-mono font-bold" x-text="remaining"></span></span>
        </span>
        <span x-show="expired" x-cloak class="font-semibold text-red-700">Batas waktu pembayaran sudah terlewat.</span>
    </div>

    @if($onlinePayment?->status === 'pending')
        <div class="rounded-xl border-2 border-goldenrod-400 bg-white p-3 space-y-2">
            <span>Metode pembayaran</span>

            <div class="va-pill rounded-full border border-goldenrod-400 bg-white" :aria-busy="vaLoading.toString()">
                <div class="va-pill-bank bg-wheat-100 border-goldenrod-400" @click.outside="vaOpen = false" @keydown.escape="vaOpen = false">
                    <button type="button" x-ref="vaBtn" class="va-bank-btn text-darkbrown-900"
                            @click="toggleBank()" :disabled="vaLoading || expired"
                            aria-haspopup="listbox" :aria-expanded="vaOpen.toString()" aria-label="Pilih metode pembayaran">
                        <span x-text="vaBanks[vaBank] || vaBank.toUpperCase()">{{ $vaBanks[$vaBankKey] }}</span>
                        <svg class="va-chev" :class="{ 'va-rot': vaOpen }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6" /></svg>
                    </button>

                    <div x-ref="vaMenu" x-show="vaOpen" x-cloak x-transition.opacity.duration.150ms role="listbox"
                         class="va-menu rounded-2xl bg-white border border-wheat-200 shadow-tendaku-sm" style="top:-9999px;left:0">
                        @foreach($vaBanks as $bankKey => $bankLabel)
                            <button type="button" role="option" :aria-selected="(vaBank === '{{ $bankKey }}').toString()"
                                    class="va-opt hover:bg-wheat-100"
                                    :class="vaBank === '{{ $bankKey }}' ? 'font-extrabold text-darkbrown-900 bg-wheat-100' : 'font-semibold text-darkbrown-700'"
                                    @click="pickBank('{{ $bankKey }}')">
                                <span>{{ $bankLabel }}</span>
                                <svg x-show="vaBank === '{{ $bankKey }}'" class="w-4 h-4 text-avocado-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7" /></svg>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div x-show="vaBank !== 'qris'" x-cloak class="min-w-0 flex-1">
                    <div x-show="vaBiller" x-cloak>
                        <div class="text-xs text-darkbrown-500">Kode perusahaan (biller code)</div>
                        <span class="mt-1 inline-block font-mono text-sm font-bold text-darkbrown-900 bg-wheat-100 px-2.5 py-1 rounded-lg border border-wheat-300 select-all" x-text="vaBiller">{{ $vaInfo['biller_code'] ?? '' }}</span>
                    </div>
                    <div class="va-pill-num flex min-w-0 items-center gap-2">
                        <span x-show="vaLoading" x-cloak class="va-loading text-xs font-semibold text-darkbrown-600">Membuat nomor VA...</span>
                        <span x-show="!vaLoading" class="va-num min-w-0 flex-1 truncate font-mono font-black text-darkbrown-900 select-all"
                              x-text="vaNumber || '-'">{{ $vaInfo['va_number'] ?? '-' }}</span>
                        <button type="button" @click="copy(vaNumber)" :disabled="vaLoading || !vaNumber"
                                class="shrink-0 px-2.5 py-1 rounded-full text-xs font-bold bg-white hover:bg-wheat-100 border border-wheat-300 transition disabled:opacity-60">
                            <span x-text="copied ? 'Tersalin' : 'Salin'">Salin</span>
                        </button>
                    </div>
                </div>
            </div>

            <p class="text-xs text-darkbrown-500">Pilih Virtual Account bank atau QRIS untuk membayar dengan scan kode.</p>
        </div>
    @endif

    @if($onlinePayment?->status === 'pending')
        <div x-show="qrisOpen" x-cloak x-transition.opacity
             @keydown.escape.window="qrisOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center bg-darkbrown-950/70 p-4"
             role="dialog" aria-modal="true" aria-labelledby="qris-title">
            <button type="button" class="absolute inset-0 cursor-default" @click="qrisOpen = false" aria-label="Tutup popup QRIS"></button>
            <section class="relative z-10 w-full max-w-sm rounded-2xl bg-white p-5 text-center shadow-2xl">
                <button type="button" @click="qrisOpen = false"
                        class="absolute right-3 top-3 rounded-full p-2 text-darkbrown-500 hover:bg-wheat-100"
                        aria-label="Tutup popup QRIS">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <path d="m18 6-12 12M6 6l12 12" />
                    </svg>
                </button>
                <h2 id="qris-title" class="pr-6 text-lg font-extrabold text-darkbrown-900">Bayar dengan QRIS</h2>
                <p class="mt-1 text-sm text-darkbrown-600">Scan kode ini dengan aplikasi pembayaran Anda.</p>

                <div class="relative mx-auto mt-4 aspect-square w-64 max-w-full rounded-xl border border-wheat-200 bg-white p-2 flex items-center justify-center">
                    <div x-show="qrisImageLoading && !qrisImageFailed" class="flex flex-col items-center justify-center gap-2 text-darkbrown-500 text-xs">
                        <svg class="w-6 h-6 animate-spin text-goldenrod-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z"></path></svg>
                        <span>Memuat gambar QRIS...</span>
                    </div>

                    <img x-show="qrisAvailable && !qrisImageFailed"
                         x-bind:src="qrisAvailable ? currentQrisImageUrl : ''"
                         alt="Kode QRIS pembayaran"
                         class="w-full h-full object-contain"
                         x-on:load="qrisImageLoading = false; qrisImageFailed = false"
                         x-on:error="qrisImageLoading = false; qrisImageFailed = true">

                    <div x-show="qrisImageFailed" x-cloak class="p-4 text-center">
                        <p class="text-sm font-semibold text-red-700">Gambar QRIS gagal dimuat.</p>
                        <button type="button" @click="retryQrisImage()"
                                class="mt-2 px-3 py-1 text-xs font-bold bg-wheat-100 hover:bg-wheat-200 rounded-lg text-darkbrown-800 transition">
                            Coba muat ulang
                        </button>
                    </div>
                </div>

                <p class="mt-3 text-xs text-darkbrown-500">QRIS berlaku sampai batas waktu pembayaran.</p>

                @if(!config('services.midtrans.is_production'))
                    <div class="mt-4 flex items-center gap-2">
                        <a href="https://simulator.sandbox.midtrans.com/v2/qris/index" target="_blank" rel="noopener"
                           class="flex-1 py-2.5 px-4 rounded-xl text-sm font-extrabold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 active:bg-goldenrod-400 border border-goldenrod-400 shadow-sm transition text-center">
                            <span>Bayar sekarang</span>
                        </a>
                        <button type="button" @click="copy(qrisRawUrl || currentQrisImageUrl)"
                                class="py-2.5 px-4 rounded-xl text-sm font-bold text-darkbrown-800 bg-white hover:bg-wheat-100 border border-wheat-300 shadow-sm transition shrink-0">
                            <span x-text="copied ? 'Tersalin' : 'Salin URL'">Salin URL</span>
                        </button>
                    </div>
                @endif
            </section>
        </div>
    @endif

    <div x-show="flash" x-cloak role="status"
         :class="flashTone === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-wheat-50 border-wheat-200 text-darkbrown-700'"
         class="rounded-xl border px-3.5 py-2.5 text-sm" x-text="flash"></div>

    <div class="flex flex-wrap items-center gap-2.5" x-show="!expired">

        @if($vaInfo && !config('services.midtrans.is_production'))
            {{-- Sandbox: VA otomatis tersalin, lalu simulator Midtrans terbuka di tab baru --}}
            <a x-show="vaBank !== 'qris'" x-cloak href="https://simulator.sandbox.midtrans.com/" target="_blank" rel="noopener"
               @click="copy(vaNumber)"
               class="py-2.5 px-5 rounded-xl text-sm font-extrabold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 active:bg-goldenrod-400 border border-goldenrod-400 shadow-sm transition flex items-center gap-2">
                <span>Bayar sekarang</span>
            </a>
            <button type="button" x-show="vaBank === 'qris' && qrisAvailable" x-cloak @click="openQris()"
                    class="py-2.5 px-5 rounded-xl text-sm font-extrabold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 active:bg-goldenrod-400 border border-goldenrod-400 shadow-sm transition">
                Tampilkan QRIS
            </button>
        @else
            {{-- Production / tanpa VA: pakai alur payNow() yang lama --}}
            <button type="button" @click="payNow()" :disabled="paying"
                    class="py-2.5 px-5 rounded-xl text-sm font-extrabold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 active:bg-goldenrod-400 border border-goldenrod-400 shadow-sm transition flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                <svg x-show="paying" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z"></path></svg>
                <span x-text="paying ? 'Memproses...' : 'Bayar sekarang'">Bayar sekarang</span>
            </button>
        @endif

        <button type="button" @click="sync()" :disabled="syncing"
                class="py-2.5 px-4 rounded-xl text-sm font-bold text-darkbrown-800 bg-white hover:bg-wheat-100 border border-wheat-300 shadow-sm transition flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
            <svg class="w-4 h-4" :class="syncing ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5.6 9A8 8 0 0119 11m-.6 4A8 8 0 015 13" /></svg>
            <span x-text="syncing ? 'Mengecek...' : 'Cek status'">Cek status</span>
        </button>
    </div>
@endif
