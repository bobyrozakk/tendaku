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

    @if($vaInfo)
        <div class="rounded-xl border-2 border-goldenrod-400 bg-white p-3 space-y-2">
            <span>Nomor virtual account</span>

            <div x-show="vaBiller" x-cloak>
                <div class="text-xs text-darkbrown-500">Kode perusahaan (biller code)</div>
                <span class="mt-1 inline-block font-mono text-sm font-bold text-darkbrown-900 bg-wheat-100 px-2.5 py-1 rounded-lg border border-wheat-300 select-all" x-text="vaBiller">{{ $vaInfo['biller_code'] ?? '' }}</span>
            </div>

            <div class="va-pill rounded-full border border-goldenrod-400 bg-white" :aria-busy="vaLoading.toString()">
                <div class="va-pill-bank bg-wheat-100 border-goldenrod-400" @click.outside="vaOpen = false" @keydown.escape="vaOpen = false">
                    <button type="button" x-ref="vaBtn" class="va-bank-btn text-darkbrown-900"
                            @click="toggleBank()" :disabled="vaLoading || expired"
                            aria-haspopup="listbox" :aria-expanded="vaOpen.toString()" aria-label="Pilih bank virtual account">
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

                <div class="va-pill-num">
                    <span x-show="vaLoading" x-cloak class="va-loading text-xs font-semibold text-darkbrown-600">Membuat nomor VA...</span>
                    <span x-show="!vaLoading" class="va-num font-mono font-black text-darkbrown-900 select-all" x-text="vaNumber || '-'">{{ $vaInfo['va_number'] ?? '-' }}</span>
                    <button type="button" @click="copy(vaNumber)" :disabled="vaLoading || !vaNumber"
                            class="shrink-0 px-2.5 py-1 rounded-full text-xs font-bold bg-white hover:bg-wheat-100 border border-wheat-300 transition disabled:opacity-60">
                        <span x-text="copied ? 'Tersalin' : 'Salin'">Salin</span>
                    </button>
                </div>
            </div>

            <p class="text-xs text-darkbrown-500">Pilih bank pembayaran anda.</p>
        </div>
    @endif

    <div x-show="flash" x-cloak role="status"
         :class="flashTone === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-wheat-50 border-wheat-200 text-darkbrown-700'"
         class="rounded-xl border px-3.5 py-2.5 text-sm" x-text="flash"></div>

    <div class="flex flex-wrap items-center gap-2.5" x-show="!expired">

        @if($vaInfo && !config('services.midtrans.is_production'))
            {{-- Sandbox: VA otomatis tersalin, lalu simulator Midtrans terbuka di tab baru --}}
            <a href="https://simulator.sandbox.midtrans.com/" target="_blank" rel="noopener"
               @click="copy(vaNumber)"
               class="py-2.5 px-5 rounded-xl text-sm font-extrabold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 active:bg-goldenrod-400 border border-goldenrod-400 shadow-sm transition flex items-center gap-2">
                <span>Bayar sekarang</span>
            </a>
        @else
            {{-- Production / tanpa VA: pakai alur payNow() yang lama --}}
            <button type="button" @click="payNow()" :disabled="paying"
                    class="py-2.5 px-5 rounded-xl text-sm font-extrabold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 active:bg-goldenrod-400 border border-goldenrod-400 shadow-sm transition flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                <svg x-show="!paying" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
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
