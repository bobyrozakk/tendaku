<div class="space-y-6" x-data="{ showPickupModal: false }" @close-pickup-modal.window="showPickupModal = false">
    {{-- Flash Success Toast Notification --}}
    @if($pickupSuccessMessage)
        <div class="flex items-center justify-between gap-3 p-4 rounded-xl bg-avocado-50 border border-avocado-300 text-darkbrown-900 shadow-sm animate-fade-in" role="alert">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-avocado-600 text-white flex items-center justify-center shrink-0">
                    <i data-lucide="check" class="w-4 h-4"></i>
                </div>
                <div>
                    <p class="text-xs font-bold leading-tight">Serah Terima Berhasil!</p>
                    <p class="text-[11px] text-darkbrown-600 mt-0.5">{{ $pickupSuccessMessage }}</p>
                </div>
            </div>
            <button type="button" wire:click="$set('pickupSuccessMessage', null)" class="text-darkbrown-400 hover:text-darkbrown-700 p-1 cursor-pointer">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    @endif

    {{-- Top Section: Operational Status Cards (POS Semi-Modern Standard) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {{-- KPI 1: Siap Diambil Hari Ini --}}
        <div class="bg-white p-5 rounded-2xl border border-wheat-200/90 shadow-sm relative overflow-hidden flex flex-col justify-between transition-all duration-150 hover:-translate-y-0.5 hover:shadow-tendaku-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <span class="text-[11px] font-bold text-darkbrown-500 uppercase tracking-wider block">Siap diambil</span>
                    <p class="text-4xl font-extrabold text-avocado-700 tracking-tight mt-1 font-admin-number tabular-nums">
                        {{ $readyCount }}
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-avocado-100 text-avocado-800 border border-avocado-200 flex items-center justify-center shrink-0 shadow-xs">
                    <i data-lucide="calendar-check" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-wheat-100 flex items-center justify-between text-xs">
                <span class="text-darkbrown-500 font-medium">Unit fisik telah siap di toko</span>
                <span class="font-bold text-avocado-700">Antrean siap</span>
            </div>
        </div>

        {{-- KPI 2: Belum Diambil / Lewat Jadwal --}}
        <div class="bg-white p-5 rounded-2xl border border-wheat-200/90 shadow-sm relative overflow-hidden flex flex-col justify-between transition-all duration-150 hover:-translate-y-0.5 hover:shadow-tendaku-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <span class="text-[11px] font-bold text-darkbrown-500 uppercase tracking-wider block">Lewat jadwal pickup</span>
                    <p class="text-4xl font-extrabold {{ $overdueCount > 0 ? 'text-rose-600' : 'text-darkbrown-700' }} tracking-tight mt-1 font-admin-number tabular-nums">
                        {{ $overdueCount }}
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl {{ $overdueCount > 0 ? 'bg-rose-100 text-rose-800 border-rose-200' : 'bg-wheat-100 text-darkbrown-700 border-wheat-200' }} border flex items-center justify-center shrink-0 shadow-xs">
                    <i data-lucide="clock-alert" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-wheat-100 flex items-center justify-between text-xs">
                <span class="text-darkbrown-500 font-medium">Belum diambil oleh penyewa</span>
                <span class="font-bold {{ $overdueCount > 0 ? 'text-rose-700' : 'text-darkbrown-400' }}">
                    {{ $overdueCount > 0 ? 'Perlu tindak lanjut' : 'Kedaluwarsa' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Main Workarea: Unified Workspace Card (No Cluttered Double Headers) --}}
    <div class="bg-white rounded-2xl border border-wheat-200/90 shadow-sm overflow-hidden">
        {{-- Section Bar: Search & Segmentation (Single Row Header) --}}
        <div class="p-4 sm:p-5 border-b border-wheat-200/80 bg-wheat-50/40 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-extrabold text-darkbrown-900 tracking-tight flex items-center gap-2">
                    <i data-lucide="package-check" class="w-4 h-4 text-avocado-700"></i>
                    <span>Antrean Serah Terima (Pickup)</span>
                </h2>
                <p class="text-xs text-darkbrown-500 mt-0.5">Pilih transaksi untuk verifikasi identitas dan serahkan unit sewa</p>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                {{-- Search Bar --}}
                <div class="relative w-full sm:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-darkbrown-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.250ms="searchPickup"
                        placeholder="Cari ID Booking / Nama..."
                        class="w-full text-xs pl-10 pr-3.5 py-2.5 bg-white border border-wheat-300 rounded-xl text-darkbrown-900 placeholder-darkbrown-400 focus:outline-none focus:ring-2 focus:ring-avocado-500/30 focus:border-avocado-600 transition"
                    />
                </div>

                {{-- Segmented Tabs --}}
                <div class="inline-flex p-1 bg-wheat-200/60 rounded-xl border border-wheat-300/70 shrink-0">
                    <button wire:click="setTab('siap')"
                        class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all duration-150 flex items-center gap-2 cursor-pointer active:scale-[0.97] {{ $activeTab === 'siap' ? 'bg-white text-darkbrown-900 shadow-sm border border-wheat-200' : 'text-darkbrown-600 hover:text-darkbrown-900' }}">
                        <span>Siap Diambil</span>
                        <span class="px-1.5 py-0.5 text-[10px] rounded-full font-admin-number font-black {{ $activeTab === 'siap' ? 'bg-avocado-100 text-avocado-900' : 'bg-wheat-300/70 text-darkbrown-700' }}">
                            {{ $readyCount }}
                        </span>
                    </button>
                    <button wire:click="setTab('belum_diambil')"
                        class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all duration-150 flex items-center gap-2 cursor-pointer active:scale-[0.97] {{ $activeTab === 'belum_diambil' ? 'bg-white text-rose-950 shadow-sm border border-wheat-200' : 'text-darkbrown-600 hover:text-rose-900' }}">
                        <span>Belum Diambil</span>
                        <span class="px-1.5 py-0.5 text-[10px] rounded-full font-admin-number font-black {{ $activeTab === 'belum_diambil' ? 'bg-rose-100 text-rose-900' : 'bg-wheat-300/70 text-darkbrown-700' }}">
                            {{ $overdueCount }}
                        </span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Orders List (Clean Divide-y Rows, No Bulky Nested Cards) --}}
        <div class="divide-y divide-wheat-200/70">
            @forelse($filteredOrders as $order)
                <div
                    wire:click="selectPickup('{{ $order['id'] }}')"
                    x-on:click="showPickupModal = true"
                    class="group p-4 sm:p-5 hover:bg-wheat-50/70 active:scale-[0.998] transition-all duration-150 cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-4 {{ $order['is_overdue_pickup'] ? 'bg-rose-50/30' : 'bg-white' }}">

                    {{-- Left column: Customer info & Booking details --}}
                    <div class="flex items-start sm:items-center gap-3.5 min-w-0">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center font-extrabold text-sm shrink-0 {{ $order['is_overdue_pickup'] ? 'bg-rose-100 border border-rose-200 text-rose-800' : 'bg-wheat-100 border border-wheat-200 text-darkbrown-800' }} group-hover:bg-avocado-50 group-hover:border-avocado-300 group-hover:text-avocado-800 transition-colors shadow-xs">
                            {{ strtoupper(substr($order['customer_name'], 0, 1)) }}
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-extrabold text-sm text-darkbrown-900 group-hover:text-avocado-800 transition-colors truncate">
                                    {{ $order['customer_name'] }}
                                </h3>
                                <span class="font-mono text-[11px] font-bold px-2 py-0.5 rounded-md bg-wheat-100 text-darkbrown-700 border border-wheat-200">
                                    {{ $order['id'] }}
                                </span>
                                @if($order['is_overdue_pickup'])
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-rose-800 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200">
                                        <i data-lucide="alert-triangle" class="w-3 h-3 text-rose-600"></i>
                                        Terlambat {{ $order['days_late'] }} hari
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-3 mt-1 text-xs text-darkbrown-500 flex-wrap">
                                <span class="flex items-center gap-1.5 font-medium {{ $order['is_overdue_pickup'] ? 'text-rose-700 font-bold' : '' }}">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 {{ $order['is_overdue_pickup'] ? 'text-rose-600' : 'text-darkbrown-400' }} shrink-0"></i>
                                    <span>{{ $order['rental_period'] }}</span>
                                </span>
                                <span>•</span>
                                <span class="flex items-center gap-1 font-mono text-darkbrown-600 font-medium">
                                    <i data-lucide="phone" class="w-3.5 h-3.5 text-darkbrown-400"></i>
                                    <span>{{ $order['phone'] }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Right column: Unit count, Financials & Actions --}}
                    <div class="flex items-center justify-between sm:justify-end gap-4 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-wheat-100">
                        <div class="text-left sm:text-right">
                            <span class="text-xs font-bold text-darkbrown-900 font-admin-number tabular-nums block">
                                {{ count($order['allocated_units']) }} Unit Fisik
                            </span>
                            <span class="text-[11px] font-semibold {{ ($order['is_fully_paid'] ?? true) ? 'text-avocado-700' : 'text-amber-700 font-bold' }}">
                                {{ $order['payment_status'] }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($order['is_overdue_pickup'])
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-300 px-2.5 py-1 rounded-lg">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-pulse"></span>
                                    Belum diambil
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold bg-sunglow-100 text-sunglow-900 border border-sunglow-300 px-2.5 py-1 rounded-lg">
                                    <span class="w-1.5 h-1.5 rounded-full bg-sunglow-600"></span>
                                    Siap pickup
                                </span>
                            @endif

                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-darkbrown-400 group-hover:text-avocado-800 group-hover:bg-wheat-100 transition-all duration-150 group-hover:translate-x-0.5">
                                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-16 text-center text-darkbrown-500 space-y-3 px-4">
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-wheat-100 border border-wheat-200 flex items-center justify-center text-darkbrown-400">
                        <i data-lucide="inbox" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <p class="font-extrabold text-sm text-darkbrown-800">Tidak ada antrean serah terima</p>
                        <p class="text-xs text-darkbrown-500 max-w-sm mx-auto mt-1">
                            {{ $searchPickup ? "Tidak ada pesanan yang sesuai dengan kata kunci '{$searchPickup}'." : ($activeTab === 'belum_diambil' ? 'Tidak ada pesanan yang terlewat jadwal pickup.' : 'Tidak ada pesanan siap pickup hari ini.') }}
                        </p>
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Pagination & Footer Summary --}}
        @if($filteredOrders->hasPages() || $filteredOrders->total() > 0)
            <div class="px-5 py-4 border-t border-wheat-200/80 bg-wheat-50/30 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-xs text-darkbrown-600 font-medium">
                    Menampilkan <span class="font-bold text-darkbrown-900">{{ $filteredOrders->firstItem() ?? 0 }}</span> – <span class="font-bold text-darkbrown-900">{{ $filteredOrders->lastItem() ?? 0 }}</span> dari <span class="font-bold text-darkbrown-900">{{ $filteredOrders->total() }}</span> transaksi
                </div>

                @if($filteredOrders->hasPages())
                    <div class="w-full sm:w-auto flex justify-center sm:justify-end">
                        {{ $filteredOrders->links('kasir.pagination') }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- MODAL SERAH TERIMA & VERIFIKASI (Streamlined, Ergonomic & Semi-Modern) --}}
    @if($this->selectedOrder)
        <template x-teleport="body">
            <div
                x-show="showPickupModal"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-darkbrown-950/65 p-4"
                x-on:click.self="showPickupModal = false"
                x-on:keydown.escape.window="showPickupModal = false"
                style="display: none;"
            >
                <div
                    x-show="showPickupModal"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="bg-white rounded-2xl border border-wheat-200 shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden relative"
                >
                    {{-- Modal Header Bar --}}
                    <div class="px-6 py-4 border-b border-wheat-200 bg-wheat-50/50 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-xs font-black px-2.5 py-1 rounded bg-white text-darkbrown-900 border border-wheat-300 shadow-sm">
                                {{ $this->selectedOrder['id'] }}
                            </span>
                            <div>
                                <h3 class="text-sm font-extrabold text-darkbrown-900 leading-tight">
                                    Serah Terima: {{ $this->selectedOrder['customer_name'] }}
                                </h3>
                                <p class="text-[11px] text-darkbrown-500">
                                    {{ $this->selectedOrder['rental_period'] }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($this->selectedOrder['is_fully_paid'] ?? true)
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    Lunas
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-900 border border-amber-200">
                                    Sisa: Rp {{ number_format($this->selectedOrder['remaining_amount'] ?? 0, 0, ',', '.') }}
                                </span>
                            @endif

                            <button
                                type="button"
                                x-on:click="showPickupModal = false"
                                class="p-1.5 rounded-lg text-darkbrown-400 hover:text-darkbrown-800 hover:bg-wheat-200/60 transition-colors cursor-pointer"
                                title="Tutup (Esc)">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Modal Scrollable Body --}}
                    <div class="p-6 overflow-y-auto space-y-5 flex-1">

                        {{-- Pelunasan Tunai Alert (Jika belum lunas) --}}
                        @if(!($this->selectedOrder['is_fully_paid'] ?? true))
                            <div class="p-3.5 rounded-xl bg-amber-50/90 border border-amber-300 text-darkbrown-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center shrink-0">
                                        <i data-lucide="wallet" class="w-4 h-4"></i>
                                    </div>
                                    <div class="text-xs">
                                        <p class="font-bold">Tagihan Belum Lunas (Sisa: Rp {{ number_format($this->selectedOrder['remaining_amount'] ?? 0, 0, ',', '.') }})</p>
                                        <p class="text-[11px] text-darkbrown-600 mt-0.5">Terima pelunasan tunai kasir sebelum menyerahkan barang.</p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    wire:click="acceptCashPayment"
                                    wire:confirm="Terima pelunasan tunai sebesar Rp {{ number_format($this->selectedOrder['remaining_amount'] ?? 0, 0, ',', '.') }} dari penyewa?"
                                    wire:loading.attr="disabled"
                                    class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-avocado-700 hover:bg-avocado-800 active:scale-[0.98] transition shadow-xs shrink-0 cursor-pointer">
                                    Terima Pelunasan Tunai
                                </button>
                            </div>
                        @endif

                        {{-- Quick Info Strip --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div class="p-3 rounded-xl bg-wheat-50/70 border border-wheat-200 flex items-center justify-between">
                                <span class="text-darkbrown-500 font-medium text-[11px]">Nomor WhatsApp:</span>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-darkbrown-900">{{ $this->selectedOrder['phone'] }}</span>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $this->selectedOrder['phone']) }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-white bg-[#25D366] hover:bg-[#20ba5a] px-2 py-0.5 rounded-md transition active:scale-95 shadow-2xs">
                                        Chat WA
                                    </a>
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-wheat-50/70 border border-wheat-200 flex items-center justify-between">
                                <span class="text-darkbrown-500 font-medium text-[11px]">Total Unit Fisik:</span>
                                <span class="font-bold text-darkbrown-900 font-admin-number">{{ count($this->selectedOrder['allocated_units']) }} Barang Siap Serah</span>
                            </div>
                        </div>

                        {{-- Unit Fisik yang Diserahkan --}}
                        <div class="space-y-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-darkbrown-700 block">
                                Rincian Unit Fisik Pesanan
                            </span>
                            <div class="overflow-x-auto rounded-xl border border-wheat-200">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-wheat-100 text-darkbrown-800 font-bold uppercase tracking-wider">
                                        <tr>
                                            <th class="py-2.5 px-4">Nama Barang</th>
                                            <th class="py-2.5 px-4">Barcode Unit</th>
                                            <th class="py-2.5 px-4 text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-wheat-200 bg-white">
                                        @foreach($this->selectedOrder['allocated_units'] as $unit)
                                            <tr class="hover:bg-wheat-50/50 transition-colors">
                                                <td class="py-3 px-4 font-bold text-darkbrown-900">
                                                    {{ $unit['name'] }}
                                                </td>
                                                <td class="py-3 px-4 font-mono">
                                                    <span class="px-2 py-0.5 rounded bg-wheat-100 text-darkbrown-800 border border-wheat-200 font-bold">
                                                        {{ $unit['barcode'] }}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-4 text-center">
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-avocado-50 text-avocado-800 border border-avocado-200">
                                                        <i data-lucide="check" class="w-3 h-3 text-avocado-600"></i>
                                                        Siap
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                                                {{-- 2. Verifikasi Identitas & Foto Penyewa --}}
                        <div class="space-y-3">
                            <h3 class="font-bold text-sm text-darkbrown-900 flex items-center gap-2">
                                <i data-lucide="id-card" class="w-4 h-4 text-avocado-600"></i>
                                Verifikasi Foto Penyewa & KTP
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                                {{-- Foto Penyewa di Tempat --}}
                                <div class="p-4 rounded-2xl transition-all duration-200 border flex flex-col justify-between space-y-3 {{ $this->isPhotoVerified ? 'bg-emerald-50/60 border-emerald-300 shadow-sm' : 'bg-wheat-50/70 border-wheat-200' }}">
                                    <div>
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-bold text-xs text-darkbrown-900">
                                                Foto Penyewa di Tempat
                                            </span>

                                            @if($this->isPhotoVerified)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-sm">
                                                    <i data-lucide="check" class="w-3 h-3 text-emerald-600"></i>
                                                    Terverifikasi
                                                </span>
                                            @else
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                    Wajib Diunggah
                                                </span>
                                            @endif
                                        </div>

                                        <p class="text-[11px] text-darkbrown-600">
                                            Unggah foto JPG, PNG, atau WebP. Foto besar dikompres otomatis sebelum diunggah.
                                        </p>
                                    </div>

                                    {{-- Photo Upload --}}
                                    <div
                                        class="space-y-2"
                                        x-data="{
                                            uploadError: '',
                                            isPreparingPhoto: false,

                                            async prepareTenantPhoto(event) {
                                                const file = event.target.files?.[0];
                                                const maxBytes = 1850000;

                                                if (!file) {
                                                    return;
                                                }

                                                this.uploadError = '';

                                                if (!file.type.startsWith('image/')) {
                                                    this.uploadError = 'Pilih file foto dengan format gambar.';
                                                    event.target.value = '';
                                                    return;
                                                }

                                                if (file.size > 20 * 1024 * 1024) {
                                                    this.uploadError = 'Ukuran foto asli maksimal 20 MB.';
                                                    event.target.value = '';
                                                    return;
                                                }

                                                let uploadFile = file;
                                                let bitmap = null;

                                                try {
                                                    if (
                                                        file.size > maxBytes ||
                                                        !['image/jpeg', 'image/png', 'image/webp'].includes(file.type)
                                                    ) {
                                                        this.isPreparingPhoto = true;
                                                        bitmap = await createImageBitmap(file);

                                                        const canvas = document.createElement('canvas');

                                                        let scale = Math.min(
                                                            1,
                                                            1920 / Math.max(bitmap.width, bitmap.height)
                                                        );

                                                        let quality = 0.82;
                                                        let compressedBlob = null;

                                                        for (let attempt = 0; attempt < 7; attempt++) {
                                                            canvas.width = Math.max(
                                                                1,
                                                                Math.round(bitmap.width * scale)
                                                            );

                                                            canvas.height = Math.max(
                                                                1,
                                                                Math.round(bitmap.height * scale)
                                                            );

                                                            const context = canvas.getContext('2d');

                                                            if (!context) {
                                                                throw new Error('Canvas tidak tersedia.');
                                                            }

                                                            context.drawImage(
                                                                bitmap,
                                                                0,
                                                                0,
                                                                canvas.width,
                                                                canvas.height
                                                            );

                                                            compressedBlob = await new Promise((resolve) => {
                                                                canvas.toBlob(
                                                                    resolve,
                                                                    'image/jpeg',
                                                                    quality
                                                                );
                                                            });

                                                            if (!compressedBlob) {
                                                                throw new Error('Foto tidak dapat dikompres.');
                                                            }

                                                            if (compressedBlob.size <= maxBytes) {
                                                                break;
                                                            }

                                                            if (quality > 0.52) {
                                                                quality -= 0.1;
                                                            } else {
                                                                scale *= 0.75;
                                                            }
                                                        }

                                                        if (
                                                            !compressedBlob ||
                                                            compressedBlob.size > maxBytes
                                                        ) {
                                                            this.uploadError =
                                                                'Foto terlalu besar untuk diproses. Coba gunakan foto lain.';
                                                            event.target.value = '';
                                                            return;
                                                        }

                                                        uploadFile = new File(
                                                            [compressedBlob],
                                                            file.name.replace(/\.[^.]+$/, '') + '.jpg',
                                                            {
                                                                type: 'image/jpeg',
                                                                lastModified: Date.now(),
                                                            }
                                                        );
                                                    }

                                                    this.isPreparingPhoto = false;

                                                    this.$wire.upload(
                                                        'tenantPhoto',
                                                        uploadFile,
                                                        () => {},
                                                        () => {
                                                            this.uploadError =
                                                                'Upload foto gagal. Periksa koneksi lalu coba lagi.';
                                                            event.target.value = '';
                                                        },
                                                    );

                                                } catch {
                                                    this.uploadError =
                                                        'Format foto tidak bisa dibaca. Coba unggah JPG, PNG, atau WebP.';
                                                    event.target.value = '';
                                                } finally {
                                                    this.isPreparingPhoto = false;
                                                    bitmap?.close();
                                                }
                                            },
                                        }"
                                    >

                                        @if($tenantPhoto)
                                            <div class="p-2 rounded-xl bg-white border border-emerald-300 text-center space-y-1.5 shadow-sm">
                                                <img
                                                    src="{{ $tenantPhoto->temporaryUrl() }}"
                                                    alt="Preview Foto Penyewa"
                                                    class="h-24 mx-auto object-cover rounded-lg border border-wheat-200 shadow-inner"
                                                />

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

                                                <span class="text-xs font-bold text-emerald-800">
                                                    Foto penyewa telah tersimpan
                                                </span>
                                            </div>
                                        @endif

                                        <label class="w-full cursor-pointer flex items-center justify-center gap-2 py-2 px-3 rounded-xl text-xs font-bold transition duration-200 shadow-sm {{ $this->isPhotoVerified ? 'bg-white text-emerald-800 border-2 border-emerald-300 hover:bg-emerald-50' : 'bg-avocado-600 hover:bg-avocado-700 text-white' }}">
                                            <i data-lucide="camera" class="w-4 h-4"></i>

                                            <span x-text="isPreparingPhoto ? 'Menyiapkan foto...' : '{{ $this->isPhotoVerified ? 'Ganti Foto Penyewa' : 'Upload / Ambil Foto' }}'"></span>

                                            <input
                                                type="file"
                                                x-on:change="prepareTenantPhoto($event)"
                                                accept="image/*"
                                                class="hidden"
                                                :disabled="isPreparingPhoto"
                                            />
                                        </label>

                                        <p
                                            x-cloak
                                            x-show="uploadError"
                                            x-text="uploadError"
                                            class="text-[10px] text-red-600 font-semibold"
                                        ></p>

                                        @error('tenantPhoto')
                                            <p class="text-[10px] text-red-600 font-semibold">
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Verifikasi KTP Fisik --}}
                                <div class="p-4 rounded-2xl border flex flex-col justify-between space-y-3 {{ $tenantKtpUploaded ? 'bg-emerald-50/60 border-emerald-300 shadow-sm' : 'bg-wheat-50/70 border-wheat-200' }}">

                                    <div>
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-bold text-xs text-darkbrown-900">
                                                KTP Fisik Asli
                                            </span>

                                            @if($tenantKtpUploaded)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-sm">
                                                    <i data-lucide="check" class="w-3 h-3 text-emerald-600"></i>
                                                    Ditahan
                                                </span>
                                            @else
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                    Perlu Dicek
                                                </span>
                                            @endif
                                        </div>

                                        <p class="text-[11px] text-darkbrown-600">
                                            Pastikan KTP asli ditahan sebagai jaminan sampai barang dikembalikan.
                                        </p>
                                    </div>

                                    <button
                                        wire:click="markTenantKtpUploaded"
                                        type="button"
                                        class="w-full py-2 px-3 rounded-xl text-xs font-bold transition shadow-xs flex items-center justify-center gap-1.5 {{ $tenantKtpUploaded ? 'bg-white text-emerald-800 border border-emerald-300 cursor-default' : 'bg-avocado-700 hover:bg-avocado-800 text-white cursor-pointer active:scale-[0.98]' }}"
                                        {{ $tenantKtpUploaded ? 'disabled' : '' }}
                                    >
                                        @if($tenantKtpUploaded)
                                            <i data-lucide="check-check" class="w-4 h-4 text-emerald-600"></i>
                                            <span>KTP Fisik Sudah Ditahan</span>
                                        @else
                                            <i data-lucide="id-card" class="w-4 h-4"></i>
                                            <span>Cocokkan & Tahan KTP</span>
                                        @endif
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Checklist Serah Terima Fisik (Compact 3-Item Grid) --}}
                        <div class="p-4 rounded-xl bg-wheat-50/60 border border-wheat-200 space-y-2.5">
                            <span class="text-xs font-bold uppercase tracking-wider text-darkbrown-700 block">
                                Checklist Konfirmasi Kasir
                            </span>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <label class="p-2.5 rounded-xl border transition flex items-center gap-2.5 cursor-pointer text-xs {{ $checkIdentity ? 'bg-emerald-50 border-emerald-300 text-emerald-950 font-bold' : 'bg-white border-wheat-200 text-darkbrown-800' }}">
                                    <input type="checkbox" wire:model.live="checkIdentity" class="rounded border-wheat-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer" />
                                    <span class="text-[11px] leading-tight">Identitas Sesuai</span>
                                </label>

                                <label class="p-2.5 rounded-xl border transition flex items-center gap-2.5 cursor-pointer text-xs {{ $checkUnits ? 'bg-emerald-50 border-emerald-300 text-emerald-950 font-bold' : 'bg-white border-wheat-200 text-darkbrown-800' }}">
                                    <input type="checkbox" wire:model.live="checkUnits" class="rounded border-wheat-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer" />
                                    <span class="text-[11px] leading-tight">Semua Unit Dicek</span>
                                </label>

                                <label class="p-2.5 rounded-xl border transition flex items-center gap-2.5 cursor-pointer text-xs {{ $checkHandover ? 'bg-emerald-50 border-emerald-300 text-emerald-950 font-bold' : 'bg-white border-wheat-200 text-darkbrown-800' }}">
                                    <input type="checkbox" wire:model.live="checkHandover" class="rounded border-wheat-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer" />
                                    <span class="text-[11px] leading-tight">Barang Diserahkan</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Modal Footer Bar --}}
                    <div class="px-6 py-4 border-t border-wheat-200 bg-wheat-50/70 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                        <div class="text-xs font-semibold">
                            @if(!($this->selectedOrder['is_fully_paid'] ?? true))
                                <span class="text-amber-800 flex items-center gap-1.5">
                                    <i data-lucide="alert-circle" class="w-4 h-4 text-amber-600"></i>
                                    Tagihan belum lunas.
                                </span>
                            @elseif(!$this->isPickupReady)
                                <span class="text-darkbrown-600 flex items-center gap-1.5">
                                    <i data-lucide="info" class="w-4 h-4 text-darkbrown-400"></i>
                                    Lengkapi foto, KTP, dan 3 checklist untuk konfirmasi.
                                </span>
                            @else
                                <span class="text-emerald-800 font-bold flex items-center gap-1.5">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-600"></i>
                                    Semua syarat lengkap!
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                            <button
                                type="button"
                                x-on:click="showPickupModal = false"
                                class="px-4 py-2 rounded-xl text-xs font-bold text-darkbrown-700 hover:bg-wheat-200/60 transition cursor-pointer">
                                Tutup
                            </button>

                            <button
                                type="button"
                                wire:click="confirmPickup"
                                class="px-5 py-2.5 rounded-xl font-bold text-xs transition duration-150 flex items-center gap-2 {{ $this->isPickupReady ? 'bg-avocado-700 hover:bg-avocado-800 text-white cursor-pointer shadow-sm active:scale-[0.98]' : 'bg-wheat-200 text-darkbrown-400 border border-wheat-300 cursor-not-allowed opacity-70' }}"
                                {{ !$this->isPickupReady ? 'disabled' : '' }}>
                                @if(!$this->isPickupReady)
                                    <i data-lucide="lock" class="w-4 h-4 text-darkbrown-400"></i>
                                    <span>Konfirmasi Serah Terima</span>
                                @else
                                    <i data-lucide="check" class="w-4 h-4 text-white"></i>
                                    <span>Konfirmasi Serah Terima</span>
                                @endif
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    @endif
</div>
