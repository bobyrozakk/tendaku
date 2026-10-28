<div class="space-y-6" x-data="{ showPickupModal: false }" @close-pickup-modal.window="showPickupModal = false">
    <!-- Success Notification Banner -->
    @if($pickupSuccessMessage)
        <div class="p-4 rounded-2xl bg-avocado-50 border border-avocado-300 text-avocado-900 flex items-center justify-between shadow-tendaku-sm animate-fade-in">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-avocado-600 text-white flex items-center justify-center font-bold shrink-0 shadow-sm">
                    <i data-lucide="check" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <h4 class="font-extrabold text-sm">Serah Terima Berhasil!</h4>
                    <p class="text-xs text-avocado-800 mt-0.5">{{ $pickupSuccessMessage }}</p>
                </div>
            </div>
            <button wire:click="$set('pickupSuccessMessage', null)" class="text-avocado-700 hover:text-avocado-900 font-bold text-sm px-2.5 py-1 rounded-lg hover:bg-avocado-100 transition cursor-pointer">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    @endif

    <!-- Full-Width Card Antrean Pickup -->
    <x-card padding="p-0">
        <!-- Header Card & Search Filter -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-5 pt-5 pb-4 border-b border-wheat-200">
            <div class="flex items-center gap-3">
                <span class="p-2.5 rounded-xl bg-sunglow-100 text-darkbrown-800 border border-sunglow-200">
                    <i data-lucide="package-check" class="w-5 h-5 text-darkbrown-800"></i>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-extrabold text-base text-darkbrown-900">Antrean Pickup Hari Ini</h2>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sunglow-100 text-darkbrown-900 border border-sunglow-300">
                            <span class="w-1.5 h-1.5 rounded-full bg-sunglow-600 animate-pulse"></span>
                            {{ $totalCount ?? $filteredOrders->total() }} Siap Pickup
                        </span>
                    </div>
                    <p class="text-[11px] text-darkbrown-500">Klik transaksi untuk membuka verifikasi dan serah terima unit</p>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="w-full sm:w-80 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-darkbrown-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
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
                            <i data-lucide="user" class="w-5 h-5"></i>
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
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-darkbrown-400 shrink-0"></i>
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
                            <i data-lucide="chevron-right" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-16 text-center text-xs text-darkbrown-500 space-y-2">
                    <i data-lucide="file-question" class="w-12 h-12 mx-auto text-wheat-400"></i>
                    <p class="font-bold text-darkbrown-700">Tidak ada transaksi ditemukan</p>
                    <p class="text-[11px]">Gunakan kata kunci pencarian lain atau pastikan ada transaksi berstatus 'Siap Pickup'.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination & Footer Summary --}}
        <div class="px-5 py-4 border-t border-wheat-200/80 bg-wheat-50/30 flex flex-col sm:flex-row items-center justify-between gap-3">
            {{-- Informasi Counter Data --}}
            <div class="text-xs text-darkbrown-600 font-medium">
                @if($filteredOrders->total() > 0)
                    Menampilkan <span class="font-extrabold text-darkbrown-900">{{ $filteredOrders->firstItem() }}</span> – <span class="font-extrabold text-darkbrown-900">{{ $filteredOrders->lastItem() }}</span> dari <span class="font-extrabold text-avocado-800">{{ $filteredOrders->total() }}</span> pesanan siap pickup
                @else
                    Menampilkan <span class="font-bold text-darkbrown-900">0</span> pesanan siap pickup
                @endif
            </div>

            {{-- Navigation Links Pagination --}}
            @if($filteredOrders->hasPages())
                <div class="w-full sm:w-auto flex justify-center sm:justify-end">
                    {{ $filteredOrders->links('kasir.pagination') }}
                </div>
            @endif
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
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-6 pt-5 pb-4 border-b border-wheat-200 sticky top-0 bg-white/95 backdrop-blur-md z-20 rounded-t-2xl">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-wheat-100 text-darkbrown-900 border border-wheat-200">
                                    {{ $this->selectedOrder['id'] }}
                                </span>
                                <x-badge variant="sunglow" :dot="true">
                                    {{ $this->selectedOrder['status'] }}
                                </x-badge>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
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
                                class="p-2 rounded-xl text-darkbrown-400 hover:text-darkbrown-700 hover:bg-wheat-100 transition border border-transparent hover:border-wheat-200 cursor-pointer"
                                title="Tutup"
                            >
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body (Scrollable) -->
                    <div class="p-6 space-y-6">

                        <!-- Info Grid Customer & Kontak -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div class="p-3.5 rounded-2xl bg-wheat-50/70 border border-wheat-200/80 space-y-1">
                                <span class="text-darkbrown-500 font-semibold block text-[11px]">Nomor Telepon / WhatsApp:</span>
                                <div class="flex items-center justify-between">
                                    <span class="font-extrabold text-darkbrown-900 text-sm flex items-center gap-2">
                                        <i data-lucide="phone" class="w-4 h-4 text-emerald-700"></i>
                                        {{ $this->selectedOrder['phone'] }}
                                    </span>
                                    {{-- Tombol Chat WA dengan Icon Resmi WhatsApp --}}
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $this->selectedOrder['phone']) }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-bold text-white bg-[#25D366] hover:bg-[#20ba5a] px-3 py-1.5 rounded-xl shadow-sm hover:shadow transition-all active:scale-95">
                                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                        </svg>
                                        <span>Chat WA</span>
                                    </a>
                                </div>
                            </div>

                            <div class="p-3.5 rounded-2xl bg-wheat-50/70 border border-wheat-200/80 space-y-1">
                                <span class="text-darkbrown-500 font-semibold block text-[11px]">Periode Sewa:</span>
                                <span class="font-extrabold text-darkbrown-900 text-sm flex items-center gap-2">
                                    <i data-lucide="calendar" class="w-4 h-4 text-emerald-700"></i>
                                    {{ $this->selectedOrder['rental_period'] }}
                                </span>
                            </div>
                        </div>

                        <!-- 1. Unit yang Akan Diserahkan -->
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="font-bold text-sm text-darkbrown-900 flex items-center gap-2">
                                        <i data-lucide="box" class="w-4 h-4 text-avocado-600"></i>
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
                                                        <i data-lucide="scan-barcode" class="w-3.5 h-3.5 text-darkbrown-500"></i>
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
                                <i data-lucide="id-card" class="w-4 h-4 text-avocado-600"></i>
                                Verifikasi Foto Penyewa & KTP
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Foto Penyewa di Tempat -->
                                <div class="p-4 rounded-2xl transition-all duration-200 border flex flex-col justify-between space-y-3 {{ $this->isPhotoVerified ? 'bg-emerald-50/60 border-emerald-300 shadow-sm' : 'bg-wheat-50/70 border-wheat-200' }}">
                                    <div>
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-bold text-xs text-darkbrown-900">Foto Penyewa di Tempat</span>
                                            @if($this->isPhotoVerified)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-sm">
                                                    <i data-lucide="check" class="w-3 h-3 text-emerald-600"></i>
                                                    Terverifikasi
                                                </span>
                                            @else
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">Wajib Diunggah</span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-darkbrown-600">Ambil/unggah foto penyewa fisik saat serah terima barang.</p>
                                    </div>

                                    <!-- Photo Upload Input & Thumbnail Preview -->
                                    <div class="space-y-2">
                                        @if($tenantPhoto)
                                            <div class="p-2 rounded-xl bg-white border border-emerald-300 text-center space-y-1.5 shadow-sm">
                                                <img src="{{ $tenantPhoto->temporaryUrl() }}" alt="Preview Foto Penyewa" class="h-24 mx-auto object-cover rounded-lg border border-wheat-200 shadow-inner" />
                                                <span class="text-[10px] font-bold text-emerald-800 flex items-center justify-center gap-1">
                                                    <i data-lucide="check" class="w-3 h-3 text-emerald-600"></i>
                                                    Foto Penyewa Siap
                                                </span>
                                            </div>
                                        @elseif($this->isPhotoVerified)
                                            <div class="p-2 rounded-xl bg-white border border-emerald-300 flex items-center gap-2 shadow-sm">
                                                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">
                                                    <i data-lucide="check" class="w-4 h-4"></i>
                                                </div>
                                                <span class="text-xs font-bold text-emerald-800">Foto penyewa telah tersimpan</span>
                                            </div>
                                        @endif

                                        <label class="w-full cursor-pointer flex items-center justify-center gap-2 py-2 px-3 rounded-xl text-xs font-bold transition duration-200 shadow-sm {{ $this->isPhotoVerified ? 'bg-white text-emerald-800 border-2 border-emerald-300 hover:bg-emerald-50' : 'bg-avocado-600 hover:bg-avocado-700 text-white' }}">
                                            <i data-lucide="camera" class="w-4 h-4"></i>
                                            <span>{{ $this->isPhotoVerified ? 'Ganti Foto Penyewa' : 'Upload / Ambil Foto' }}</span>
                                            <input type="file" wire:model="tenantPhoto" accept="image/*" class="hidden" />
                                        </label>
                                        @error('tenantPhoto')
                                            <p class="text-[10px] text-red-600 font-semibold">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Verifikasi KTP -->
                                <div class="p-4 rounded-2xl transition-all duration-200 border flex flex-col justify-between space-y-3 {{ $tenantKtpUploaded ? 'bg-emerald-50/60 border-emerald-300 shadow-sm' : 'bg-wheat-50/70 border-wheat-200' }}">
                                    <div>
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-bold text-xs text-darkbrown-900">Kartu Tanda Penduduk (KTP)</span>
                                            @if($tenantKtpUploaded)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-sm">
                                                    <i data-lucide="check" class="w-3 h-3 text-emerald-600"></i>
                                                    Terverifikasi
                                                </span>
                                            @else
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">Belum Verified</span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-darkbrown-600">Verifikasi NIK dan foto KTP fisik penyewa sebagai jaminan sewa.</p>
                                    </div>

                                    <div>
                                        @if($tenantKtpUploaded)
                                            <div class="p-3 rounded-xl bg-white border border-emerald-300 flex items-center justify-between shadow-sm mb-2">
                                                <div class="flex items-center gap-2">
                                                    <i data-lucide="id-card" class="w-4 h-4 text-emerald-700"></i>
                                                    <span class="text-xs font-bold text-emerald-900">KTP Fisik Sudah Dicocokkan</span>
                                                </div>
                                                <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs">
                                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                </span>
                                            </div>
                                        @endif

                                        <button 
                                            wire:click="markTenantKtpUploaded" 
                                            type="button"
                                            class="w-full py-2 px-3 rounded-xl text-xs font-bold transition-all duration-200 shadow-sm flex items-center justify-center gap-1.5 {{ $tenantKtpUploaded ? 'bg-white text-emerald-800 border-2 border-emerald-300 cursor-default' : 'bg-avocado-600 hover:bg-avocado-700 text-white cursor-pointer active:scale-95' }}"
                                            {{ $tenantKtpUploaded ? 'disabled' : '' }}
                                        >
                                            @if($tenantKtpUploaded)
                                                <i data-lucide="check-check" class="w-4 h-4 text-emerald-600"></i>
                                                <span>KTP Terverifikasi (Selesai)</span>
                                            @else
                                                <i data-lucide="id-card" class="w-4 h-4"></i>
                                                <span>Verifikasi KTP Penyewa</span>
                                            @endif
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Checklist Serah Terima Fisik (Interaktif Hijau Terang Saat Dicentang) -->
                        <div class="p-5 rounded-2xl bg-wheat-50/70 border border-wheat-200 space-y-3">
                            <h3 class="font-bold text-xs text-darkbrown-900 uppercase tracking-wider flex items-center gap-1.5">
                                <i data-lucide="list-checks" class="w-4 h-4 text-avocado-600"></i>
                                Checklist Serah Terima Fisik
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5">
                                {{-- Checklist 1: Identitas sesuai --}}
                                <label class="p-3.5 rounded-2xl border transition-all duration-200 flex items-center gap-3 cursor-pointer select-none font-extrabold text-xs shadow-sm {{ $checkIdentity ? 'bg-emerald-50 border-emerald-400 text-emerald-950 ring-2 ring-emerald-300/60 shadow-emerald-950/10' : 'bg-white border-wheat-200 text-darkbrown-900 hover:border-avocado-300 hover:bg-wheat-50/50' }}">
                                    <input 
                                        type="checkbox" 
                                        wire:model.live="checkIdentity" 
                                        class="rounded-lg border-wheat-300 text-emerald-600 focus:ring-emerald-500 w-5 h-5 transition cursor-pointer" 
                                    />
                                    <div class="flex-1 min-w-0">
                                        <p class="leading-tight">Identitas Sesuai</p>
                                        <p class="text-[10px] font-normal {{ $checkIdentity ? 'text-emerald-700 font-semibold' : 'text-darkbrown-400' }}">KTP & identitas cocok</p>
                                    </div>
                                    @if($checkIdentity)
                                        <span class="w-5 h-5 rounded-full bg-emerald-200 text-emerald-800 flex items-center justify-center font-black text-xs shrink-0 animate-fade-in">
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                        </span>
                                    @endif
                                </label>

                                {{-- Checklist 2: Semua unit sesuai --}}
                                <label class="p-3.5 rounded-2xl border transition-all duration-200 flex items-center gap-3 cursor-pointer select-none font-extrabold text-xs shadow-sm {{ $checkUnits ? 'bg-emerald-50 border-emerald-400 text-emerald-950 ring-2 ring-emerald-300/60 shadow-emerald-950/10' : 'bg-white border-wheat-200 text-darkbrown-900 hover:border-avocado-300 hover:bg-wheat-50/50' }}">
                                    <input 
                                        type="checkbox" 
                                        wire:model.live="checkUnits" 
                                        class="rounded-lg border-wheat-300 text-emerald-600 focus:ring-emerald-500 w-5 h-5 transition cursor-pointer" 
                                    />
                                    <div class="flex-1 min-w-0">
                                        <p class="leading-tight">Semua Unit Sesuai</p>
                                        <p class="text-[10px] font-normal {{ $checkUnits ? 'text-emerald-700 font-semibold' : 'text-darkbrown-400' }}">Barang fisik dicek kasir</p>
                                    </div>
                                    @if($checkUnits)
                                        <span class="w-5 h-5 rounded-full bg-emerald-200 text-emerald-800 flex items-center justify-center font-black text-xs shrink-0 animate-fade-in">
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                        </span>
                                    @endif
                                </label>

                                {{-- Checklist 3: Barang diserahkan --}}
                                <label class="p-3.5 rounded-2xl border transition-all duration-200 flex items-center gap-3 cursor-pointer select-none font-extrabold text-xs shadow-sm {{ $checkHandover ? 'bg-emerald-50 border-emerald-400 text-emerald-950 ring-2 ring-emerald-300/60 shadow-emerald-950/10' : 'bg-white border-wheat-200 text-darkbrown-900 hover:border-avocado-300 hover:bg-wheat-50/50' }}">
                                    <input 
                                        type="checkbox" 
                                        wire:model.live="checkHandover" 
                                        class="rounded-lg border-wheat-300 text-emerald-600 focus:ring-emerald-500 w-5 h-5 transition cursor-pointer" 
                                    />
                                    <div class="flex-1 min-w-0">
                                        <p class="leading-tight">Barang Diserahkan</p>
                                        <p class="text-[10px] font-normal {{ $checkHandover ? 'text-emerald-700 font-semibold' : 'text-darkbrown-400' }}">Diterima oleh penyewa</p>
                                    </div>
                                    @if($checkHandover)
                                        <span class="w-5 h-5 rounded-full bg-emerald-200 text-emerald-800 flex items-center justify-center font-black text-xs shrink-0 animate-fade-in">
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                        </span>
                                    @endif
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Sticky Modal Footer (Melayang di Bawah Modal) -->
                    <div class="sticky bottom-0 bg-white/95 backdrop-blur-md px-6 py-4 border-t border-wheat-200 rounded-b-2xl shadow-lg z-20 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="w-full sm:w-auto">
                            @if(!$this->isPickupReady)
                                <div class="text-[11px] font-semibold text-amber-800 bg-amber-50 px-3 py-1.5 rounded-xl border border-amber-200 flex items-center gap-1.5">
                                    <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600 shrink-0"></i>
                                    <span>Lengkapi foto, KTP, dan 3 checklist untuk konfirmasi.</span>
                                </div>
                            @else
                                <div class="text-[11px] font-extrabold text-emerald-800 bg-emerald-50 px-3 py-1.5 rounded-xl border border-emerald-300 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>Semua syarat lengkap! Siap konfirmasi serah terima.</span>
                                </div>
                            @endif
                        </div>

                        <!-- Tombol Konfirmasi Dinamis (Abu-abu saat disabled, Hijau Gradasi Aktif saat ready) -->
                        <div class="w-full sm:w-auto">
                            <button 
                                type="button"
                                wire:click="confirmPickup"
                                class="w-full sm:w-auto py-2.5 px-6 rounded-xl font-extrabold text-xs tracking-wider uppercase transition-all duration-200 shadow-sm flex items-center justify-center gap-2 {{ $this->isPickupReady ? 'bg-gradient-to-r from-emerald-600 to-avocado-600 hover:from-emerald-500 hover:to-avocado-500 text-white cursor-pointer shadow-lg shadow-emerald-600/30 ring-2 ring-emerald-400/50 hover:scale-[1.02] active:scale-[0.98]' : 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-300 opacity-80' }}"
                                {{ !$this->isPickupReady ? 'disabled' : '' }}
                            >
                                @if(!$this->isPickupReady)
                                    <i data-lucide="lock" class="w-4 h-4 text-amber-600 shrink-0"></i>
                                    <span>Konfirmasi Serah Terima Barang</span>
                                @else
                                    <span class="p-0.5 rounded-md bg-white/20">
                                        <i data-lucide="check" class="w-3.5 h-3.5 text-white"></i>
                                    </span>
                                    <span>Konfirmasi Serah Terima Barang</span>
                                @endif
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    @endif
</div>