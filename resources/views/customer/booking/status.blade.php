<x-layouts.customer>
    <x-slot name="title">
        Status Booking #{{ $rental->booking_code }} - Tendaku
    </x-slot>

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <nav class="flex items-center gap-2 text-xs text-darkbrown-500 mb-1.5">
                    <a href="{{ route('home') }}" class="hover:text-avocado-600 transition">Beranda</a>
                    <span class="text-wheat-400">/</span>
                    <a href="{{ route('customer.catalog.index') }}" class="hover:text-avocado-600 transition">Katalog</a>
                    <span class="text-wheat-400">/</span>
                    @auth
                        <a href="{{ route('customer.booking.index') }}" class="hover:text-avocado-600 transition">Riwayat</a>
                        <span class="text-wheat-400">/</span>
                    @endauth
                    <span class="text-darkbrown-800 font-semibold">Status #{{ $rental->booking_code }}</span>
                </nav>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-darkbrown-800 tracking-tight font-heading">
                        Status Booking & Rental
                    </h1>
                </div>
            </div>

            <!-- Booking Code Monospace Tag -->
            <div class="flex items-center gap-2.5 px-4 py-2.5 bg-wheat-100 rounded-2xl border border-wheat-300 shadow-tendaku-sm self-start sm:self-auto">
                <div class="w-8 h-8 rounded-xl bg-darkbrown-800 text-wheat-200 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500 block leading-none">Kode Booking</span>
                    <span class="font-mono text-sm sm:text-base font-extrabold text-darkbrown-900">#{{ $rental->booking_code }}</span>
                </div>
            </div>
        </div>
    </x-slot>

    @php
        $statusLabels = [
            'pending' => 'Menunggu Pembayaran',
            'confirmed' => 'Dikonfirmasi (Siap Pickup)',
            'picked_up' => 'Sedang Dipakai (Disewa)',
            'returned' => 'Telah Dikembalikan',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'expired' => 'Kedaluwarsa',
        ];

        // Definisi alur timeline standar: pending -> confirmed -> picked_up -> returned -> completed
        $allSteps = [
            'pending' => [
                'title' => 'Booking Dibuat',
                'description' => 'Pesanan berhasil dibuat, menunggu pelunasan tagihan sewa.',
                'time' => $rental->created_at->translatedFormat('d M Y, H:i') . ' WIB',
            ],
            'confirmed' => [
                'title' => 'Pembayaran Lunas',
                'description' => 'Pembayaran terverifikasi, barang disiapkan oleh staf vendor.',
                'time' => $rental->payments->where('status', 'paid')->first()?->paid_at?->translatedFormat('d M Y, H:i') ?? 'Menunggu pelunasan',
            ],
            'picked_up' => [
                'title' => 'Serah Terima (Pickup)',
                'description' => 'Peralatan diserahkan dan fisik KTP ditahan sebagai jaminan.',
                'time' => $rental->picked_up_at?->translatedFormat('d M Y, H:i') ?? \Carbon\Carbon::parse($rental->pickup_date)->translatedFormat('d M Y'),
            ],
            'returned' => [
                'title' => 'Pengembalian (Return)',
                'description' => 'Unit diperiksa kondisinya dan fisik KTP dikembalikan.',
                'time' => $rental->returned_at?->translatedFormat('d M Y, H:i') ?? 'Maks: ' . \Carbon\Carbon::parse($rental->return_date)->translatedFormat('d M Y'),
            ],
            'completed' => [
                'title' => 'Transaksi Selesai',
                'description' => 'Seluruh kewajiban sewa dan inspeksi telah rampung.',
                'time' => $rental->status === 'completed' ? ($rental->updated_at->translatedFormat('d M Y, H:i') . ' WIB') : 'Menunggu selesai',
            ],
        ];

        $stepOrder = ['pending', 'confirmed', 'picked_up', 'returned', 'completed'];
        $currentIdx = array_search($rental->status, $stepOrder, true);
        if ($currentIdx === false) {
            $currentIdx = -1; // untuk cancelled/expired
        }

        $isExpiredOrCancelled = in_array($rental->status, ['cancelled', 'expired'], true);
        $hasDenda = ($rental->total_late_fee > 0 || $rental->total_damage_fee > 0);
        $totalFinal = $rental->final_total ?: ($rental->grand_total + $rental->total_late_fee + $rental->total_damage_fee);
    @endphp

    <div class="py-4 sm:py-6 space-y-6">

        <!-- ALERT KHUSUS JIKA STATUS PENDING & COUNTDOWN EXPIRES_AT -->
        @if($rental->status === 'pending' && $rental->expires_at)
            @php
                $isExpiredNow = $rental->expires_at->isPast();
                $onlinePayment = $latestPayment ?? $rental->payments->where('channel', 'midtrans')->first();
            @endphp

            <div class="space-y-4">
                <!-- 1. ALERT STATUS & TOMBOL AKSI UTAMA -->
                <div class="p-5 rounded-3xl border {{ $isExpiredNow ? 'bg-red-50 border-red-200' : 'bg-gradient-to-r from-wheat-100 to-[#FAF6ED] border-wheat-300' }} shadow-tendaku-sm">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-sunglow-200 text-darkbrown-900 border border-goldenrod-400">
                                    {{ $isExpiredNow ? 'Kadaluwarsa' : 'Menunggu Pembayaran' }}
                                </span>
                                @if($onlinePayment)
                                    <span class="font-mono text-xs text-darkbrown-600 font-semibold">
                                        #{{ $onlinePayment->order_id }}
                                    </span>
                                @endif
                            </div>
                            <h3 class="text-lg sm:text-xl font-extrabold text-darkbrown-900 tracking-tight">
                                Tagihan: Rp {{ number_format($onlinePayment?->amount ?? $totalFinal, 0, ',', '.') }}
                                <span class="text-xs font-bold text-darkbrown-500 font-normal">
                                    ({{ $onlinePayment?->payment_type === 'dp' ? 'Uang Muka / DP 50%' : 'Pelunasan Penuh' }})
                                </span>
                            </h3>
                            <p class="text-xs text-darkbrown-600 leading-relaxed">
                                Rekening Penampung: <strong class="text-darkbrown-900">Platform TENDAKU (Super Admin)</strong> • Batas bayar: <strong>{{ $rental->expires_at->translatedFormat('d M Y, H:i') }} WIB</strong> ({{ $rental->expires_at->diffForHumans() }}).
                            </p>
                        </div>

                        @if(!$isExpiredNow)
                            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                                <button 
                                    type="button"
                                    onclick="payPendingBooking()"
                                    id="btn-pay-pending"
                                    class="py-3 px-5 rounded-xl text-xs font-extrabold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 active:bg-goldenrod-400 border border-goldenrod-400 shadow-sm transition flex items-center gap-2 cursor-pointer"
                                >
                                    <svg class="w-4 h-4 text-darkbrown-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    <span>Buka Pembayaran (Midtrans Snap)</span>
                                </button>

                                <button 
                                    type="button"
                                    onclick="syncPaymentStatus()"
                                    id="btn-sync-status"
                                    class="py-3 px-4 rounded-xl text-xs font-bold text-darkbrown-800 bg-white hover:bg-wheat-100 border border-wheat-300 shadow-sm transition flex items-center gap-1.5 cursor-pointer"
                                    title="Cek apakah pembayaran sudah masuk di Midtrans"
                                >
                                    <span id="sync-icon">🔄</span>
                                    <span>Cek Status</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 2. PILIHAN CARA PEMBAYARAN: QRIS & REKENING SUPERADMIN TENDAKU -->
                @if(!$isExpiredNow)
                    <div x-data="{ 
                        activeMethod: '{{ ($onlinePayment?->method && str_contains($onlinePayment->method, 'va')) ? 'va' : 'qris' }}',
                        copiedVa: '',
                        copyText(text, key) {
                            if (navigator.clipboard) {
                                navigator.clipboard.writeText(text);
                                this.copiedVa = key;
                                setTimeout(() => { this.copiedVa = ''; }, 2000);
                            }
                        }
                    }" class="p-5 sm:p-6 rounded-3xl bg-white border-2 border-wheat-300 shadow-tendaku-sm space-y-6">

                        <!-- Header & Tab Switcher -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-wheat-200">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500 block">Pilihan Pembayaran Resmi</span>
                                <h4 class="font-extrabold text-base sm:text-lg text-darkbrown-900">
                                    Bayar ke Rekening Platform Tendaku
                                </h4>
                            </div>

                            <!-- Method Switcher Pills -->
                            <div class="flex items-center gap-1.5 p-1 bg-wheat-100 rounded-2xl border border-wheat-300 self-start sm:self-auto">
                                <button
                                    type="button"
                                    @click="activeMethod = 'qris'"
                                    :class="activeMethod === 'qris' ? 'bg-sunglow-300 text-darkbrown-900 font-extrabold shadow-sm' : 'text-darkbrown-600 hover:text-darkbrown-900 font-semibold'"
                                    class="px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5"
                                >
                                    <span>📱</span>
                                    <span>QRIS Realtime</span>
                                </button>
                                <button
                                    type="button"
                                    @click="activeMethod = 'va'"
                                    :class="activeMethod === 'va' ? 'bg-sunglow-300 text-darkbrown-900 font-extrabold shadow-sm' : 'text-darkbrown-600 hover:text-darkbrown-900 font-semibold'"
                                    class="px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5"
                                >
                                    <span>🏦</span>
                                    <span>Rekening / VA Superadmin</span>
                                </button>
                            </div>
                        </div>

                        <!-- ── TAB 1: PEMBAYARAN QRIS ────────────────────────────────────────────── -->
                        <div x-show="activeMethod === 'qris'" x-cloak class="space-y-5">
                            <div class="flex flex-col md:flex-row items-center gap-6 p-5 sm:p-6 rounded-2xl bg-[#FAF6ED] border border-wheat-300">
                                <!-- Kartu QRIS Resmi Midtrans -->
                                <div class="w-64 sm:w-72 bg-white p-5 rounded-2xl border-2 border-darkbrown-800 shadow-md shrink-0 flex flex-col items-center text-center space-y-3.5">
                                    <div class="w-full flex items-center justify-between pb-2 border-b border-gray-200">
                                        <div class="flex items-center gap-1">
                                            <span class="font-black text-sm tracking-tighter text-red-600 font-sans">QRIS</span>
                                            <span class="text-[9px] font-bold text-gray-500 uppercase leading-none">Standar Pembayaran Nasional</span>
                                        </div>
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-darkbrown-800 text-white">GPN</span>
                                    </div>

                                    <div class="space-y-0.5">
                                        <h5 class="font-extrabold text-xs text-darkbrown-900 uppercase tracking-tight">
                                            TENDAKU PLATFORM
                                        </h5>
                                        <p class="text-[10px] text-darkbrown-500">
                                            Midtrans Payment Gateway Resmi
                                        </p>
                                    </div>

                                    <!-- Action Card to open dynamic QRIS -->
                                    <div class="p-4 bg-wheat-50 rounded-xl border border-wheat-300 w-full flex flex-col items-center justify-center space-y-2.5">
                                        <span class="text-3xl">📱</span>
                                        <p class="text-[11px] font-semibold text-darkbrown-700 leading-snug">
                                            Kode QRIS dinamis &amp; resmi akan ditampilkan langsung di popup Midtrans.
                                        </p>
                                        <button
                                            type="button"
                                            onclick="payPendingBooking()"
                                            class="w-full py-2.5 px-3 rounded-xl text-xs font-extrabold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 active:bg-goldenrod-400 border border-goldenrod-400 shadow-sm transition flex items-center justify-center gap-1.5 cursor-pointer"
                                        >
                                            <span>📷 Tampilkan Kode QRIS</span>
                                        </button>
                                    </div>

                                    <!-- Nominal Pas -->
                                    <div class="w-full pt-1.5 border-t border-gray-100">
                                        <span class="text-[10px] text-gray-400 block font-bold uppercase">Total Tagihan Pas</span>
                                        <span class="font-mono text-base font-black text-darkbrown-900">
                                            Rp {{ number_format($onlinePayment?->amount ?? $totalFinal, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Panduan Sisi Kanan QRIS -->
                                <div class="flex-1 space-y-3.5 text-xs">
                                    <div>
                                        <h4 class="font-extrabold text-sm sm:text-base text-darkbrown-900">
                                            Cara Pembayaran QRIS Realtime:
                                        </h4>
                                        <p class="text-darkbrown-600 mt-1 leading-relaxed">
                                            Dapat dibayar menggunakan aplikasi m-Banking atau e-Wallet apa pun di Indonesia (BCA Mobile, Livin' Mandiri, BRImo, BNI Mobile, GoPay, OVO, Dana, ShopeePay, LinkAja).
                                        </p>
                                    </div>

                                    <div class="p-3.5 rounded-xl bg-white border border-wheat-200 space-y-1.5">
                                        <span class="font-bold text-darkbrown-900 block">Langkah Pembayaran:</span>
                                        <ol class="list-decimal list-inside space-y-1 text-darkbrown-600 leading-relaxed">
                                            <li>Klik tombol <strong>Tampilkan Kode QRIS</strong> di samping.</li>
                                            <li>Scan kode QR yang muncul dengan aplikasi m-Banking atau E-Wallet Anda.</li>
                                            <li>Periksa nama merchant <strong>TENDAKU PLATFORM</strong> dan nominal tagihan.</li>
                                            <li>Selesaikan pembayaran. Status booking akan otomatis terkonfirmasi.</li>
                                        </ol>
                                    </div>

                                    <!-- Tombol Aksi Snap & Cek Status -->
                                    <div class="flex flex-wrap items-center gap-2.5 pt-2">
                                        <button
                                            type="button"
                                            onclick="payPendingBooking()"
                                            class="py-2.5 px-4 rounded-xl text-xs font-bold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 border border-goldenrod-400 shadow-sm transition flex items-center gap-1.5 cursor-pointer"
                                        >
                                            <span>💳 Buka Pembayaran (Midtrans Snap)</span>
                                        </button>

                                        <button
                                            type="button"
                                            onclick="syncPaymentStatus()"
                                            class="py-2.5 px-4 rounded-xl text-xs font-bold text-darkbrown-800 bg-white hover:bg-wheat-100 border border-wheat-300 shadow-sm transition flex items-center gap-1.5 cursor-pointer"
                                        >
                                            <span>🔄 Cek Status Pembayaran</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── TAB 2: VIRTUAL ACCOUNT (BANK TRANSFER RESMI) ───────────────────── -->
                        <div x-show="activeMethod === 'va'" x-cloak class="space-y-4">
                            <div class="p-4 rounded-xl bg-[#FAF6ED] border border-wheat-300 text-xs text-darkbrown-700 leading-relaxed">
                                Pembayaran transfer ditampung secara aman oleh <strong>Platform TENDAKU melalui Gateway Resmi Midtrans</strong>. Nomor Virtual Account unik dan resmi untuk pesanan Anda akan otomatis diterbitkan saat Anda membuka pembayaran.
                            </div>

                            <!-- List Bank Cards (Informasi Channel Tersedia) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                                <!-- 1. BCA Virtual Account -->
                                <div class="p-4 rounded-2xl bg-white border-2 border-wheat-200 hover:border-goldenrod-400 transition space-y-2.5 shadow-sm">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-800 flex items-center justify-center font-black text-xs border border-blue-200">
                                                BCA
                                            </span>
                                            <div>
                                                <h5 class="font-extrabold text-darkbrown-900 text-xs sm:text-sm">BCA Virtual Account</h5>
                                                <span class="text-[10px] text-darkbrown-500">Bank Central Asia</span>
                                            </div>
                                        </div>
                                        <span class="text-[10px] font-bold text-avocado-800 bg-avocado-100 px-2 py-0.5 rounded">Otomatis</span>
                                    </div>
                                    <p class="text-[11px] text-darkbrown-600">Transfer via BCA Mobile, myBCA, KlikBCA, atau ATM BCA 24 jam.</p>
                                </div>

                                <!-- 2. Mandiri Virtual Account -->
                                <div class="p-4 rounded-2xl bg-white border-2 border-wheat-200 hover:border-goldenrod-400 transition space-y-2.5 shadow-sm">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-800 flex items-center justify-center font-black text-xs border border-amber-200">
                                                MDR
                                            </span>
                                            <div>
                                                <h5 class="font-extrabold text-darkbrown-900 text-xs sm:text-sm">Mandiri Virtual Account</h5>
                                                <span class="text-[10px] text-darkbrown-500">Bank Mandiri (Bill Payment)</span>
                                            </div>
                                        </div>
                                        <span class="text-[10px] font-bold text-avocado-800 bg-avocado-100 px-2 py-0.5 rounded">Otomatis</span>
                                    </div>
                                    <p class="text-[11px] text-darkbrown-600">Transfer via Livin' by Mandiri, Kopra Mandiri, atau ATM Mandiri.</p>
                                </div>

                                <!-- 3. BRI Virtual Account (BRIVA) -->
                                <div class="p-4 rounded-2xl bg-white border-2 border-wheat-200 hover:border-goldenrod-400 transition space-y-2.5 shadow-sm">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-900 flex items-center justify-center font-black text-xs border border-blue-200">
                                                BRI
                                            </span>
                                            <div>
                                                <h5 class="font-extrabold text-darkbrown-900 text-xs sm:text-sm">BRI Virtual Account (BRIVA)</h5>
                                                <span class="text-[10px] text-darkbrown-500">Bank Rakyat Indonesia</span>
                                            </div>
                                        </div>
                                        <span class="text-[10px] font-bold text-avocado-800 bg-avocado-100 px-2 py-0.5 rounded">Otomatis</span>
                                    </div>
                                    <p class="text-[11px] text-darkbrown-600">Transfer via BRImo, Internet Banking BRI, atau ATM BRI.</p>
                                </div>

                                <!-- 4. BNI Virtual Account -->
                                <div class="p-4 rounded-2xl bg-white border-2 border-wheat-200 hover:border-goldenrod-400 transition space-y-2.5 shadow-sm">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="w-8 h-8 rounded-lg bg-teal-50 text-teal-800 flex items-center justify-center font-black text-xs border border-teal-200">
                                                BNI
                                            </span>
                                            <div>
                                                <h5 class="font-extrabold text-darkbrown-900 text-xs sm:text-sm">BNI Virtual Account</h5>
                                                <span class="text-[10px] text-darkbrown-500">Bank Negara Indonesia</span>
                                            </div>
                                        </div>
                                        <span class="text-[10px] font-bold text-avocado-800 bg-avocado-100 px-2 py-0.5 rounded">Otomatis</span>
                                    </div>
                                    <p class="text-[11px] text-darkbrown-600">Transfer via BNI Mobile Banking, BNI Direct, atau ATM BNI.</p>
                                </div>
                            </div>

                            <!-- Petunjuk & Tombol Aksi -->
                            <div class="p-3.5 rounded-xl bg-sunglow-50 border border-sunglow-200 text-xs text-darkbrown-800 flex items-start gap-2.5">
                                <span class="text-base shrink-0">ℹ️</span>
                                <p class="leading-relaxed">
                                    Klik tombol <strong>Dapatkan Nomor VA (Midtrans)</strong> di bawah. Anda akan diarahkan ke pop-up resmi untuk memilih bank Anda dan mendapatkan nomor Virtual Account secara instan.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-wheat-200 text-xs">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        onclick="payPendingBooking()"
                                        class="py-2.5 px-4 rounded-xl text-xs font-bold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 border border-goldenrod-400 shadow-sm transition flex items-center gap-1.5 cursor-pointer"
                                    >
                                        <span>🏦 Dapatkan Nomor VA (Midtrans)</span>
                                    </button>

                                    @if(!config('services.midtrans.is_production'))
                                    <a
                                        href="https://simulator.sandbox.midtrans.com/"
                                        target="_blank"
                                        class="py-2.5 px-4 rounded-xl text-xs font-bold text-darkbrown-800 bg-wheat-100 hover:bg-wheat-200 border border-wheat-300 shadow-sm transition flex items-center gap-1.5"
                                    >
                                        <span>🧪 Simulator Sandbox ↗</span>
                                    </a>
                                    @endif
                                </div>

                                <button
                                    type="button"
                                    onclick="syncPaymentStatus()"
                                    class="py-2.5 px-4 rounded-xl text-xs font-bold text-darkbrown-900 bg-goldenrod-200 hover:bg-goldenrod-300 border border-goldenrod-400 shadow-sm transition flex items-center gap-1.5 cursor-pointer"
                                >
                                    <span>🔄 Cek Status Pembayaran</span>
                                </button>
                            </div>
                        </div>

                    </div>
                @endif
            </div>
        @elseif($isExpiredOrCancelled)
            <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-xs text-red-800 flex items-center gap-2">
                <span class="text-lg">❌</span>
                <span>Transaksi ini telah <strong>{{ $statusLabels[$rental->status] }}</strong>. Unit perlengkapan camping telah dilepaskan kembali ke katalog.</span>
            </div>
        @endif

        <!-- 1. RENTAL TIMELINE TRACKER -->
        <div class="bg-white rounded-2xl border border-wheat-200 p-5 sm:p-6 shadow-tendaku-sm">
            <h3 class="text-xs font-bold uppercase tracking-wider text-darkbrown-500 mb-4">
                Alur Tahapan Rental & Verifikasi
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
                @foreach ($stepOrder as $idx => $stepKey)
                    @php
                        $step = $allSteps[$stepKey];
                        $isCompleted = ($currentIdx !== -1 && $idx < $currentIdx) || ($rental->status === 'completed');
                        $isActive = ($currentIdx !== -1 && $idx === $currentIdx && $rental->status !== 'completed');
                    @endphp
                    <div class="p-3.5 rounded-xl border flex flex-col justify-between space-y-2 {{ $isActive ? 'bg-sunglow-50 border-goldenrod-400 ring-2 ring-goldenrod-400/30' : ($isCompleted ? 'bg-avocado-50/60 border-avocado-200' : 'bg-[#FAF6ED] border-wheat-200') }}">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[11px] font-bold {{ $isActive ? 'bg-goldenrod-500 text-white' : ($isCompleted ? 'bg-avocado-500 text-white' : 'bg-wheat-200 text-darkbrown-600') }}">
                                    @if ($isCompleted)
                                        ✓
                                    @elseif ($isActive)
                                        ●
                                    @else
                                        {{ $idx + 1 }}
                                    @endif
                                </span>
                                <span class="text-[10px] font-bold uppercase tracking-wider {{ $isActive ? 'text-goldenrod-800' : ($isCompleted ? 'text-avocado-800' : 'text-darkbrown-400') }}">
                                    {{ $isActive ? 'Sedang Berjalan' : ($isCompleted ? 'Selesai' : 'Menunggu') }}
                                </span>
                            </div>
                            <h4 class="font-extrabold text-xs text-darkbrown-900 leading-snug">
                                {{ $step['title'] }}
                            </h4>
                            <p class="text-[11px] text-darkbrown-600 mt-0.5 leading-relaxed line-clamp-2">
                                {{ $step['description'] }}
                            </p>
                        </div>
                        <div class="pt-1.5 border-t {{ $isActive ? 'border-goldenrod-300' : ($isCompleted ? 'border-avocado-200' : 'border-wheat-200') }} text-[10px] font-semibold text-darkbrown-500">
                            {{ $step['time'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 2. CARD UTAMA RINCIAN TRANSAKSI -->
        <div class="bg-white rounded-3xl border border-wheat-300 p-6 sm:p-8 shadow-tendaku-sm space-y-6">

            <!-- HEADER CARD: VENDOR TOKO -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-5 border-b border-wheat-200">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-darkbrown-800 text-wheat-200 flex items-center justify-center font-black text-lg shrink-0">
                        🏬
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base sm:text-lg font-extrabold text-darkbrown-900 leading-tight">
                                {{ $rental->vendor?->name }}
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-avocado-100 text-avocado-800 border border-avocado-300">
                                Verified Vendor
                            </span>
                        </div>
                        <p class="text-xs text-darkbrown-500 mt-0.5">
                            {{ $rental->vendor?->address }} • {{ $rental->vendor?->city }}
                        </p>
                    </div>
                </div>

                @if($rental->vendor?->phone)
                    <a href="tel:{{ $rental->vendor->phone }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-darkbrown-800 bg-[#FAF6ED] border border-wheat-300 hover:bg-wheat-100 transition shadow-sm self-start sm:self-center">
                        <svg class="w-3.5 h-3.5 text-avocado-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        <span>{{ $rental->vendor->phone }}</span>
                    </a>
                @endif
            </div>

            <!-- JADWAL SEWA & METODE -->
            <div class="space-y-2.5">
                <span class="text-xs font-bold uppercase tracking-wider text-darkbrown-400 block">Jadwal Sewa & Pengambilan</span>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 rounded-2xl bg-[#FAF6ED] border border-wheat-200 text-xs">
                    <div class="space-y-0.5">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-avocado-700 block">Tanggal Ambil (Pickup)</span>
                        <div class="text-sm font-extrabold text-darkbrown-900">
                            {{ \Carbon\Carbon::parse($rental->pickup_date)->translatedFormat('d M Y') }}
                        </div>
                        <div class="text-darkbrown-600">
                            Metode: <strong>{{ $rental->pickup_method === 'delivery' ? 'Diantar (Delivery)' : 'Ambil di Toko (Self-Pickup)' }}</strong>
                        </div>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-goldenrod-700 block">Tanggal Kembali (Return)</span>
                        <div class="text-sm font-extrabold text-darkbrown-900">
                            {{ \Carbon\Carbon::parse($rental->return_date)->translatedFormat('d M Y') }}
                        </div>
                        <div class="text-darkbrown-600">
                            Pengembalian Fisik
                        </div>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-darkbrown-500 block">Durasi Sewa</span>
                        <div class="text-sm font-extrabold text-darkbrown-900">{{ $rental->rental_days }} Hari</div>
                        <div class="text-darkbrown-600">Total Durasi Pemakaian</div>
                    </div>
                </div>

                @if($rental->pickup_method === 'delivery' && $rental->delivery_address)
                    <div class="p-3 rounded-xl bg-wheat-50 border border-wheat-200 text-xs text-darkbrown-700">
                        📍 <strong>Alamat Pengantaran:</strong> {{ $rental->delivery_address }}
                    </div>
                @endif
            </div>

            <!-- DAFTAR BARANG YANG DISEWA -->
            <div class="space-y-3 pt-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-darkbrown-400 block">Daftar Barang yang Disewa</span>
                    <span class="text-xs text-darkbrown-500 font-semibold">{{ $rental->details->count() }} Unit Alat</span>
                </div>

                <div class="divide-y divide-wheat-200 border border-wheat-200 rounded-2xl overflow-hidden bg-white">
                    @foreach ($rental->details as $detail)
                        @php
                            $mItem = $detail->masterItem;
                            $unit = $detail->itemUnit;
                        @endphp
                        <div class="p-3.5 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-[#FAF6ED]/40 transition">
                            <div class="flex items-center gap-3.5 min-w-0 flex-1">
                                <div class="w-10 h-10 rounded-xl bg-wheat-100 border border-wheat-200 flex items-center justify-center shrink-0 text-darkbrown-800">
                                    ⛺
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-darkbrown-900 text-sm truncate">
                                            {{ $mItem?->name ?? 'Peralatan Camping' }}
                                        </h4>
                                        @if($unit)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-wheat-100 text-darkbrown-700 border border-wheat-300 shrink-0">
                                                Unit: {{ $unit->unit_code }}
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-sans font-medium bg-amber-50 text-amber-800 border border-amber-200 shrink-0">
                                                Alokasi saat pickup
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-darkbrown-500 mt-0.5">
                                        Rp {{ number_format($detail->daily_rate_snapshot, 0, ',', '.') }}/hari × {{ $rental->rental_days }} hari
                                        @if($detail->condition_before)
                                            • Kondisi awal: <strong>{{ $detail->condition_before }}</strong>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="text-left sm:text-right shrink-0">
                                <span class="font-extrabold text-sm sm:text-base text-darkbrown-900">
                                    Rp {{ number_format($detail->daily_rate_snapshot * $rental->rental_days, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- JAMINAN KTP & RINCIAN BIAYA & DENDA -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-4 border-t border-wheat-200 items-stretch">

                <!-- STATUS JAMINAN KTP -->
                <div class="p-5 rounded-2xl bg-[#FAF6ED] border border-wheat-300 flex flex-col justify-between space-y-4">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-darkbrown-500">Jaminan Identitas Fisik</span>
                            @php
                                $ktpBadgeClass = match($rental->ktp_collateral_status) {
                                    'held' => 'bg-goldenrod-100 text-goldenrod-900 border-goldenrod-300',
                                    'returned' => 'bg-avocado-100 text-avocado-900 border-avocado-300',
                                    default => 'bg-wheat-100 text-darkbrown-700 border-wheat-300',
                                };
                                $ktpLabel = match($rental->ktp_collateral_status) {
                                    'held' => 'KTP Ditahan di Toko',
                                    'returned' => 'KTP Telah Dikembalikan',
                                    default => 'Menunggu Serah Terima',
                                };
                            @endphp
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold border {{ $ktpBadgeClass }}">
                                {{ $ktpLabel }}
                            </span>
                        </div>

                        <div class="flex items-start gap-3 pt-1">
                            <div class="w-10 h-10 rounded-xl bg-goldenrod-100 text-goldenrod-800 border border-goldenrod-300 flex items-center justify-center shrink-0 font-bold text-sm">
                                🪪
                            </div>
                            <div>
                                <h4 class="font-extrabold text-sm text-darkbrown-900">Tanda Terima Digital KTP</h4>
                                <span class="font-mono text-xs text-darkbrown-600 font-semibold block">
                                    No: REC-{{ $rental->booking_code }}
                                </span>
                            </div>
                        </div>

                        <p class="text-xs text-darkbrown-600 leading-relaxed pt-1">
                            @if($rental->ktp_collateral_status === 'held')
                                KTP fisik asli disimpan aman oleh toko vendor sejak <strong>{{ $rental->ktp_received_at?->translatedFormat('d M Y, H:i') }} WIB</strong>. KTP dikembalikan langsung saat Anda menyerahkan kembali alat camping.
                            @elseif($rental->ktp_collateral_status === 'returned')
                                KTP fisik asli telah dikembalikan kepada penyewa pada <strong>{{ $rental->ktp_returned_at?->translatedFormat('d M Y, H:i') }} WIB</strong>.
                            @else
                                KTP fisik asli wajib diserahkan kepada kasir vendor saat serah terima barang (pickup). Tanpa deposit uang tunai.
                            @endif
                        </p>
                    </div>

                    <a href="{{ route('booking.receipt', $rental->booking_code) }}"
                       class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-extrabold text-xs text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 active:bg-goldenrod-400 border border-goldenrod-400 shadow-sm transition">
                        <span>Lihat Dokumen Tanda Terima KTP</span>
                        <svg class="w-4 h-4 text-darkbrown-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>

                <!-- TOTAL PEMBAYARAN & DENDA -->
                <div class="p-5 rounded-2xl bg-wheat-50/70 border border-wheat-200 flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-wheat-200">
                            <span class="text-xs font-bold uppercase tracking-wider text-darkbrown-500">Rincian Biaya</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold {{ $rental->status !== 'pending' ? 'bg-avocado-100 text-avocado-800 border border-avocado-300' : 'bg-sunglow-100 text-sunglow-900 border border-sunglow-300' }}">
                                {{ $rental->status !== 'pending' ? '✓ Lunas' : 'Menunggu Bayar' }}
                            </span>
                        </div>

                        <div class="space-y-2 text-xs text-darkbrown-600">
                            <div class="flex justify-between">
                                <span>Subtotal Sewa</span>
                                <span class="font-bold text-darkbrown-800">Rp {{ number_format($rental->subtotal, 0, ',', '.') }}</span>
                            </div>
                            @if($rental->delivery_fee > 0)
                                <div class="flex justify-between">
                                    <span>Biaya Pengantaran (Delivery)</span>
                                    <span class="font-bold text-darkbrown-800">Rp {{ number_format($rental->delivery_fee, 0, ',', '.') }}</span>
                                </div>
                            @endif
                            @if($rental->discount_amount > 0)
                                <div class="flex justify-between text-emerald-700 font-semibold">
                                    <span>Diskon</span>
                                    <span>- Rp {{ number_format($rental->discount_amount, 0, ',', '.') }}</span>
                                </div>
                            @endif

                            {{-- RINCIAN DENDA JIKA ADA --}}
                            @if($hasDenda)
                                <div class="pt-2 border-t border-red-200 text-red-700 space-y-1">
                                    @if($rental->total_late_fee > 0)
                                        <div class="flex justify-between">
                                            <span>Denda Keterlambatan</span>
                                            <span class="font-bold">+ Rp {{ number_format($rental->total_late_fee, 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                    @if($rental->total_damage_fee > 0)
                                        <div class="flex justify-between">
                                            <span>Denda Kerusakan Unit</span>
                                            <span class="font-bold">+ Rp {{ number_format($rental->total_damage_fee, 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="pt-3 border-t-2 border-wheat-200/90 flex flex-col space-y-3">
                        <div class="flex items-baseline justify-between">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-darkbrown-700 block">Total Akhir</span>
                                <span class="text-[11px] text-darkbrown-500 font-medium">Tanpa Deposit Uang Tunai</span>
                            </div>
                            <span class="text-2xl font-black {{ $hasDenda ? 'text-red-700' : 'text-darkbrown-900' }} tracking-tight font-heading">
                                Rp {{ number_format($totalFinal, 0, ',', '.') }}
                            </span>
                        </div>

                        {{-- Tombol bayar denda bila ada denda yang belum terselesaikan --}}
                        @if($hasDenda && Auth::check() && Auth::id() === $rental->user_id)
                            <button
                                type="button"
                                onclick="payPendingBooking()"
                                class="w-full py-2.5 px-4 rounded-xl font-bold text-xs bg-red-600 hover:bg-red-700 text-white shadow transition flex items-center justify-center gap-2"
                            >
                                <span>Bayar Denda (Rp {{ number_format($rental->total_late_fee + $rental->total_damage_fee, 0, ',', '.') }})</span>
                            </button>
                        @endif
                    </div>
                </div>

            </div>

        </div>

    </div>

    {{-- Midtrans Snap SDK Sandbox & Action Scripts --}}
    @if(config('services.midtrans.client_key'))
    <script src="{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
            data-client-key="{{ config('services.midtrans.client_key') }}"></script>
    @endif

    <script>
        function payPendingBooking() {
            const btn = document.getElementById('btn-pay-pending');
            if (btn) btn.disabled = true;

            fetch('{{ route('booking.pay', $rental->booking_code) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.snap_token) {
                    if (typeof window.snap !== 'undefined') {
                        window.snap.pay(data.snap_token, {
                            onSuccess: function() { syncPaymentStatus(); },
                            onPending: function() { syncPaymentStatus(); },
                            onError: function(result) {
                                alert('Pembayaran gagal: ' + (result.status_message || 'Terjadi kesalahan'));
                            },
                            onClose: function() { syncPaymentStatus(); }
                        });
                    } else {
                        alert('Snap SDK belum siap. Silakan muat ulang halaman.');
                    }
                } else {
                    alert(data.message || 'Gagal memproses transaksi Snap.');
                }
            })
            .catch(err => {
                alert('Terjadi kendala jaringan saat menghubungi server.');
            })
            .finally(() => {
                if (btn) btn.disabled = false;
            });
        }

        function syncPaymentStatus() {
            const btn = document.getElementById('btn-sync-status');
            const icon = document.getElementById('sync-icon');
            if (btn) btn.disabled = true;
            if (icon) icon.classList.add('inline-block', 'animate-spin');

            fetch('{{ route('booking.sync', $rental->booking_code) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.is_paid) {
                    alert('Pembayaran Berhasil! Pesanan Anda telah terkonfirmasi.');
                    window.location.reload();
                } else {
                    alert('Status pembayaran: ' + (data.payment_status || 'Menunggu Pembayaran') + '.\nJika sudah bayar di simulator Midtrans, tunggu beberapa detik lalu klik Cek Status kembali.');
                }
            })
            .catch(err => {
                alert('Gagal menyinkronkan status pembayaran.');
            })
            .finally(() => {
                if (btn) btn.disabled = false;
                if (icon) icon.classList.remove('animate-spin');
            });
        }

        window.payPendingBooking = payPendingBooking;
        window.syncPaymentStatus = syncPaymentStatus;
    </script>
</x-layouts.customer>
