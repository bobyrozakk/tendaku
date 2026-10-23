<x-layouts.customer>
    <x-slot name="title">
        Tanda Terima Digital KTP - #REC-{{ $rental->booking_code }} - Tendaku
    </x-slot>

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <nav class="flex items-center gap-2 text-xs text-darkbrown-500 mb-1.5">
                    <a href="{{ route('home') }}" class="hover:text-avocado-600 transition">Beranda</a>
                    <span class="text-wheat-400">/</span>
                    <a href="{{ route('customer.catalog.index') }}" class="hover:text-avocado-600 transition">Katalog</a>
                    <span class="text-wheat-400">/</span>
                    <a href="{{ route('booking.status', $rental->booking_code) }}" class="hover:text-avocado-600 transition">Status #{{ $rental->booking_code }}</a>
                    <span class="text-wheat-400">/</span>
                    <span class="text-darkbrown-800 font-semibold">Tanda Terima KTP</span>
                </nav>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-darkbrown-800 tracking-tight font-heading">
                        Digital Collateral Receipt
                    </h1>
                </div>
            </div>

            <div class="flex items-center gap-3 self-start sm:self-auto">
                <a href="{{ route('booking.status', $rental->booking_code) }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-darkbrown-800 bg-white border border-wheat-300 hover:bg-wheat-100 transition shadow-sm">
                    <i data-lucide="arrow-left" class="w-4 h-4 text-darkbrown-600"></i>
                    <span>Kembali ke Status Booking</span>
                </a>

                <button onclick="window.print()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-darkbrown-900 bg-sunglow-300 hover:bg-sunglow-400 border border-goldenrod-400 shadow-sm transition">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span>Cetak Bukti</span>
                </button>
            </div>
        </div>
    </x-slot>

    @php
        $customerName = $rental->isGuest() ? ($rental->guest_name ?? 'Penyewa Tamu') : ($rental->customer?->name ?? 'Pelanggan');
        $customerPhone = $rental->isGuest() ? ($rental->guest_phone ?? '-') : ($rental->customer?->phone ?? '-');
        $customerNik = $rental->isGuest() ? ($rental->guest_id_number ?? '-') : ($rental->customer?->nik ?? '-');
        $customerAddress = $rental->isGuest() ? ($rental->delivery_address ?? '-') : ($rental->customer?->address ?? '-');
        $staffName = $rental->pickedUpBy?->name ?? 'Staf Toko Vendor';
    @endphp

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
                        REC-{{ $rental->booking_code }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $rental->ktp_collateral_status === 'held' ? 'bg-goldenrod-100 text-goldenrod-900 border border-goldenrod-300' : ($rental->ktp_collateral_status === 'returned' ? 'bg-avocado-100 text-avocado-900 border border-avocado-300' : 'bg-wheat-100 text-darkbrown-700 border border-wheat-300') }}">
                        {{ $rental->ktp_collateral_status === 'held' ? 'KTP Ditahan di Toko' : ($rental->ktp_collateral_status === 'returned' ? 'KTP Telah Dikembalikan' : 'Status: ' . $rental->ktp_collateral_status) }}
                    </span>
                </div>
            </div>

            <!-- Meta Reference Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 p-4 rounded-2xl bg-[#FAF6ED] border border-wheat-200 text-xs">
                <div>
                    <span class="text-darkbrown-400 font-medium block">Kode Booking:</span>
                    <span class="font-mono font-bold text-darkbrown-900 text-sm">#{{ $rental->booking_code }}</span>
                </div>
                <div>
                    <span class="text-darkbrown-400 font-medium block">Waktu Serah Terima KTP:</span>
                    <span class="font-bold text-darkbrown-800">
                        {{ $rental->ktp_received_at ? $rental->ktp_received_at->translatedFormat('d M Y, H:i') . ' WIB' : 'Belum diserahterimakan' }}
                    </span>
                </div>
                <div>
                    <span class="text-darkbrown-400 font-medium block">Petugas Penerima Vendor:</span>
                    <span class="font-bold text-darkbrown-800">{{ $staffName }}</span>
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
                            <span class="font-bold text-darkbrown-900">{{ $customerName }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-darkbrown-500">Nomor KTP / NIK:</span>
                            <span class="font-mono font-bold text-darkbrown-900">{{ $customerNik }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-darkbrown-500">No. WhatsApp / HP:</span>
                            <span class="font-bold text-darkbrown-900">{{ $customerPhone }}</span>
                        </div>
                        <div class="pt-2 border-t border-wheat-100">
                            <span class="text-darkbrown-500 block">Alamat / Catatan:</span>
                            <p class="font-medium text-darkbrown-800 mt-0.5 leading-relaxed">{{ $customerAddress }}</p>
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
                            <span class="font-bold text-darkbrown-900">{{ $rental->vendor?->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-darkbrown-500">Hotline / Telepon:</span>
                            <span class="font-bold text-darkbrown-900">{{ $rental->vendor?->phone }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-darkbrown-500">Kota Lokasi:</span>
                            <span class="font-bold text-darkbrown-900">{{ $rental->vendor?->city }}</span>
                        </div>
                        <div class="pt-2 border-t border-wheat-100">
                            <span class="text-darkbrown-500 block">Alamat Store / Workshop:</span>
                            <p class="font-medium text-darkbrown-800 mt-0.5 leading-relaxed">{{ $rental->vendor?->address }}</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- GRID DUA KOLOM: FOTO VERIFIKASI KTP & PROSEDUR PENGAMBILAN -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">
                <!-- KIRI: Foto Verifikasi Fisik KTP (Lewat route berotorisasi) -->
                <div class="p-5 rounded-2xl bg-wheat-50 border border-wheat-200 flex flex-col justify-between space-y-4">
                    <div>
                        <h4 class="font-bold text-sm text-darkbrown-800">Foto Verifikasi Fisik KTP Pelanggan</h4>
                        <p class="text-xs text-darkbrown-500 mt-0.5">Dokumentasi yang diambil saat proses pickup serah terima barang</p>
                    </div>

                    <div class="w-full">
                        @if($rental->ktp_collateral_photo_url)
                            <div class="p-2 bg-white rounded-xl border border-wheat-300 flex flex-col items-center justify-center text-center space-y-2">
                                <div class="w-full h-44 rounded-lg bg-wheat-100 border border-wheat-300 overflow-hidden relative">
                                    <img 
                                        src="{{ route('booking.receipt.photo', $rental->booking_code) }}" 
                                        alt="Foto Verifikasi KTP"
                                        class="w-full h-full object-cover"
                                    />
                                    <div class="absolute bottom-1 right-2 px-1.5 py-0.5 rounded text-[9px] font-bold bg-avocado-600 text-white shadow-sm">
                                        TERVERIFIKASI
                                    </div>
                                </div>
                                <span class="text-[11px] font-semibold text-darkbrown-700">Foto Fisik KTP Penyewa</span>
                            </div>
                        @else
                            <div class="p-4 bg-white rounded-xl border border-dashed border-wheat-300 text-center text-xs text-darkbrown-500 space-y-1">
                                <span class="text-2xl block">📷</span>
                                <p class="font-medium">Foto verifikasi belum diunggah.</p>
                                <p class="text-[10px] text-darkbrown-400">Akan diambil oleh petugas toko saat pengambilan barang fisik.</p>
                            </div>
                        @endif
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
                            <li>KTP fisik asli disimpan dalam brankas penyimpanan toko vendor selama masa sewa (<strong>{{ \Carbon\Carbon::parse($rental->pickup_date)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($rental->return_date)->translatedFormat('d M Y') }}</strong>).</li>
                            <li>Saat mengembalikan unit alat camping, tunjukkan nomor bukti <strong>REC-{{ $rental->booking_code }}</strong> ini kepada kasir/petugas toko.</li>
                            <li>Petugas toko akan memeriksa kondisi barang dan langsung menyerahkan kembali KTP fisik asli Anda.</li>
                        </ol>
                    </div>

                    @if($rental->ktp_collateral_notes)
                        <div class="p-3 bg-wheat-100 rounded-xl border border-wheat-200 text-[11px] text-darkbrown-700">
                            📌 <strong>Catatan Penahanan:</strong> {{ $rental->ktp_collateral_notes }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</x-layouts.customer>
