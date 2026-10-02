<x-layouts.customer>
    <x-slot name="title">
        Tanda Terima Digital KTP - #{{ $booking['status_jaminan_ktp']['receipt_number'] }} - Tendaku
    </x-slot>

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <nav class="flex items-center gap-2 text-xs text-darkbrown-500 mb-1.5">
                    <a href="{{ url('/') }}" class="hover:text-avocado-600 transition">Beranda</a>
                    <span class="text-wheat-400">/</span>
                    <a href="{{ route('booking.status', $booking['booking_code']) }}" class="hover:text-avocado-600 transition">Status Booking #{{ $booking['booking_code'] }}</a>
                    <span class="text-wheat-400">/</span>
                    <span class="text-darkbrown-800 font-semibold">Tanda Terima KTP</span>
                </nav>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-darkbrown-800 tracking-tight">
                        Digital Collateral Receipt
                    </h1>
                </div>
            </div>

            <div class="flex items-center gap-3 self-start sm:self-auto">
                <a href="{{ route('booking.status', $booking['booking_code']) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-darkbrown-800 bg-white border border-wheat-300 hover:bg-wheat-100 transition shadow-sm">
                    <svg class="w-4 h-4 text-darkbrown-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali ke Status Booking</span>
                </a>

                <button onclick="window.print()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 border border-goldenrod-400 shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Cetak Bukti</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6 max-w-4xl mx-auto space-y-6">

        <!-- OFFICIAL RECEIPT DOCUMENT CARD -->
        <div class="bg-white rounded-3xl border-2 border-wheat-300 p-6 sm:p-10 shadow-tendaku space-y-8">

            <!-- Document Brand & Barcode Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 pb-6 border-b-2 border-wheat-200">
                <div class="space-y-1">
                    <div class="flex items-center gap-2.5">
                        <x-application-logo size="md" :withText="false" />
                        <div>
                            <span class="text-xl font-black text-darkbrown-900 tracking-tight">TENDA<span class="text-avocado-600">KU</span></span>
                            <span class="block text-[10px] font-bold text-darkbrown-500 uppercase tracking-widest">Outdoor Rental Platform</span>
                        </div>
                    </div>
                    <p class="text-xs text-darkbrown-600 pt-1">
                        Surat Tanda Terima Elektronik Penyerahan Dokumen Jaminan Fisik (KTP)
                    </p>
                </div>

                <div class="text-left sm:text-right space-y-1">
                    <span class="font-mono text-lg sm:text-xl font-black text-darkbrown-900 block">
                        {{ $booking['status_jaminan_ktp']['receipt_number'] }}
                    </span>
                </div>
            </div>

            <!-- Meta Reference Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 p-4 rounded-2xl bg-[#FAF6ED] border border-wheat-200 text-xs">
                <div>
                    <span class="text-darkbrown-400 font-medium block">Kode Booking:</span>
                    <span class="font-mono font-bold text-darkbrown-900 text-sm">#{{ $booking['booking_code'] }}</span>
                </div>
                <div>
                    <span class="text-darkbrown-400 font-medium block">Waktu Serah Terima:</span>
                    <span class="font-bold text-darkbrown-800">{{ $booking['status_jaminan_ktp']['received_at'] }}</span>
                </div>
                <div>
                    <span class="text-darkbrown-400 font-medium block">Penerima Staf Vendor:</span>
                    <span class="font-bold text-darkbrown-800">{{ $booking['status_jaminan_ktp']['received_by'] }}</span>
                </div>
            </div>

            <!-- Two Columns: Identitas Pemilik & Detail Vendor -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- 1. Identitas Pemilik KTP (Penyewa) -->
                <div class="p-5 rounded-2xl border border-wheat-200 space-y-3">
                    <div class="flex items-center gap-2 pb-2 border-b border-wheat-200">
                        <div class="w-6 h-6 rounded-md bg-avocado-100 text-avocado-800 flex items-center justify-center font-bold text-xs">
                            👤
                        </div>
                        <h4 class="font-bold text-sm text-darkbrown-800">Identitas Pemilik KTP (Penyewa)</h4>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-darkbrown-500">Nama Lengkap:</span>
                            <span class="font-bold text-darkbrown-900">{{ $booking['customer']['name'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-darkbrown-500">Nomor Induk Kependudukan (NIK):</span>
                            <span class="font-mono font-bold text-darkbrown-900">{{ $booking['customer']['nik'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-darkbrown-500">No. WhatsApp / HP:</span>
                            <span class="font-bold text-darkbrown-900">{{ $booking['customer']['phone'] }}</span>
                        </div>
                        <div class="pt-2 border-t border-wheat-100">
                            <span class="text-darkbrown-500 block">Alamat Domisili KTP:</span>
                            <p class="font-medium text-darkbrown-800 mt-0.5 leading-relaxed">{{ $booking['customer']['address'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- 2. Vendor Penanggung Jawab Penyimpanan -->
                <div class="p-5 rounded-2xl border border-wheat-200 space-y-3">
                    <div class="flex items-center gap-2 pb-2 border-b border-wheat-200">
                        <div class="w-6 h-6 rounded-md bg-goldenrod-100 text-goldenrod-800 flex items-center justify-center font-bold text-xs">
                            🏢
                        </div>
                        <h4 class="font-bold text-sm text-darkbrown-800">Vendor Penanggung Jawab</h4>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-darkbrown-500">Nama Vendor Toko:</span>
                            <span class="font-bold text-darkbrown-900">{{ $booking['vendor']['name'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-darkbrown-500">Hotline / Telepon:</span>
                            <span class="font-bold text-darkbrown-900">{{ $booking['vendor']['phone'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-darkbrown-500">Lokasi Penahanan:</span>
                            <span class="font-bold text-darkbrown-900">{{ $booking['vendor']['city'] }}</span>
                        </div>
                        <div class="pt-2 border-t border-wheat-100">
                            <span class="text-darkbrown-500 block">Alamat Store / Workshop:</span>
                            <p class="font-medium text-darkbrown-800 mt-0.5 leading-relaxed">{{ $booking['vendor']['address'] }}</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- GRID DUA KOLOM: FOTO VERIFIKASI KTP & PROSEDUR PENGAMBILAN (KIRI - KANAN) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">
                <!-- KIRI: Foto Verifikasi Fisik KTP -->
                <div class="p-5 rounded-2xl bg-wheat-50 border border-wheat-200 flex flex-col justify-between space-y-4">
                    <div>
                        <h4 class="font-bold text-sm text-darkbrown-800">Foto Verifikasi Fisik KTP Pelanggan</h4>
                        <p class="text-xs text-darkbrown-500 mt-0.5">Diambil oleh petugas toko saat proses pickup serah terima barang</p>
                    </div>

                    <div class="w-full">
                        <!-- Frame: Simulasi Foto KTP Asli -->
                        <div class="p-4 bg-white rounded-xl border border-wheat-300 flex flex-col items-center justify-center text-center space-y-2">
                            <div class="w-full h-36 rounded-lg bg-wheat-100 border border-wheat-300 flex flex-col items-center justify-center text-darkbrown-700 relative overflow-hidden">
                                <svg class="w-12 h-12 text-darkbrown-400 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                </svg>
                                <span class="font-mono text-xs font-bold text-darkbrown-900">REPUBLIK INDONESIA - KTP</span>
                                <span class="text-[10px] text-darkbrown-500 font-mono">NIK: 357901************</span>
                                <div class="absolute bottom-1 right-2 px-1.5 py-0.5 rounded text-[9px] font-bold bg-avocado-500 text-white">
                                    DIVERIFIKASI
                                </div>
                            </div>
                            <span class="text-xs font-semibold text-darkbrown-700">Foto Fisik KTP Asli</span>
                        </div>
                    </div>
                </div>

                <!-- KANAN: Prosedur Pengambilan Kembali KTP Fisik -->
                <div class="p-5 rounded-2xl bg-[#FAF6ED] border border-wheat-300 flex flex-col justify-between space-y-4 text-xs text-darkbrown-700">
                    <div class="space-y-3">
                        <h4 class="font-extrabold text-sm text-darkbrown-900 flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-goldenrod-500 text-white flex items-center justify-center text-xs font-bold">i</span>
                            Prosedur Pengambilan Kembali KTP Fisik:
                        </h4>
                        <ol class="list-decimal list-inside space-y-2 leading-relaxed pl-1">
                            <li>KTP fisik asli disimpan dalam loker brankas toko vendor selama masa sewa (<strong>{{ $booking['pickup_date'] }} s.d {{ $booking['return_date'] }}</strong>).</li>
                            <li>Saat mengembalikan unit, tunjukkan nomor bukti <strong>{{ $booking['status_jaminan_ktp']['receipt_number'] }}</strong> ini kepada kasir/petugas toko.</li>
                            <li>Petugas toko akan mengecek kelengkapan unit alat camping dan langsung menyerahkan kembali KTP fisik Anda tanpa potongan biaya apapun.</li>
                        </ol>
                    </div>

                    <div class="p-3 bg-wheat-100/70 rounded-xl border border-wheat-200 text-[11px] text-darkbrown-600 flex items-center gap-2">
                        <svg class="w-4 h-4 text-goldenrod-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Simpan bukti tanda terima ini sebagai syarat mutlak klaim pengembalian fisik KTP.</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-layouts.customer>
