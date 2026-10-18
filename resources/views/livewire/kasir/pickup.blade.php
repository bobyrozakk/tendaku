<div class="space-y-6" x-data="{ showPickupModal: false }">
    <!-- Success Notification Banner -->
    @if($pickupSuccessMessage)
        <div class="p-4 rounded-2xl bg-avocado-50 border border-avocado-300 text-avocado-900 flex items-center justify-between shadow-tendaku-sm animate-fade-in">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-avocado-600 text-white flex items-center justify-center font-bold shrink-0 shadow-sm">
                    ✓
                </div>
                <div>
                    <h4 class="font-extrabold text-sm">Serah Terima Berhasil!</h4>
                    <p class="text-xs text-avocado-800 mt-0.5">{{ $pickupSuccessMessage }}</p>
                </div>
            </div>
            <button wire:click="$set('pickupSuccessMessage', null)" class="text-avocado-700 hover:text-avocado-900 font-bold text-sm px-2.5 py-1 rounded-lg hover:bg-avocado-100 transition">
                ✕
            </button>
        </div>
    @endif

    <!-- Full-Width Card Antrean Pickup -->
    <x-card padding="p-0">
        <!-- Header Card & Search Filter -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-5 pt-5 pb-4 border-b border-wheat-200">
            <div class="flex items-center gap-3">
                <span class="p-2.5 rounded-xl bg-sunglow-100 text-darkbrown-800 border border-sunglow-200">
                    <svg class="w-5 h-5 text-darkbrown-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                    </svg>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-extrabold text-base text-darkbrown-900">Antrean Pickup Hari Ini</h2>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sunglow-100 text-darkbrown-900 border border-sunglow-300">
                            <span class="w-1.5 h-1.5 rounded-full bg-sunglow-600 animate-pulse"></span>
                            {{ count($pickupOrders) }} Siap Pickup
                        </span>
                    </div>
                    <p class="text-[11px] text-darkbrown-500">Klik transaksi untuk membuka verifikasi dan serah terima unit</p>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="w-full sm:w-80 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-darkbrown-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <x-text-input 
                    wire:model.live.debounce.250ms="searchPickup" 
                    placeholder="Cari ID Booking atau Nama..." 
                    class="w-full text-xs pl-9 py-2 bg-wheat-50/50 border-wheat-300 rounded-xl focus:bg-white"
                />
            </div>
        </div>

        <!-- Daftar Transaksi Siap Pickup (Full-Width List) -->
        <div class="p-5">
            @forelse($filteredOrders as $order)
                <div 
                    wire:click="selectPickup('{{ $order['id'] }}')" 
                    x-on:click="showPickupModal = true"
                    class="group flex items-center justify-between p-4 mb-3 border rounded-2xl cursor-pointer transition-all duration-200 hover:border-avocado-400 hover:shadow-tendaku-sm bg-white border-wheat-200/90 hover:bg-wheat-50/50 last:mb-0"
                >
                    <!-- Left: Customer info & Booking ID -->
                    <div class="flex items-center gap-4">
                        <div class="p-2.5 rounded-xl bg-wheat-100 border border-wheat-200 text-darkbrown-700 group-hover:bg-avocado-50 group-hover:border-avocado-200 group-hover:text-avocado-700 transition-colors shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-0.5">
                                <h4 class="font-extrabold text-darkbrown-900 text-sm">{{ $order['customer_name'] }}</h4>
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-mono font-bold bg-wheat-100 text-darkbrown-800 border border-wheat-200">
                                    {{ $order['id'] }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 text-[11px] text-darkbrown-500">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-darkbrown-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ $order['rental_period'] }}
                                </span>
                                <span class="hidden sm:inline text-darkbrown-300">•</span>
                                <span class="hidden sm:inline font-semibold text-darkbrown-600">{{ $order['phone'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Unit Count + Status Badge + Arrow -->
                    <div class="flex items-center gap-4 shrink-0">
                        <div class="text-right hidden sm:block">
                            <p class="text-xs font-bold text-darkbrown-800">{{ count($order['allocated_units']) }} Unit Fisik</p>
                            <p class="text-[10px] text-avocado-700 font-semibold">{{ $order['payment_status'] }}</p>
                        </div>
                        <x-badge variant="sunglow" size="sm" :dot="true">
                            {{ $order['status'] }}
                        </x-badge>
                        <div class="text-wheat-400 group-hover:text-avocado-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-16 text-center text-xs text-darkbrown-500 space-y-2">
                    <svg class="w-12 h-12 mx-auto text-wheat-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="font-bold text-darkbrown-700">Tidak ada transaksi ditemukan</p>
                    <p class="text-[11px]">Gunakan kata kunci pencarian lain atau pastikan ada transaksi berstatus 'Siap Pickup'.</p>
                </div>
            @endforelse
        </div>
    </x-card>

    <!-- ==========================================
         PICKUP / SERAH TERIMA MODAL (Center Popup Teleported to Body)
         ========================================== -->
    @if($this->selectedOrder)
        <template x-teleport="body">
            <div 
                x-show="showPickupModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-darkbrown-950/60 p-4"
                x-on:click.self="showPickupModal = false"
                style="display: none;"
            >
                <div 
                    x-show="showPickupModal"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="bg-white rounded-2xl border border-wheat-200 shadow-tendaku-lg w-full max-w-3xl max-h-[90vh] overflow-y-auto"
                >
                    <!-- Modal Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-6 pt-5 pb-4 border-b border-wheat-200 sticky top-0 bg-white z-10 rounded-t-2xl">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-wheat-100 text-darkbrown-900 border border-wheat-200">
                                    {{ $this->selectedOrder['id'] }}
                                </span>
                                <x-badge variant="sunglow" :dot="true">
                                    {{ $this->selectedOrder['status'] }}
                                </x-badge>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-avocado-600"></span>
                                    {{ $this->selectedOrder['payment_status'] }}
                                </span>
                            </div>
                            <h2 class="text-xl font-extrabold text-darkbrown-900 tracking-tight">
                                Serah Terima: {{ $this->selectedOrder['customer_name'] }}
                            </h2>
                        </div>

                        <!-- Action & Close -->
                        <div class="flex items-center gap-2">
                            <button 
                                x-on:click="showPickupModal = false" 
                                class="p-2 rounded-xl text-darkbrown-400 hover:text-darkbrown-700 hover:bg-wheat-100 transition border border-transparent hover:border-wheat-200"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-6">

                        <!-- Info Grid Customer & Kontak -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div class="p-3.5 rounded-2xl bg-wheat-50/70 border border-wheat-200/80 space-y-1">
                                <span class="text-darkbrown-500 font-semibold block text-[11px]">Nomor Telepon / WhatsApp:</span>
                                <div class="flex items-center justify-between">
                                    <span class="font-extrabold text-darkbrown-900 text-sm flex items-center gap-2">
                                        <svg class="w-4 h-4 text-avocado-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1.01 1.01 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                        {{ $this->selectedOrder['phone'] }}
                                    </span>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $this->selectedOrder['phone']) }}" target="_blank" class="text-[11px] font-bold text-avocado-700 hover:text-avocado-900 bg-avocado-100 hover:bg-avocado-200 px-2.5 py-1 rounded-lg transition">
                                        Chat WA ↗
                                    </a>
                                </div>
                            </div>

                            <div class="p-3.5 rounded-2xl bg-wheat-50/70 border border-wheat-200/80 space-y-1">
                                <span class="text-darkbrown-500 font-semibold block text-[11px]">Periode Sewa:</span>
                                <span class="font-extrabold text-darkbrown-900 text-sm flex items-center gap-2">
                                    <svg class="w-4 h-4 text-avocado-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ $this->selectedOrder['rental_period'] }}
                                </span>
                            </div>
                        </div>

                        <!-- 1. Unit yang Akan Diserahkan -->
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="font-bold text-sm text-darkbrown-900 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-avocado-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        Unit yang Akan Diserahkan
                                    </h3>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">
                                    {{ count($this->selectedOrder['allocated_units']) }} Unit Dialokasikan
                                </span>
                            </div>

                            <!-- Tabel Unit Dialokasikan -->
                            <div class="overflow-x-auto rounded-xl border border-wheat-200">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-wheat-100/70 text-darkbrown-800 font-bold uppercase tracking-wider">
                                        <tr>
                                            <th class="py-3 px-4">Nama Barang</th>
                                            <th class="py-3 px-4">Barcode Unit</th>
                                            <th class="py-3 px-4 text-right">Status Alokasi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-wheat-200 bg-white">
                                        @foreach($this->selectedOrder['allocated_units'] as $unit)
                                            <tr class="hover:bg-wheat-50/70 transition-colors">
                                                <td class="py-3.5 px-4 font-bold text-darkbrown-900 text-sm">
                                                    {{ $unit['name'] }}
                                                </td>
                                                <td class="py-3.5 px-4 font-mono font-bold text-darkbrown-800">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-wheat-100 border border-wheat-200 text-xs">
                                                        <svg class="w-3.5 h-3.5 text-darkbrown-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                                        </svg>
                                                        {{ $unit['barcode'] }}
                                                    </span>
                                                </td>
                                                <td class="py-3.5 px-4 text-right">
                                                    <x-badge variant="avocado" size="sm" :dot="true">
                                                        Ready ({{ $unit['status'] }})
                                                    </x-badge>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- 2. Verifikasi Identitas & Foto Penyewa -->
                        <div class="space-y-3">
                            <h3 class="font-bold text-sm text-darkbrown-900 flex items-center gap-2">
                                <svg class="w-4 h-4 text-avocado-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 012-2h2a2 2 0 012 2v1m-4 0h4" />
                                </svg>
                                Verifikasi Foto Penyewa & KTP
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Foto Penyewa di Tempat -->
                                <div class="p-4 rounded-2xl bg-wheat-50/70 border border-wheat-200 flex flex-col justify-between space-y-3">
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="font-bold text-xs text-darkbrown-900">Foto Penyewa di Tempat</span>
                                        @if($this->isPhotoVerified)
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">✓ Foto Tersedia</span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-wheat-200 text-darkbrown-700">Wajib Diunggah</span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-darkbrown-600">Ambil/unggah foto penyewa fisik saat serah terima barang.</p>
                                </div>

                                <!-- Photo Upload Input & Preview -->
                                <div class="space-y-2">
                                    @if($tenantPhoto)
                                        <div class="p-2 rounded-xl bg-white border border-wheat-300 text-center space-y-1 shadow-sm">
                                            <img src="{{ $tenantPhoto->temporaryUrl() }}" alt="Preview Foto Penyewa" class="h-24 mx-auto object-cover rounded-lg border border-wheat-200" />
                                            <span class="text-[10px] font-bold text-avocado-700 block">✓ Foto Penyewa Siap</span>
                                        </div>
                                    @endif

                                    <label class="w-full cursor-pointer flex items-center justify-center gap-2 py-2 px-3 rounded-xl text-xs font-bold transition duration-200 shadow-sm {{ $this->isPhotoVerified ? 'bg-avocado-100 text-avocado-800 border border-avocado-300' : 'bg-avocado-600 hover:bg-avocado-700 text-white' }}">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span>{{ $tenantPhoto ? 'Ganti Foto' : 'Upload / Ambil Foto' }}</span>
                                        <input type="file" wire:model="tenantPhoto" accept="image/*" class="hidden" />
                                    </label>
                                    @error('tenantPhoto')
                                        <p class="text-[10px] text-red-600 font-semibold">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Verifikasi KTP -->
                            <div class="p-4 rounded-2xl bg-wheat-50/70 border border-wheat-200 flex flex-col justify-between space-y-3">
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="font-bold text-xs text-darkbrown-900">Kartu Tanda Penduduk (KTP)</span>
                                        @if($tenantKtpUploaded)
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">✓ KTP Verified</span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-wheat-200 text-darkbrown-700">Belum Verified</span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-darkbrown-600">Verifikasi NIK dan foto KTP fisik penyewa sebagai jaminan sewa.</p>
                                </div>

                                <button 
                                    wire:click="markTenantKtpUploaded" 
                                    class="w-full py-2 px-3 rounded-xl text-xs font-bold transition duration-200 shadow-sm flex items-center justify-center gap-1.5 {{ $tenantKtpUploaded ? 'bg-avocado-100 text-avocado-800 border border-avocado-300 cursor-default' : 'bg-avocado-600 hover:bg-avocado-700 text-white cursor-pointer' }}"
                                    {{ $tenantKtpUploaded ? 'disabled' : '' }}
                                >
                                    @if($tenantKtpUploaded)
                                        <svg class="w-4 h-4 text-avocado-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span>KTP Terverifikasi</span>
                                    @else
                                        <span>🪪 Verifikasi KTP Penyewa</span>
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Checklist Serah Terima & Konfirmasi Button -->
                    <div class="p-4 rounded-2xl bg-wheat-50/70 border border-wheat-200 space-y-3">
                        <h3 class="font-bold text-xs text-darkbrown-900 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-avocado-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Checklist Serah Terima Fisik
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5">
                            <label class="p-3 rounded-xl border border-wheat-200 bg-white hover:border-avocado-300 transition-all flex items-center gap-2.5 cursor-pointer select-none font-bold text-xs text-darkbrown-900 shadow-sm">
                                <input 
                                    type="checkbox" 
                                    wire:model.live="checkIdentity" 
                                    class="rounded border-wheat-300 text-avocado-600 focus:ring-avocado-500/30 w-4 h-4" 
                                />
                                <span>Identitas sesuai</span>
                            </label>

                            <label class="p-3 rounded-xl border border-wheat-200 bg-white hover:border-avocado-300 transition-all flex items-center gap-2.5 cursor-pointer select-none font-bold text-xs text-darkbrown-900 shadow-sm">
                                <input 
                                    type="checkbox" 
                                    wire:model.live="checkUnits" 
                                    class="rounded border-wheat-300 text-avocado-600 focus:ring-avocado-500/30 w-4 h-4" 
                                />
                                <span>Semua unit sesuai</span>
                            </label>

                            <label class="p-3 rounded-xl border border-wheat-200 bg-white hover:border-avocado-300 transition-all flex items-center gap-2.5 cursor-pointer select-none font-bold text-xs text-darkbrown-900 shadow-sm">
                                <input 
                                    type="checkbox" 
                                    wire:model.live="checkHandover" 
                                    class="rounded border-wheat-300 text-avocado-600 focus:ring-avocado-500/30 w-4 h-4" 
                                />
                                <span>Barang diserahkan</span>
                            </label>
                        </div>

                        @if(!$this->isPickupReady)
                            <div class="p-3 rounded-xl bg-sunglow-100/70 border border-sunglow-300 text-darkbrown-900 text-xs flex items-center gap-2">
                                <svg class="w-4 h-4 text-sunglow-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="font-medium text-[11px]">Lengkapi upload foto, verifikasi KTP, dan centang 3 checklist di atas untuk konfirmasi.</span>
                            </div>
                        @endif

                        <div class="flex justify-end pt-2">
                            <button 
                                wire:click="confirmPickup"
                                x-on:click="if ({{ $this->isPickupReady ? 'true' : 'false' }}) { showPickupModal = false }"
                                class="w-full sm:w-auto py-2.5 px-6 rounded-xl font-bold text-xs tracking-wider uppercase transition-all duration-200 shadow-sm flex items-center justify-center gap-2 {{ $this->isPickupReady ? 'bg-avocado-600 hover:bg-avocado-700 active:bg-avocado-800 text-white cursor-pointer shadow-tendaku-avocado-glow' : 'bg-gray-200 text-gray-400 cursor-not-allowed border border-gray-300' }}"
                                {{ !$this->isPickupReady ? 'disabled' : '' }}
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Konfirmasi Serah Terima Barang</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    @endif
</div>