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

    @if($isQris)
        {{-- Tampilan Pembayaran QRIS (Pop-up Kode QR) --}}
        <div class="rounded-xl border-2 border-avocado-400 bg-white p-4 space-y-3">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-md bg-avocado-700 text-white font-extrabold text-[11px] tracking-wider uppercase">QRIS</span>
                    <span class="font-extrabold text-darkbrown-900 text-sm">Pembayaran QRIS</span>
                </div>
                <span class="text-xs font-semibold text-avocado-700 bg-avocado-50 border border-avocado-200 px-2 py-0.5 rounded-full">Otomatis Terverifikasi</span>
            </div>

            <p class="text-xs text-darkbrown-600 leading-relaxed">
                Pindai kode QR menggunakan GoPay, OVO, Dana, ShopeePay, BCA Mobile, atau aplikasi e-wallet &amp; mobile banking lainnya.
            </p>

            <div class="pt-1">
                <button type="button" @click="openQrisModal()" :disabled="paying || expired"
                        class="w-full sm:w-auto py-2.5 px-5 rounded-xl text-sm font-extrabold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 active:bg-goldenrod-400 border border-goldenrod-400 shadow-sm transition flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4 text-darkbrown-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span x-text="paying ? 'Memuat Kode QR...' : 'Tampilkan Kode QR (QRIS)'">Tampilkan Kode QR (QRIS)</span>
                </button>
            </div>

            <div class="pt-1 text-xs text-darkbrown-500 flex items-center justify-between">
                <span>Ingin bayar via transfer bank?</span>
                <button type="button" @click="changeBank('bca')" :disabled="vaLoading || expired"
                        class="font-bold text-avocado-700 hover:text-avocado-800 underline disabled:opacity-60">
                    Beralih ke Virtual Account
                </button>
            </div>
        </div>

        {{-- Modal Pop-up Kode QR QRIS --}}
        <div x-show="showQrisModal" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="modal-qris-title" role="dialog" aria-modal="true"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-darkbrown-900/60 backdrop-blur-sm transition-opacity"
                 @click="showQrisModal = false"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center">
                <div class="relative transform overflow-hidden rounded-3xl bg-white p-6 sm:p-7 text-left shadow-2xl transition-all w-full max-w-sm sm:max-w-md border border-wheat-200 space-y-4"
                     @click.outside="showQrisModal = false"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-wheat-200">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-md bg-avocado-700 text-white font-extrabold text-xs tracking-wider uppercase">QRIS</span>
                            <h3 class="font-extrabold text-darkbrown-900 text-lg font-heading" id="modal-qris-title">
                                Pindai QRIS untuk Membayar
                            </h3>
                        </div>
                        <button type="button" @click="showQrisModal = false"
                                class="rounded-xl p-1.5 text-darkbrown-400 hover:text-darkbrown-700 hover:bg-wheat-100 transition"
                                aria-label="Tutup">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- QR Code Display Box -->
                    <div class="flex flex-col items-center justify-center p-4 rounded-2xl bg-wheat-50 border border-wheat-200">
                        <template x-if="qrCodeUrl">
                            <div class="relative bg-white p-3 rounded-2xl border-2 border-goldenrod-400 shadow-sm">
                                <img :src="qrCodeUrl" alt="Kode QRIS" class="w-56 h-56 sm:w-60 sm:h-60 object-contain rounded-xl select-all" />
                            </div>
                        </template>
                        <template x-if="!qrCodeUrl">
                            <div class="w-56 h-56 sm:w-60 sm:h-60 flex flex-col items-center justify-center gap-3 bg-white rounded-2xl border border-wheat-300">
                                <svg class="w-8 h-8 animate-spin text-avocado-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z"></path>
                                </svg>
                                <span class="text-xs font-semibold text-darkbrown-600">Memuat kode QR...</span>
                            </div>
                        </template>

                        <div class="mt-3 text-center">
                            <div class="text-xs text-darkbrown-500 font-medium">Total tagihan</div>
                            <div class="text-2xl font-extrabold text-darkbrown-900 tracking-tight font-heading">{{ $rupiah($payAmount) }}</div>
                            <div x-show="remaining" x-cloak class="text-xs text-goldenrod-700 font-semibold mt-0.5">
                                Sisa waktu: <span class="font-mono font-bold" x-text="remaining"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Panduan Scan -->
                    <div class="text-xs text-darkbrown-600 space-y-1 bg-wheat-100/70 p-3 rounded-xl border border-wheat-200">
                        <p class="font-bold text-darkbrown-800">Petunjuk Pembayaran:</p>
                        <ol class="list-decimal list-inside space-y-0.5 leading-relaxed text-darkbrown-700">
                            <li>Buka aplikasi mobile banking atau e-wallet Anda.</li>
                            <li>Pilih menu <strong>Scan / Bayar QRIS</strong>.</li>
                            <li>Arahkan kamera smartphone ke kode QR di atas.</li>
                            <li>Periksa nominal lalu konfirmasi pembayaran.</li>
                        </ol>
                    </div>

                    <!-- Info Sandbox Midtrans -->
                    @if(!config('services.midtrans.is_production'))
                        <div class="rounded-xl border border-goldenrod-300 bg-sunglow-50 p-3 text-xs space-y-2">
                            <div class="font-bold text-darkbrown-800 flex items-center justify-between">
                                <span>Mode Sandbox (Pengujian)</span>
                                <span class="text-[10px] bg-sunglow-200 text-darkbrown-900 font-extrabold px-1.5 py-0.5 rounded">Testing</span>
                            </div>
                            <p class="text-darkbrown-600">Gunakan Simulator Midtrans untuk mensimulasikan pembayaran QRIS:</p>
                            <div class="flex flex-wrap gap-2 pt-0.5">
                                <a href="https://simulator.sandbox.midtrans.com/qris/index" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 border border-goldenrod-400 transition">
                                    <span>Buka Simulator QRIS</span>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                                <template x-if="qrString">
                                    <button type="button" @click="copy(qrString)"
                                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-white hover:bg-wheat-100 border border-wheat-300 transition text-darkbrown-800">
                                        <span x-text="copied ? 'QR String Tersalin' : 'Salin QR String'">Salin QR String</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    @endif

                    <!-- Action Buttons in Modal -->
                    <div class="pt-2 flex items-center justify-between gap-3 border-t border-wheat-200">
                        <button type="button" @click="showQrisModal = false"
                                class="py-2.5 px-4 rounded-xl text-xs font-bold text-darkbrown-700 bg-wheat-100 hover:bg-wheat-200 border border-wheat-300 transition">
                            Tutup
                        </button>
                        <button type="button" @click="sync()" :disabled="syncing"
                                class="py-2.5 px-4 rounded-xl text-xs font-extrabold text-white bg-avocado-700 hover:bg-avocado-800 transition flex items-center gap-1.5 disabled:opacity-60">
                            <svg class="w-3.5 h-3.5" :class="syncing ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5.6 9A8 8 0 0119 11m-.6 4A8 8 0 015 13" /></svg>
                            <span x-text="syncing ? 'Mengecek...' : 'Saya Sudah Bayar'">Saya Sudah Bayar</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Tampilan Pembayaran Virtual Account (INLINE - Tanpa Pop-up) --}}
        <div class="rounded-xl border-2 border-goldenrod-400 bg-white p-3 space-y-2">
            <div class="flex items-center justify-between">
                <span class="font-extrabold text-darkbrown-900 text-sm">Nomor virtual account</span>
                <button type="button" @click="changeBank('qris')" :disabled="vaLoading || expired"
                        class="text-xs font-bold text-avocado-700 hover:text-avocado-800 underline disabled:opacity-60">
                    Beralih ke QRIS
                </button>
            </div>

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

                <div class="va-pill-num flex min-w-0 items-center gap-2">
                    <span
                        x-show="vaLoading"
                        x-cloak
                        class="va-loading text-xs font-semibold text-darkbrown-600">Membuat nomor VA...
                    </span>
                    <span
                        x-show="!vaLoading"
                        class="va-num min-w-0 flex-1 truncate font-mono font-black text-darkbrown-900 select-all"
                        x-text="vaNumber || '-'">{{ $vaInfo['va_number'] ?? '-' }}
                    </span>
                    <button
                        type="button"
                        @click="copy(vaNumber)"
                        :disabled="vaLoading || !vaNumber"
                        class="shrink-0 px-2.5 py-1 rounded-full text-xs font-bold bg-white hover:bg-wheat-100 border border-wheat-300 transition disabled:opacity-60">
                        <span x-text="copied ? 'Tersalin' : 'Salin'">Salin</span>
                    </button>
                </div>
            </div>

            <p class="text-xs text-darkbrown-500">Pilih bank pembayaran Anda.</p>
        </div>
    @endif

    <div x-show="flash" x-cloak role="status"
         :class="flashTone === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-wheat-50 border-wheat-200 text-darkbrown-700'"
         class="rounded-xl border px-3.5 py-2.5 text-sm" x-text="flash"></div>

    <div class="flex flex-wrap items-center gap-2.5" x-show="!expired">
        @if($isQris)
            <button type="button" @click="openQrisModal()" :disabled="paying"
                    class="py-2.5 px-5 rounded-xl text-sm font-extrabold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 active:bg-goldenrod-400 border border-goldenrod-400 shadow-sm transition flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                <svg x-show="!paying" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <svg x-show="paying" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z"></path></svg>
                <span x-text="paying ? 'Memuat Kode QR...' : 'Tampilkan Kode QR (QRIS)'">Tampilkan Kode QR (QRIS)</span>
            </button>
        @else
            @if(!config('services.midtrans.is_production'))
                {{-- Sandbox: VA otomatis tersalin, lalu simulator Midtrans terbuka di tab baru --}}
                <a href="https://simulator.sandbox.midtrans.com/" target="_blank" rel="noopener"
                   @click="copy(vaNumber)"
                   class="py-2.5 px-5 rounded-xl text-sm font-extrabold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 active:bg-goldenrod-400 border border-goldenrod-400 shadow-sm transition flex items-center gap-2">
                    <span>Bayar sekarang</span>
                </a>
            @else
                <button type="button" @click="payNow()" :disabled="paying"
                        class="py-2.5 px-5 rounded-xl text-sm font-extrabold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 active:bg-goldenrod-400 border border-goldenrod-400 shadow-sm transition flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                    <span x-text="paying ? 'Memproses...' : 'Perbarui Nomor VA'">Perbarui Nomor VA</span>
                </button>
            @endif
        @endif

        <button type="button" @click="sync()" :disabled="syncing"
                class="py-2.5 px-4 rounded-xl text-sm font-bold text-darkbrown-800 bg-white hover:bg-wheat-100 border border-wheat-300 shadow-sm transition flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
            <svg class="w-4 h-4" :class="syncing ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5.6 9A8 8 0 0119 11m-.6 4A8 8 0 015 13" /></svg>
            <span x-text="syncing ? 'Mengecek...' : 'Cek status'">Cek status</span>
        </button>
    </div>

        <button type="button" @click="sync()" :disabled="syncing"
                class="py-2.5 px-4 rounded-xl text-sm font-bold text-darkbrown-800 bg-white hover:bg-wheat-100 border border-wheat-300 shadow-sm transition flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
            <svg class="w-4 h-4" :class="syncing ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5.6 9A8 8 0 0119 11m-.6 4A8 8 0 015 13" /></svg>
            <span x-text="syncing ? 'Mengecek...' : 'Cek status'">Cek status</span>
        </button>
    </div>
@endif
