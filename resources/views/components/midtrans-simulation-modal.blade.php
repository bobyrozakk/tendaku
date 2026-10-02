@props([
    'booking',
])

<!--
====================================================================================
MODAL SIMULASI PEMBAYARAN MIDTRANS SNAP (SANDBOX DEMO)
Arsitektur ini didesain agar mudah di-switch ke `snap.pay(snapToken)` ketika
Server Key & Client Key Midtrans resmi sudah dikonfigurasi di project.
====================================================================================
-->
<div
    x-data="midtransSimulation()"
    x-on:open-midtrans-simulation.window="openModal($event.detail)"
    x-on:keydown.escape.window="if(isOpen && !isProcessing) closeModal()"
    class="relative z-50"
    x-cloak
>
    <!-- BACKDROP OVERLAY -->
    <div
        x-show="isOpen"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-darkbrown-900/75 backdrop-blur-sm transition-opacity"
        x-on:click="if(!isProcessing) closeModal()"
    ></div>

    <!-- DIALOG CONTAINER -->
    <div
        x-show="isOpen"
        class="fixed inset-0 z-50 overflow-y-auto p-4 sm:p-6 lg:p-8 flex items-center justify-center min-h-full"
    >
        <div
            x-show="isOpen"
            x-transition:enter="ease-out duration-250"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative w-full max-w-lg bg-white rounded-3xl border-2 border-wheat-300 shadow-2xl overflow-hidden flex flex-col max-h-[92vh]"
            x-on:click.stop
        >
            <!-- 1. POPUP BRAND & SANDBOX HEADER -->
            <div class="px-5 py-4 bg-wheat-100 border-b border-wheat-200 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <x-application-logo size="sm" :withText="false" />
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-extrabold text-sm text-darkbrown-900 tracking-tight">TENDAKU</span>
                            <span class="text-darkbrown-400 font-bold">•</span>
                            <span class="font-bold text-xs text-darkbrown-700">Midtrans Snap</span>
                        </div>
                        <span class="text-[10px] text-darkbrown-500 font-medium block">
                            Pembayaran Aman & Terverifikasi Otomatis
                        </span>
                    </div>
                </div>

                <!-- Tombol Tutup (X) -->
                <button
                    type="button"
                    x-on:click="closeModal()"
                    :disabled="isProcessing"
                    class="w-8 h-8 rounded-xl bg-white border border-wheat-300 text-darkbrown-600 hover:text-darkbrown-900 hover:bg-wheat-200 transition flex items-center justify-center focus:outline-none disabled:opacity-50"
                    title="Tutup Modal"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>


            <!-- 3. ORDER SUMMARY STRIP -->
            <div class="px-5 py-3.5 bg-[#FAF6ED] border-b border-wheat-200 flex items-center justify-between text-xs">
                <div>
                    <span class="text-darkbrown-400 block text-[10px] font-bold uppercase tracking-wider">Nomor Booking</span>
                    <span class="font-mono font-extrabold text-darkbrown-900 text-sm" x-text="'#' + bookingCode"></span>
                </div>
                <div class="text-right">
                    <span class="text-darkbrown-400 block text-[10px] font-bold uppercase tracking-wider">Total Tagihan</span>
                    <span class="font-extrabold text-darkbrown-900 text-base sm:text-lg" x-text="formatRupiah(amount)"></span>
                </div>
            </div>

            <!-- 4. MODAL SCROLLABLE BODY -->
            <div class="p-5 overflow-y-auto space-y-5 flex-1">

                <!-- Channel Selector Tabs -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-darkbrown-600 mb-2.5">
                        Pilih Metode Pembayaran
                    </label>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <!-- Tab 1: QRIS -->
                        <button
                            type="button"
                            x-on:click="selectedChannel = 'qris'"
                            :class="selectedChannel === 'qris' ? 'bg-sunglow-200 border-goldenrod-500 text-darkbrown-900 font-extrabold shadow-sm' : 'bg-white border-wheat-300 text-darkbrown-700 hover:bg-wheat-50'"
                            class="p-2.5 rounded-xl border text-xs text-center transition flex flex-col items-center justify-center gap-1"
                        >
                            <span class="text-base">📱</span>
                            <span class="leading-none text-[11px]">QRIS Realtime</span>
                        </button>

                        <!-- Tab 2: BCA VA -->
                        <button
                            type="button"
                            x-on:click="selectedChannel = 'bca_va'"
                            :class="selectedChannel === 'bca_va' ? 'bg-sunglow-200 border-goldenrod-500 text-darkbrown-900 font-extrabold shadow-sm' : 'bg-white border-wheat-300 text-darkbrown-700 hover:bg-wheat-50'"
                            class="p-2.5 rounded-xl border text-xs text-center transition flex flex-col items-center justify-center gap-1"
                        >
                            <span class="text-base">🏦</span>
                            <span class="leading-none text-[11px]">BCA VA</span>
                        </button>

                        <!-- Tab 3: Mandiri VA -->
                        <button
                            type="button"
                            x-on:click="selectedChannel = 'mandiri_va'"
                            :class="selectedChannel === 'mandiri_va' ? 'bg-sunglow-200 border-goldenrod-500 text-darkbrown-900 font-extrabold shadow-sm' : 'bg-white border-wheat-300 text-darkbrown-700 hover:bg-wheat-50'"
                            class="p-2.5 rounded-xl border text-xs text-center transition flex flex-col items-center justify-center gap-1"
                        >
                            <span class="text-base">🏛️</span>
                            <span class="leading-none text-[11px]">Mandiri VA</span>
                        </button>

                        <!-- Tab 4: BRI VA -->
                        <button
                            type="button"
                            x-on:click="selectedChannel = 'bri_va'"
                            :class="selectedChannel === 'bri_va' ? 'bg-sunglow-200 border-goldenrod-500 text-darkbrown-900 font-extrabold shadow-sm' : 'bg-white border-wheat-300 text-darkbrown-700 hover:bg-wheat-50'"
                            class="p-2.5 rounded-xl border text-xs text-center transition flex flex-col items-center justify-center gap-1"
                        >
                            <span class="text-base">💳</span>
                            <span class="leading-none text-[11px]">BRI BRIVA</span>
                        </button>
                    </div>
                </div>

                <!-- DETAIL METODE: 1. QRIS -->
                <div x-show="selectedChannel === 'qris'" class="space-y-4">
                    <div class="p-4 rounded-2xl bg-[#FAF6ED] border border-wheat-300 text-center space-y-3">
                        <div class="flex items-center justify-between text-xs px-2">
                            <span class="font-bold text-darkbrown-800 uppercase tracking-wider text-[11px]">QRIS Standar Indonesia</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-avocado-100 text-avocado-800 border border-avocado-300">
                                Realtime Auto-Check
                            </span>
                        </div>

                        <!-- Simulated QR Code SVG Frame -->
                        <div class="mx-auto w-52 h-52 bg-white p-3 rounded-2xl border-2 border-wheat-300 shadow-sm flex flex-col items-center justify-center relative">
                            <svg class="w-full h-full text-darkbrown-900" viewBox="0 0 120 120" fill="currentColor">
                                <!-- Top-left finder pattern -->
                                <rect x="10" y="10" width="30" height="30" rx="4" />
                                <rect x="16" y="16" width="18" height="18" fill="white" />
                                <rect x="20" y="20" width="10" height="10" />

                                <!-- Top-right finder pattern -->
                                <rect x="80" y="10" width="30" height="30" rx="4" />
                                <rect x="86" y="16" width="18" height="18" fill="white" />
                                <rect x="90" y="20" width="10" height="10" />

                                <!-- Bottom-left finder pattern -->
                                <rect x="10" y="80" width="30" height="30" rx="4" />
                                <rect x="16" y="86" width="18" height="18" fill="white" />
                                <rect x="20" y="90" width="10" height="10" />

                                <!-- QR Matrix Dots Dummy -->
                                <rect x="48" y="12" width="6" height="6" />
                                <rect x="62" y="12" width="6" height="6" />
                                <rect x="48" y="24" width="8" height="6" />
                                <rect x="64" y="24" width="6" height="8" />

                                <rect x="14" y="48" width="6" height="6" />
                                <rect x="28" y="48" width="6" height="6" />
                                <rect x="14" y="60" width="8" height="6" />
                                <rect x="28" y="60" width="6" height="8" />

                                <rect x="48" y="48" width="8" height="8" />
                                <rect x="64" y="48" width="8" height="8" />
                                <rect x="56" y="64" width="8" height="8" />

                                <rect x="84" y="48" width="6" height="8" />
                                <rect x="96" y="48" width="8" height="6" />
                                <rect x="84" y="62" width="8" height="6" />

                                <rect x="48" y="84" width="6" height="6" />
                                <rect x="62" y="84" width="6" height="8" />
                                <rect x="48" y="96" width="8" height="8" />

                                <rect x="80" y="80" width="10" height="6" />
                                <rect x="98" y="80" width="10" height="6" />
                                <rect x="84" y="94" width="8" height="8" />
                                <rect x="100" y="94" width="8" height="8" />

                                <!-- Center QRIS Logo Badge -->
                                <circle cx="60" cy="60" r="14" fill="white" stroke="#2B2202" stroke-width="2" />
                                <text x="60" y="63" text-anchor="middle" font-size="7" font-weight="900" fill="#2B2202" font-family="sans-serif">QRIS</text>
                            </svg>
                        </div>

                        <div class="space-y-1 text-xs">
                            <span class="font-extrabold text-darkbrown-900 block">TENDAKU • Mahameru Outdoor</span>
                            <span class="font-mono text-[11px] text-darkbrown-500">NMID: ID1020268921001</span>
                            <p class="text-[11px] text-darkbrown-600 pt-1">
                                Scan dengan GoPay, ShopeePay, OVO, Dana, LinkAja, atau Mobile Banking apa pun.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- DETAIL METODE: 2. BCA VIRTUAL ACCOUNT -->
                <div x-show="selectedChannel === 'bca_va'" class="space-y-4">
                    <div class="p-4 rounded-2xl bg-[#FAF6ED] border border-wheat-300 space-y-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-extrabold text-darkbrown-900">BCA Virtual Account</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-white text-darkbrown-700 border border-wheat-300">
                                Verifikasi Instan
                            </span>
                        </div>

                        <div class="p-3 bg-white rounded-xl border border-wheat-300 flex items-center justify-between gap-3">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400 block">Nomor Virtual Account</span>
                                <span class="font-mono font-black text-darkbrown-900 text-base sm:text-lg">80777 0812 3456 7891</span>
                            </div>
                            <button
                                type="button"
                                x-on:click="copyVa('80777081234567891')"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-darkbrown-800 bg-wheat-100 hover:bg-wheat-200 border border-wheat-300 transition"
                            >
                                <span x-text="copied ? 'Tersalin!' : 'Salin'"></span>
                            </button>
                        </div>

                        <div class="space-y-1.5 text-xs text-darkbrown-600 pt-1">
                            <p class="font-bold text-darkbrown-800">Petunjuk Pembayaran BCA:</p>
                            <ol class="list-decimal list-inside space-y-1 text-[11px] leading-relaxed">
                                <li>Buka m-BCA &gt; Pilih <strong>m-Transfer</strong> &gt; <strong>BCA Virtual Account</strong>.</li>
                                <li>Masukkan nomor <strong>80777 0812 3456 7891</strong>.</li>
                                <li>Pastikan nominal tagihan sesuai (<strong x-text="formatRupiah(amount)"></strong>) &gt; Masukkan PIN.</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- DETAIL METODE: 3. MANDIRI VIRTUAL ACCOUNT -->
                <div x-show="selectedChannel === 'mandiri_va'" class="space-y-4">
                    <div class="p-4 rounded-2xl bg-[#FAF6ED] border border-wheat-300 space-y-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-extrabold text-darkbrown-900">Mandiri Bill Payment</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-white text-darkbrown-700 border border-wheat-300">
                                Verifikasi Instan
                            </span>
                        </div>

                        <div class="p-3 bg-white rounded-xl border border-wheat-300 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-darkbrown-500">Kode Perusahaan:</span>
                                <span class="font-mono font-bold text-darkbrown-900">70012 (Tendaku)</span>
                            </div>
                            <div class="flex items-center justify-between pt-1 border-t border-wheat-100">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400 block">Nomor Tagihan:</span>
                                    <span class="font-mono font-black text-darkbrown-900 text-sm sm:text-base">88908 0812 3456 7891</span>
                                </div>
                                <button
                                    type="button"
                                    x-on:click="copyVa('88908081234567891')"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold text-darkbrown-800 bg-wheat-100 hover:bg-wheat-200 border border-wheat-300 transition"
                                >
                                    <span x-text="copied ? 'Tersalin!' : 'Salin'"></span>
                                </button>
                            </div>
                        </div>

                        <p class="text-[11px] text-darkbrown-600 leading-relaxed">
                            Buka aplikasi Livin' by Mandiri, pilih menu <strong>Bayar</strong> &gt; cari <strong>70012 Tendaku</strong> &gt; masukkan nomor tagihan.
                        </p>
                    </div>
                </div>

                <!-- DETAIL METODE: 4. BRI BRIVA -->
                <div x-show="selectedChannel === 'bri_va'" class="space-y-4">
                    <div class="p-4 rounded-2xl bg-[#FAF6ED] border border-wheat-300 space-y-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-extrabold text-darkbrown-900">BRI Virtual Account (BRIVA)</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-white text-darkbrown-700 border border-wheat-300">
                                Verifikasi Instan
                            </span>
                        </div>

                        <div class="p-3 bg-white rounded-xl border border-wheat-300 flex items-center justify-between gap-3">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-400 block">Nomor BRIVA</span>
                                <span class="font-mono font-black text-darkbrown-900 text-base sm:text-lg">12800 0812 3456 7891</span>
                            </div>
                            <button
                                type="button"
                                x-on:click="copyVa('12800081234567891')"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold text-darkbrown-800 bg-wheat-100 hover:bg-wheat-200 border border-wheat-300 transition"
                            >
                                <span x-text="copied ? 'Tersalin!' : 'Salin'"></span>
                            </button>
                        </div>

                        <p class="text-[11px] text-darkbrown-600 leading-relaxed">
                            Buka aplikasi BRImo &gt; pilih menu <strong>BRIVA</strong> &gt; masukkan nomor BRIVA di atas &gt; konfirmasi dan masukkan PIN transaksi Anda.
                        </p>
                    </div>
                </div>

            </div>

            <!-- 5. MODAL ACTION FOOTER -->
            <div class="p-5 bg-wheat-50 border-t border-wheat-200 space-y-2.5">
                <button
                    type="button"
                    x-on:click="simulatePaymentSuccess()"
                    :disabled="isProcessing"
                    class="w-full py-3.5 px-5 rounded-xl font-extrabold text-sm text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 active:bg-goldenrod-400 border border-goldenrod-400 shadow-tendaku transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60"
                >
                    <!-- Spinner saat simulasi verifikasi -->
                    <template x-if="isProcessing">
                        <div class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4 text-darkbrown-900" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Memproses Verifikasi Pembayaran...</span>
                        </div>
                    </template>
                    <template x-if="!isProcessing">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-darkbrown-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Bayar Sekarang (Simulasi Berhasil)</span>
                            <span class="font-mono text-xs px-2 py-0.5 rounded bg-darkbrown-800 text-sunglow-300 ml-1" x-text="formatRupiah(amount)"></span>
                        </div>
                    </template>
                </button>

                <div class="flex items-center justify-between text-[11px] text-darkbrown-500 pt-1 px-1">
                    <span>Transaksi Sandbox Demo • Tendaku</span>
                    <button
                        type="button"
                        x-on:click="closeModal()"
                        :disabled="isProcessing"
                        class="hover:text-darkbrown-900 underline disabled:opacity-50"
                    >
                        Batal &amp; Kembali ke Checkout
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function midtransSimulation() {
        return {
            isOpen: false,
            isProcessing: false,
            copied: false,
            selectedChannel: 'qris',
            bookingCode: '{{ $booking['booking_code'] }}',
            amount: {{ $booking['grand_total'] }},
            pickupMethod: '{{ $booking['pickup_method'] }}',
            formAction: "{{ route('checkout.store') }}",
            redirectUrl: "{{ route('booking.status', $booking['booking_code']) }}",

            openModal(detail) {
                if (detail) {
                    if (detail.bookingCode) this.bookingCode = detail.bookingCode;
                    if (detail.grossAmount) this.amount = detail.grossAmount;
                    if (detail.pickupMethod) this.pickupMethod = detail.pickupMethod;
                    if (detail.formAction) this.formAction = detail.formAction;
                    if (detail.redirectUrl) this.redirectUrl = detail.redirectUrl;
                }
                this.isProcessing = false;
                this.copied = false;
                this.isOpen = true;
                document.body.classList.add('overflow-hidden');
            },

            closeModal() {
                this.isOpen = false;
                document.body.classList.remove('overflow-hidden');
            },

            copyVa(text) {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text);
                    this.copied = true;
                    setTimeout(() => { this.copied = false; }, 2000);
                }
            },

            formatRupiah(num) {
                return 'Rp ' + (num || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            },

            simulatePaymentSuccess() {
                this.isProcessing = true;

                // Simulasi latensi jaringan pembayaran Midtrans (600ms)
                setTimeout(() => {
                    // Cari hidden checkout form di halaman atau buat form submit secara dinamis
                    let checkoutForm = document.getElementById('checkout-payment-form');
                    if (checkoutForm) {
                        // Update input channel yang dipilih
                        let channelInput = checkoutForm.querySelector('input[name="payment_channel"]');
                        if (channelInput) {
                            channelInput.value = this.selectedChannel;
                        }
                        checkoutForm.submit();
                    } else {
                        // Fallback redirect langsung ke status booking
                        window.location.href = this.redirectUrl;
                    }
                }, 600);
            }
        };
    }
</script>
