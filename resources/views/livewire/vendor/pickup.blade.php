<div class="space-y-6">
    <!-- Baris Judul Bagian & Badge Jumlah Transaksi Siap Pickup -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-wheat-300 shadow-sm">
        <div>
            <h3 class="font-bold text-base text-darkbrown-900">Daftar Antrean Serah Terima</h3>
            <p class="text-xs text-darkbrown-600">Pilih transaksi di sebelah kiri untuk memproses verifikasi dan serah terima unit.</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-sunglow-200 text-darkbrown-900 border border-sunglow-300">
                <span class="w-2 h-2 rounded-full bg-sunglow-500 animate-pulse"></span>
                {{ count($pickupOrders) }} Transaksi Siap Pickup
            </span>
        </div>
    </div>

    <!-- Success Notification Banner -->
    @if($pickupSuccessMessage)
        <div class="p-4 rounded-xl bg-avocado-100 border border-avocado-300 text-avocado-900 flex items-center justify-between shadow-sm animate-fade-in">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-avocado-600 text-white flex items-center justify-center font-bold shrink-0">
                    ✓
                </div>
                <div>
                    <h4 class="font-bold text-sm">Serah Terima Berhasil</h4>
                    <p class="text-xs text-avocado-800">{{ $pickupSuccessMessage }}</p>
                </div>
            </div>
            <button wire:click="$set('pickupSuccessMessage', null)" class="text-avocado-700 hover:text-avocado-900 font-bold text-sm px-2 py-1">
                ✕
            </button>
        </div>
    @endif

    <!-- Layout 2 Kolom -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- KOLOM KIRI: Daftar Pickup Hari Ini -->
        <div class="lg:col-span-4 space-y-4">
            <x-card variant="warm" class="!p-4">
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <h3 class="font-extrabold text-sm text-darkbrown-900 uppercase tracking-wider">
                            Daftar Pickup Hari Ini
                        </h3>
                        <span class="text-xs text-darkbrown-500 font-medium">{{ count($filteredOrders) }} transaksi</span>
                    </div>

                    <!-- Search Input -->
                    <div class="relative">
                        <x-text-input 
                            wire:model.live.debounce.250ms="searchPickup" 
                            placeholder="Cari ID Booking atau Nama..." 
                            class="w-full text-sm pl-9"
                        />
                        <svg class="w-4 h-4 text-darkbrown-400 absolute left-3 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
            </x-card>

            <!-- Card List Transaksi Siap Pickup -->
            <div class="space-y-3">
                @forelse($filteredOrders as $order)
                    <div 
                        wire:click="selectPickup('{{ $order['id'] }}')" 
                        class="p-4 rounded-xl border cursor-pointer transition-all duration-150 {{ $selectedPickupId === $order['id'] ? 'bg-wheat-100 border-avocado-600 shadow-md ring-2 ring-avocado-500/20' : 'bg-white border-wheat-300 hover:border-wheat-400 hover:bg-wheat-50/50' }}"
                    >
                        <div class="flex justify-between items-start mb-2">
                            <span class="font-bold text-darkbrown-900 text-sm flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-sunglow-500"></span>
                                {{ $order['customer_name'] }}
                            </span>
                            <span class="px-2 py-0.5 rounded-md text-xs font-mono font-bold bg-wheat-200 text-darkbrown-800 border border-wheat-300">
                                {{ $order['id'] }}
                            </span>
                        </div>

                        <div class="text-xs text-darkbrown-600 space-y-1">
                            <p class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-darkbrown-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ $order['rental_period'] }}
                            </p>
                            
                            <div class="flex justify-between items-center pt-2 border-t border-wheat-200/60 mt-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-sunglow-100 text-darkbrown-800 border border-sunglow-200">
                                    Status: {{ $order['status'] }}
                                </span>
                                <span class="text-[11px] font-medium text-darkbrown-500">
                                    {{ count($order['allocated_units']) }} Unit Dialokasikan
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-sm text-darkbrown-500 bg-white rounded-xl border border-wheat-300 space-y-2">
                        <svg class="w-10 h-10 mx-auto text-wheat-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="font-medium">Tidak ada transaksi Siap Pickup yang ditemukan.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- KOLOM KANAN: Detail Transaksi & Proses Serah Terima -->
        <div class="lg:col-span-8">
            @if($this->selectedOrder)
                <div class="space-y-6">
                    
                    <!-- 1. Detail Transaksi & Informasi Penyewa -->
                    <x-card class="space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-wheat-200 pb-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-wheat-200 text-darkbrown-900 border border-wheat-300">
                                        {{ $this->selectedOrder['id'] }}
                                    </span>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-sunglow-100 text-darkbrown-800 border border-sunglow-200">
                                        {{ $this->selectedOrder['status'] }}
                                    </span>
                                </div>
                                <h3 class="text-xl font-extrabold text-darkbrown-900 mt-1">
                                    {{ $this->selectedOrder['customer_name'] }}
                                </h3>
                            </div>

                            <div class="text-right">
                                <span class="text-xs font-semibold text-darkbrown-500 uppercase tracking-wider block">Status Pembayaran</span>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-avocado-100 text-avocado-800 border border-avocado-200 mt-1">
                                    <span class="w-2 h-2 rounded-full bg-avocado-600"></span>
                                    {{ $this->selectedOrder['payment_status'] }}
                                </span>
                            </div>
                        </div>

                        <!-- Info Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div class="p-3 rounded-xl bg-wheat-50 border border-wheat-200 space-y-1">
                                <span class="text-darkbrown-500 font-medium block">Nomor Telepon / WhatsApp:</span>
                                <span class="font-bold text-darkbrown-900 text-sm flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-avocado-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1.01 1.01 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    {{ $this->selectedOrder['phone'] }}
                                </span>
                            </div>

                            <div class="p-3 rounded-xl bg-wheat-50 border border-wheat-200 space-y-1">
                                <span class="text-darkbrown-500 font-medium block">Periode Sewa:</span>
                                <span class="font-bold text-darkbrown-900 text-sm flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-avocado-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ $this->selectedOrder['rental_period'] }}
                                </span>
                            </div>
                        </div>
                    </x-card>

                    <!-- 2. Unit yang Akan Diserahkan -->
                    <x-card class="space-y-4">
                        <div class="flex justify-between items-center border-b border-wheat-200 pb-3">
                            <div>
                                <h3 class="text-base font-bold text-darkbrown-900 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-avocado-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    Unit yang Akan Diserahkan
                                </h3>
                                <p class="text-xs text-darkbrown-600">Daftar unit & barcode fisik yang sudah dialokasikan saat pencocokan validasi booking.</p>
                            </div>

                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">
                                {{ count($this->selectedOrder['allocated_units']) }} Unit Dialokasikan
                            </span>
                        </div>

                        <!-- Tabel Unit Dialokasikan -->
                        <div class="overflow-x-auto rounded-xl border border-wheat-200">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-wheat-100 text-darkbrown-800 font-bold uppercase tracking-wider">
                                    <tr>
                                        <th class="py-2.5 px-3">Nama Barang</th>
                                        <th class="py-2.5 px-3">Barcode Unit</th>
                                        <th class="py-2.5 px-3 text-right">Status Alokasi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-wheat-200 bg-white">
                                    @foreach($this->selectedOrder['allocated_units'] as $unit)
                                        <tr class="hover:bg-wheat-50 transition-colors">
                                            <td class="py-3 px-3 font-bold text-darkbrown-900">
                                                {{ $unit['name'] }}
                                            </td>
                                            <td class="py-3 px-3 font-mono font-bold text-darkbrown-800">
                                                <span class="px-2.5 py-1 rounded bg-wheat-100 border border-wheat-300 font-mono text-xs">
                                                    {{ $unit['barcode'] }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 text-right">
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">
                                                    ✓ Ready ({{ $unit['status'] }})
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-card>

                    <!-- 3. Verifikasi Identitas & Foto Penyewa -->
                    <x-card class="space-y-4">
                        <h3 class="text-base font-bold text-darkbrown-900 flex items-center gap-2 border-b border-wheat-200 pb-3">
                            <svg class="w-5 h-5 text-avocado-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0h4" />
                            </svg>
                            Verifikasi Foto Penyewa & KTP
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Foto Penyewa di Tempat -->
                            <div class="p-4 rounded-xl bg-wheat-50 border border-wheat-200 flex flex-col justify-between space-y-3">
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="font-bold text-xs text-darkbrown-900">Foto Penyewa di Tempat</span>
                                        @if($this->isPhotoVerified)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">✓ Foto Tersedia</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-wheat-200 text-darkbrown-700">Wajib Diunggah</span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-darkbrown-600">Ambil/unggah foto penyewa fisik saat proses serah terima barang.</p>
                                </div>

                                <!-- Photo Upload Input & Preview -->
                                <div class="space-y-2">
                                    @if($tenantPhoto)
                                        <div class="p-2 rounded-lg bg-white border border-wheat-300 text-center space-y-1">
                                            <img src="{{ $tenantPhoto->temporaryUrl() }}" alt="Preview Foto Penyewa" class="h-28 mx-auto object-cover rounded-md border border-wheat-200" />
                                            <span class="text-[10px] font-bold text-avocado-700 block">✓ Foto Penyewa Siap</span>
                                        </div>
                                    @endif

                                    <div class="flex items-center gap-2">
                                        <label class="w-full cursor-pointer flex items-center justify-center gap-2 py-2 px-3 rounded-lg text-xs font-bold transition {{ $this->isPhotoVerified ? 'bg-avocado-100 text-avocado-800 border border-avocado-300' : 'bg-avocado-600 hover:bg-avocado-700 text-white shadow-sm' }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span>{{ $tenantPhoto ? 'Ganti Foto Penyewa' : 'Tambah File / Foto Customer' }}</span>
                                            <input type="file" wire:model="tenantPhoto" accept="image/*" class="hidden" />
                                        </label>
                                    </div>
                                    @error('tenantPhoto')
                                        <p class="text-[10px] text-red-600 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Verifikasi KTP -->
                            <div class="p-4 rounded-xl bg-wheat-50 border border-wheat-200 flex flex-col justify-between space-y-3">
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="font-bold text-xs text-darkbrown-900">Kartu Tanda Penduduk (KTP)</span>
                                        @if($tenantKtpUploaded)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">✓ KTP Verified</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-wheat-200 text-darkbrown-700">Belum Verified</span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-darkbrown-600">Verifikasi NIK dan foto KTP fisik penyewa sebagai jaminan sewa.</p>
                                </div>

                                <button 
                                    wire:click="markTenantKtpUploaded" 
                                    class="w-full py-2 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 {{ $tenantKtpUploaded ? 'bg-avocado-100 text-avocado-800 border border-avocado-300 cursor-default' : 'bg-avocado-600 hover:bg-avocado-700 text-white shadow-sm' }}"
                                    {{ $tenantKtpUploaded ? 'disabled' : '' }}
                                >
                                    @if($tenantKtpUploaded)
                                        ✓ KTP Terverifikasi
                                    @else
                                        🪪 Verifikasi KTP Penyewa
                                    @endif
                                </button>
                            </div>
                        </div>
                    </x-card>

                    <!-- 4. Checklist Serah Terima & Tombol Konfirmasi -->
                    <x-card class="space-y-4">
                        <h3 class="text-base font-bold text-darkbrown-900 flex items-center gap-2 border-b border-wheat-200 pb-3">
                            <svg class="w-5 h-5 text-avocado-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Checklist & Konfirmasi Serah Terima
                        </h3>

                        <div class="space-y-3 bg-wheat-50 p-4 rounded-xl border border-wheat-200 text-xs">
                            <label class="flex items-center gap-3 cursor-pointer select-none font-semibold text-darkbrown-900">
                                <input 
                                    type="checkbox" 
                                    wire:model.live="checkIdentity" 
                                    class="rounded border-wheat-300 text-avocado-600 focus:ring-avocado-500 w-4 h-4" 
                                />
                                <span>✓ Identitas penyewa sesuai</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer select-none font-semibold text-darkbrown-900">
                                <input 
                                    type="checkbox" 
                                    wire:model.live="checkUnits" 
                                    class="rounded border-wheat-300 text-avocado-600 focus:ring-avocado-500 w-4 h-4" 
                                />
                                <span>✓ Semua unit sesuai</span>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer select-none font-semibold text-darkbrown-900">
                                <input 
                                    type="checkbox" 
                                    wire:model.live="checkHandover" 
                                    class="rounded border-wheat-300 text-avocado-600 focus:ring-avocado-500 w-4 h-4" 
                                />
                                <span>✓ Barang telah diserahkan</span>
                            </label>
                        </div>

                        <!-- Info kelengkapan verifikasi -->
                        @if(!$this->isPickupReady)
                            <div class="p-3 rounded-lg bg-sunglow-100 border border-sunglow-200 text-darkbrown-800 text-xs flex items-center gap-2">
                                <svg class="w-4 h-4 text-sunglow-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Lengkapi upload foto penyewa, verifikasi KTP, dan beri centang pada 3 checklist di atas untuk mengaktifkan Konfirmasi Serah Terima.</span>
                            </div>
                        @endif

                        <!-- Tombol Konfirmasi Serah Terima -->
                        <div class="flex justify-end pt-2">
                            <button 
                                wire:click="confirmPickup"
                                class="w-full sm:w-auto py-3 px-6 rounded-xl font-bold text-sm transition shadow-sm flex items-center justify-center gap-2 {{ $this->isPickupReady ? 'bg-avocado-600 hover:bg-avocado-700 active:bg-avocado-800 text-white cursor-pointer' : 'bg-gray-300 text-gray-500 cursor-not-allowed' }}"
                                {{ !$this->isPickupReady ? 'disabled' : '' }}
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Konfirmasi Serah Terima
                            </button>
                        </div>
                    </x-card>

                </div>
            @else
                <x-card class="h-full flex flex-col items-center justify-center p-12 text-center text-darkbrown-500 min-h-[400px]">
                    <svg class="w-14 h-14 mb-3 text-wheat-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    <p class="text-base font-bold text-darkbrown-800">Tidak ada transaksi yang dipilih</p>
                    <p class="text-xs text-darkbrown-500 max-w-sm mt-1">Pilih transaksi pada daftar di sebelah kiri untuk memproses serah terima barang.</p>
                </x-card>
            @endif
        </div>

    </div>
</div>