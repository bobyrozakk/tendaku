<x-layouts.customer>
    <x-slot name="title">
        Status Booking #{{ $booking['booking_code'] }} - Tendaku
    </x-slot>

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <nav class="flex items-center gap-2 text-xs text-darkbrown-500 mb-1.5">
                    <a href="{{ route('home') }}" class="hover:text-avocado-600 transition">Beranda</a>
                    <span class="text-wheat-400">/</span>
                    <a href="{{ route('customer.catalog.index') }}" class="hover:text-avocado-600 transition">Katalog</a>
                    <span class="text-wheat-400">/</span>
                    <a href="{{ route('checkout.index') }}" class="hover:text-avocado-600 transition">Booking</a>
                    <span class="text-wheat-400">/</span>
                    <span class="text-darkbrown-800 font-semibold">Status</span>
                </nav>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-darkbrown-800 tracking-tight">
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
                    <span class="font-mono text-sm sm:text-base font-extrabold text-darkbrown-900">#{{ $booking['booking_code'] }}</span>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6 space-y-6">

        <!-- 1. RENTAL TIMELINE TRACKER -->
        <div class="bg-white rounded-2xl border border-wheat-200 p-5 sm:p-6 shadow-tendaku-sm">
            <h3 class="text-xs font-bold uppercase tracking-wider text-darkbrown-500 mb-4">
                Alur Tahapan Rental & Verifikasi
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
                @foreach ($booking['timeline'] as $index => $step)
                    <div class="p-3.5 rounded-xl border flex flex-col justify-between space-y-2 {{ !empty($step['active']) ? 'bg-sunglow-50 border-goldenrod-400 ring-2 ring-goldenrod-400/30' : (!empty($step['completed']) ? 'bg-avocado-50/60 border-avocado-200' : 'bg-[#FAF6ED] border-wheat-200') }}">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[11px] font-bold {{ !empty($step['active']) ? 'bg-goldenrod-500 text-white' : (!empty($step['completed']) ? 'bg-avocado-500 text-white' : 'bg-wheat-200 text-darkbrown-600') }}">
                                    @if (!empty($step['completed']))
                                        ✓
                                    @elseif (!empty($step['active']))
                                        ●
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </span>
                                <span class="text-[10px] font-bold uppercase tracking-wider {{ !empty($step['active']) ? 'text-goldenrod-800' : (!empty($step['completed']) ? 'text-avocado-800' : 'text-darkbrown-400') }}">
                                    {{ !empty($step['active']) ? 'Sedang Berjalan' : (!empty($step['completed']) ? 'Selesai' : 'Menunggu') }}
                                </span>
                            </div>
                            <h4 class="font-extrabold text-xs text-darkbrown-900 leading-snug">
                                {{ $step['title'] }}
                            </h4>
                            <p class="text-[11px] text-darkbrown-600 mt-0.5 leading-relaxed line-clamp-2">
                                {{ $step['description'] }}
                            </p>
                        </div>
                        <div class="pt-1.5 border-t {{ !empty($step['active']) ? 'border-goldenrod-300' : (!empty($step['completed']) ? 'border-avocado-200' : 'border-wheat-200') }} text-[10px] font-semibold text-darkbrown-500">
                            {{ $step['time'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 2. SATU CARD UTAMA (JADWAL SEWA, DAFTAR BARANG, TANDA TERIMA KTP, TOTAL PEMBAYARAN) -->
        <div class="bg-white rounded-3xl border border-wheat-300 p-6 sm:p-8 shadow-tendaku-sm space-y-6">

            <!-- HEADER CARD: VENDOR TOKO -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-5 border-b border-wheat-200">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-darkbrown-800 text-wheat-200 flex items-center justify-center font-black text-lg shrink-0">
                        M
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base sm:text-lg font-extrabold text-darkbrown-900 leading-tight">
                                {{ $booking['vendor']['name'] }}
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-avocado-100 text-avocado-800 border border-avocado-300">
                                Official Partner
                            </span>
                        </div>
                        <p class="text-xs text-darkbrown-500 mt-0.5">
                            {{ $booking['vendor']['address'] }} • {{ $booking['vendor']['city'] }}
                        </p>
                    </div>
                </div>

                <a href="tel:{{ $booking['vendor']['phone'] }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-darkbrown-800 bg-[#FAF6ED] border border-wheat-300 hover:bg-wheat-100 transition shadow-sm self-start sm:self-center">
                    <svg class="w-3.5 h-3.5 text-avocado-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <span>{{ $booking['vendor']['phone'] }}</span>
                </a>
            </div>

            <!-- JADWAL SEWA (SIMPLE & MINIMALIS 3-KOLOM) -->
            <div class="space-y-2.5">
                <span class="text-xs font-bold uppercase tracking-wider text-darkbrown-400 block">Jadwal Sewa</span>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 rounded-2xl bg-[#FAF6ED] border border-wheat-200 text-xs">
                    <div class="space-y-0.5">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-avocado-700 block">Tanggal Ambil</span>
                        <div class="text-sm font-extrabold text-darkbrown-900">{{ $booking['pickup_date'] }}</div>
                        <div class="text-darkbrown-600">Pukul {{ $booking['pickup_time'] }} (Ambil di Toko)</div>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-goldenrod-700 block">Tanggal Kembali</span>
                        <div class="text-sm font-extrabold text-darkbrown-900">{{ $booking['return_date'] }}</div>
                        <div class="text-darkbrown-600">Maksimal {{ $booking['return_time'] }}</div>
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-darkbrown-500 block">Durasi Sewa</span>
                        <div class="text-sm font-extrabold text-darkbrown-900">{{ $booking['rental_days'] }} Hari</div>
                        <div class="text-darkbrown-600">2 Malam Camping</div>
                    </div>
                </div>
            </div>

            <!-- DAFTAR BARANG YANG DISEWA -->
            <div class="space-y-3 pt-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-darkbrown-400 block">Daftar Barang yang Disewa</span>
                    <span class="text-xs text-darkbrown-500 font-semibold">{{ count($booking['items']) }} Item Alat</span>
                </div>

                <div class="divide-y divide-wheat-200 border border-wheat-200 rounded-2xl overflow-hidden bg-white">
                    @foreach ($booking['items'] as $item)
                        <div class="p-3.5 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-[#FAF6ED]/40 transition">
                            <div class="flex items-center gap-3.5 min-w-0 flex-1">
                                <div class="w-10 h-10 rounded-xl bg-wheat-100 border border-wheat-200 flex items-center justify-center shrink-0 text-darkbrown-800">
                                    @if ($item['icon'] === 'tent')
                                        <svg class="w-5 h-5 text-avocado-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M19 20 10 4 1 20h18Z" />
                                            <path d="m14 20-4-8-4 8" />
                                        </svg>
                                    @elseif ($item['icon'] === 'sleeping_bag')
                                        <svg class="w-5 h-5 text-goldenrod-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect width="12" height="18" x="6" y="3" rx="4" />
                                            <path d="M10 7h4" />
                                            <path d="M10 11h4" />
                                        </svg>
                                    @elseif ($item['icon'] === 'stove')
                                        <svg class="w-5 h-5 text-sunglow-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="7" />
                                            <path d="M12 9v6" />
                                            <path d="M9 12h6" />
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-avocado-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M9 18h6" />
                                            <path d="M10 22h4" />
                                            <path d="M12 2v1" />
                                            <path d="M12 7a5 5 0 0 0-5 5c0 2 1.5 3.5 2 4.5h6c.5-1 2-2.5 2-4.5a5 5 0 0 0-5-5Z" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-darkbrown-900 text-sm truncate">
                                            {{ $item['name'] }}
                                        </h4>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-wheat-100 text-darkbrown-700 border border-wheat-300 shrink-0">
                                            {{ $item['unit_code'] }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-darkbrown-500 mt-0.5">
                                        {{ $item['qty'] }} Unit • Rp {{ number_format($item['price_per_day'], 0, ',', '.') }}/hari • Kondisi: {{ $item['condition_before'] }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-left sm:text-right shrink-0">
                                <span class="font-extrabold text-sm sm:text-base text-darkbrown-900">
                                    Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- BAGIAN BAWAH: TANDA TERIMA KTP & TOTAL PEMBAYARAN (SEJAJAR KIRI - KANAN) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-4 border-t border-wheat-200 items-stretch">

                <!-- TANDA TERIMA KTP -->
                <div class="p-5 rounded-2xl bg-[#FAF6ED] border border-wheat-300 flex flex-col justify-between space-y-4">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-darkbrown-500">Jaminan Identitas</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-goldenrod-100 text-goldenrod-900 border border-goldenrod-300">
                                {{ $booking['status_jaminan_ktp']['label'] }}
                            </span>
                        </div>

                        <div class="flex items-start gap-3 pt-1">
                            <div class="w-10 h-10 rounded-xl bg-goldenrod-100 text-goldenrod-800 border border-goldenrod-300 flex items-center justify-center shrink-0 font-bold text-sm">
                                📄
                            </div>
                            <div>
                                <h4 class="font-extrabold text-sm text-darkbrown-900">Tanda Terima Digital KTP</h4>
                                <span class="font-mono text-xs text-darkbrown-600 font-semibold block">
                                    No: {{ $booking['status_jaminan_ktp']['receipt_number'] }}
                                </span>
                            </div>
                        </div>

                        <p class="text-xs text-darkbrown-600 leading-relaxed pt-1">
                            KTP fisik asli disimpan pada <strong>{{ $booking['status_jaminan_ktp']['vault_box'] }}</strong> oleh {{ $booking['status_jaminan_ktp']['received_by'] }}. Tunjukkan bukti ini saat pengembalian barang untuk mengambil KTP Anda.
                        </p>
                    </div>

                    <a href="{{ route('booking.receipt', $booking['booking_code']) }}"
                       class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-extrabold text-xs text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 active:bg-goldenrod-400 border border-goldenrod-400 shadow-sm transition">
                        <svg class="w-4 h-4 text-darkbrown-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span>Lihat Tanda Terima KTP</span>
                        <svg class="w-4 h-4 text-darkbrown-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>

                <!-- TOTAL PEMBAYARAN -->
                <div class="p-5 rounded-2xl bg-wheat-50/70 border border-wheat-200 flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-wheat-200">
                            <span class="text-xs font-bold uppercase tracking-wider text-darkbrown-500">Rincian Pembayaran</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-avocado-100 text-avocado-800 border border-avocado-300">
                                ✓ Lunas Terverifikasi
                            </span>
                        </div>

                        <div class="space-y-2 text-xs text-darkbrown-600">
                            <div class="flex justify-between">
                                <span>Subtotal Sewa (2 Hari)</span>
                                <span class="font-bold text-darkbrown-800">Rp {{ number_format($booking['subtotal'], 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Biaya Pengantaran (Ambil di Toko)</span>
                                <span class="font-bold text-darkbrown-800">Rp 0 (Gratis)</span>
                            </div>
                            <div class="flex justify-between">
                                <div>
                                    <span>Uang Jaminan / Deposit</span>
                                    <span class="block text-[10px] text-goldenrod-700 font-semibold">100% Refundable</span>
                                </div>
                                <span class="font-bold text-darkbrown-800">Rp {{ number_format($booking['deposit'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t-2 border-wheat-200/90 flex items-baseline justify-between">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-darkbrown-700 block">Total Pembayaran</span>
                            <span class="text-[11px] text-avocado-800 font-medium">Via {{ $booking['status_pembayaran']['method'] }}</span>
                        </div>
                        <span class="text-2xl font-black text-darkbrown-900 tracking-tight">
                            Rp {{ number_format($booking['grand_total'], 0, ',', '.') }}
                        </span>
                    </div>
                </div>

            </div>

        </div>

        <!-- BANTUAN CS KENDALA SEWA (SUBTLE & MINIMALIS) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-3.5 rounded-2xl bg-[#FAF6ED] border border-wheat-200 text-xs text-darkbrown-600">
            <div class="flex items-center gap-2">
                <span class="text-darkbrown-800 font-bold">Butuh Bantuan Kendala Sewa?</span>
                <span class="text-darkbrown-500">Hubungi langsung staf toko {{ $booking['vendor']['name'] }}</span>
            </div>
            <a href="https://wa.me/6281298765432" target="_blank" class="font-bold text-avocado-700 hover:text-avocado-800 transition flex items-center gap-1 self-start sm:self-center">
                <span>WhatsApp: {{ $booking['vendor']['phone'] }}</span>
                <span>↗</span>
            </a>
        </div>

    </div>
</x-layouts.customer>
