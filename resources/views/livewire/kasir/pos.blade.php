<div class="space-y-6" x-data="{ showValidationModal: false }">
    @if(session()->has('pos_message'))
        <div class="p-4 rounded-2xl bg-avocado-50 border border-avocado-300 text-avocado-900 flex items-center justify-between shadow-sm animate-fade-in">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-avocado-600 text-white flex items-center justify-center font-bold">
                    ✓
                </div>
                <p class="text-xs font-bold">{{ session('pos_message') }}</p>
            </div>
        </div>
    @endif

    <!-- Header & Top KPI Cards: Hanya Pending & Completed -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Card 1: Pending Validasi -->
        <div class="bg-white p-5 rounded-2xl border border-wheat-200/90 shadow-tendaku-sm flex items-center justify-between relative overflow-hidden">
            <div class="space-y-1">
                <span class="text-xs font-bold text-darkbrown-500 uppercase tracking-wider block">Pending Validasi</span>
                <p class="text-3xl font-extrabold text-sunglow-600 tracking-tight">{{ $kpi['pending'] }}</p>
                <p class="text-[11px] text-darkbrown-500">Pesanan butuh scan & pencocokan barcode</p>
            </div>
            <div class="p-3.5 rounded-2xl bg-sunglow-100 text-sunglow-700 border border-sunglow-200 shrink-0">
                <svg class="w-7 h-7 text-sunglow-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- Card 2: Completed / Tervalidasi -->
        <div class="bg-white p-5 rounded-2xl border border-wheat-200/90 shadow-tendaku-sm flex items-center justify-between relative overflow-hidden">
            <div class="space-y-1">
                <span class="text-xs font-bold text-darkbrown-500 uppercase tracking-wider block">Completed (Disetujui)</span>
                <p class="text-3xl font-extrabold text-avocado-600 tracking-tight">{{ $kpi['completed'] }}</p>
                <p class="text-[11px] text-darkbrown-500">Pesanan telah divalidasi & siap disewa</p>
            </div>
            <div class="p-3.5 rounded-2xl bg-avocado-100 text-avocado-700 border border-avocado-200 shrink-0">
                <svg class="w-7 h-7 text-avocado-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Full-width Validation Queue -->
    <x-card padding="p-0">
        <!-- Card Header & Navigation Tabs (Hanya Pending & Completed) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-5 pt-5 pb-4 border-b border-wheat-200">
            <div class="flex items-center gap-3">
                <span class="p-2.5 rounded-xl bg-wheat-100 text-darkbrown-800 border border-wheat-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </span>
                <div>
                    <h2 class="font-extrabold text-base text-darkbrown-900">Validasi & Pencocokan Barcode</h2>
                    <p class="text-[11px] text-darkbrown-500">Scan barcode unit fisik toko sebelum pesanan disetujui untuk sewa</p>
                </div>
            </div>

            <!-- Search Bar via kode_booking -->
            <div class="w-full sm:w-64 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-darkbrown-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <x-text-input 
                    wire:model.live.debounce.250ms="searchOrder" 
                    placeholder="Cari Kode Booking / Nama..." 
                    class="w-full text-xs pl-9 py-2 bg-wheat-50/50 border-wheat-300 rounded-xl focus:bg-white"
                />
            </div>

            <!-- Segmented Filter Tabs: Pending & Completed -->
            <div class="inline-flex p-1 bg-wheat-100/80 rounded-2xl border border-wheat-200/80">
                <button wire:click="$set('activeTab', 'pending')"
                    class="px-4 py-2 text-center text-xs font-bold rounded-xl transition-all duration-200 flex items-center gap-1.5 cursor-pointer {{ $activeTab === 'pending' ? 'bg-white text-darkbrown-900 shadow-sm border border-wheat-200' : 'text-darkbrown-600 hover:text-darkbrown-900' }}">
                    <span>Pending</span>
                    <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-sunglow-100 text-darkbrown-900 border border-sunglow-200">{{ $kpi['pending'] }}</span>
                </button>
                <button wire:click="$set('activeTab', 'completed')"
                    class="px-4 py-2 text-center text-xs font-bold rounded-xl transition-all duration-200 flex items-center gap-1.5 cursor-pointer {{ $activeTab === 'completed' ? 'bg-white text-darkbrown-900 shadow-sm border border-wheat-200' : 'text-darkbrown-600 hover:text-darkbrown-900' }}">
                    <span>Completed</span>
                    <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-avocado-100 text-avocado-800 border border-avocado-200">{{ $kpi['completed'] }}</span>
                </button>
            </div>
        </div>

        <!-- Order Grid / Table from DB -->
        <div class="p-5">
            @forelse($orders as $order)
                @php
                    $cust = $order->customer?->name ?? $order->guest_name;
                    $period = $order->pickup_date->format('d M') . ' - ' . $order->return_date->format('d M Y') . ' (' . $order->rental_days . ' Hari)';
                    $isPending = $order->status === 'pending';
                @endphp
                <div
                    wire:click="selectOrder({{ $order->id }})"
                    x-on:click="showValidationModal = true"
                    class="group flex items-center justify-between p-4 mb-3 border rounded-2xl cursor-pointer transition-all duration-200 hover:border-avocado-400 hover:shadow-tendaku-sm bg-white border-wheat-200/90 hover:bg-wheat-50/50 last:mb-0">

                    <!-- Left: Customer info -->
                    <div class="flex items-center gap-4">
                        <div class="p-2.5 rounded-xl bg-wheat-100 border border-wheat-200 text-darkbrown-700 group-hover:bg-avocado-50 group-hover:border-avocado-200 group-hover:text-avocado-700 transition-colors shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-0.5">
                                <h4 class="font-extrabold text-darkbrown-900 text-sm">{{ $cust }}</h4>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-wheat-100 text-darkbrown-700 border border-wheat-200">Customer</span>
                            </div>
                            <div class="flex items-center gap-3 text-[11px] text-darkbrown-500">
                                <span class="font-mono font-bold bg-wheat-100 text-darkbrown-700 px-1.5 py-0.5 rounded border border-wheat-200">{{ $order->booking_code }}</span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $period }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Stats + Status + Action -->
                    <div class="flex items-center gap-4 shrink-0">
                        <div class="text-right hidden sm:block">
                            <p class="text-xs font-bold text-darkbrown-800">{{ $order->details->count() }} Item</p>
                            <p class="text-[10px] text-darkbrown-500">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</p>
                        </div>
                        @if($isPending)
                            <x-badge variant="sunglow" size="sm" :dot="true">Pending Validasi</x-badge>
                        @else
                            <x-badge variant="avocado" size="sm" :dot="true">Completed (Disetujui)</x-badge>
                        @endif
                        <div class="text-wheat-400 group-hover:text-avocado-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16 text-xs text-darkbrown-500 space-y-2">
                    <svg class="w-12 h-12 mx-auto text-wheat-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p class="font-bold text-darkbrown-700">Tidak ada pesanan</p>
                    <p class="text-[11px]">Belum ada data transaksi di kategori ini atau sesuai pencarian.</p>
                </div>
            @endforelse
        </div>
    </x-card>

    <!-- VALIDATION DETAIL MODAL -->
    @if($this->selectedOrder)
        @php
            $custModal = $this->selectedOrder->customer?->name ?? $this->selectedOrder->guest_name;
            $totalItems = $this->selectedOrder->details->count();
            // Item terhitung matched jika item_unit_id terpasang dan sudah diverifikasi/discan
            $orderScannedMap = $scannedUnits[$this->selectedOrder->id] ?? [];
            $assignedCount = 0;
            foreach ($this->selectedOrder->details as $d) {
                if (!empty($d->item_unit_id) && !empty($orderScannedMap[$d->id])) {
                    $assignedCount++;
                }
            }
            $allMatched = ($totalItems > 0 && $assignedCount >= $totalItems);
            $progressPercent = $totalItems > 0 ? round(($assignedCount / $totalItems) * 100) : 0;
            $isOrderPending = $this->selectedOrder->status === 'pending';
        @endphp

        <template x-teleport="body">
            <div
                x-show="showValidationModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-darkbrown-950/60 p-4"
                x-on:click.self="showValidationModal = false"
                style="display: none;">

                <div
                    x-show="showValidationModal"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="bg-white rounded-2xl border border-wheat-200 shadow-tendaku-lg w-full max-w-3xl max-h-[90vh] overflow-y-auto">

                    <!-- Modal Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-6 pt-5 pb-4 border-b border-wheat-200 sticky top-0 bg-white z-10 rounded-t-2xl">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-wheat-100 text-darkbrown-900 border border-wheat-200">
                                    {{ $this->selectedOrder->booking_code }}
                                </span>
                                @if($isOrderPending)
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-sunglow-100 text-darkbrown-900 border border-sunglow-200">
                                        Pending Validasi
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">
                                        Completed (Disetujui)
                                    </span>
                                @endif
                            </div>
                            <h2 class="text-xl font-extrabold text-darkbrown-900 tracking-tight">Validasi: {{ $custModal }}</h2>
                            <p class="text-xs text-darkbrown-600 mt-0.5">Total Bayar: <span class="text-avocado-700 font-bold">Rp {{ number_format($this->selectedOrder->grand_total, 0, ',', '.') }}</span></p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($isOrderPending)
                                <button
                                    wire:click="approveOrder({{ $this->selectedOrder->id }})"
                                    x-on:click="if ({{ $allMatched ? 'true' : 'false' }}) { showValidationModal = false }"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-xs transition duration-200 shadow-sm {{ $allMatched ? 'bg-avocado-600 hover:bg-avocado-700 text-white cursor-pointer shadow-tendaku-avocado-glow' : 'bg-gray-200 text-gray-400 cursor-not-allowed border border-gray-300' }}"
                                    {{ !$allMatched ? 'disabled' : '' }}>
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Setujui Pesanan (Completed)</span>
                                </button>
                            @else
                                <x-badge variant="avocado" size="lg" :dot="true">Pesanan Disetujui</x-badge>
                            @endif
                            <button x-on:click="showValidationModal = false" class="p-2 rounded-xl text-darkbrown-400 hover:text-darkbrown-700 hover:bg-wheat-100 transition border border-transparent hover:border-wheat-200 cursor-pointer">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-5">
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <h3 class="font-bold text-sm text-darkbrown-900 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-avocado-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    Item Pesanan & Pencocokan Barcode Unit
                                </h3>
                                <span class="text-xs font-semibold text-darkbrown-600">Total: {{ $totalItems }} Item</span>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-wheat-200">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-wheat-100/70 text-darkbrown-800 font-bold uppercase tracking-wider">
                                        <tr>
                                            <th class="py-3 px-4">Nama Barang</th>
                                            <th class="py-3 px-4">Tarif Harian</th>
                                            <th class="py-3 px-4">Unit / Barcode Terpasang</th>
                                            <th class="py-3 px-4 text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-wheat-200 bg-white">
                                        @foreach($this->selectedOrder->details as $item)
                                            @php
                                                $unit = $item->itemUnit;
                                                $isItemScanned = !empty($orderScannedMap[$item->id]);
                                            @endphp
                                            <tr class="hover:bg-wheat-50/70 transition-colors">
                                                <td class="py-3.5 px-4 font-bold text-darkbrown-900 text-sm">
                                                    {{ $item->masterItem?->name }}
                                                </td>
                                                <td class="py-3.5 px-4 font-semibold text-darkbrown-800">
                                                    Rp {{ number_format($item->daily_rate_snapshot, 0, ',', '.') }} / hari
                                                </td>
                                                <td class="py-3.5 px-4">
                                                    @if($unit)
                                                        @if($isItemScanned)
                                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-avocado-100 text-avocado-900 border border-avocado-300 shadow-sm">
                                                                <span>{{ $unit->unit_code }}</span>
                                                                <span class="bg-white/90 px-1.5 py-0.5 rounded text-avocado-800 border border-avocado-200">{{ $unit->barcode }}</span>
                                                                <svg class="w-3.5 h-3.5 text-avocado-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                            </span>
                                                        @else
                                                            <div class="inline-flex items-center gap-2">
                                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-wheat-100 text-darkbrown-800 border border-wheat-300">
                                                                    <span>{{ $unit->unit_code }}</span>
                                                                    <span class="bg-white px-1.5 py-0.5 rounded text-darkbrown-700 border border-wheat-300">{{ $unit->barcode }}</span>
                                                                </span>
                                                                <span class="text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200 font-semibold text-[10px]">
                                                                    Belum Cocok Fisik
                                                                </span>
                                                            </div>
                                                        @endif
                                                    @else
                                                        <span class="text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200 font-semibold text-[11px] inline-flex items-center gap-1">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                            Belum dialokasikan unit
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="py-3.5 px-4 text-center">
                                                    @if($isItemScanned)
                                                        <x-badge variant="avocado" size="sm">✓ Cocok</x-badge>
                                                    @else
                                                        <x-badge variant="sunglow" size="sm">Menunggu Scan</x-badge>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Barcode Matching Meter -->
                        @if($isOrderPending)
                            <div class="p-5 rounded-2xl border transition-all {{ $allMatched ? 'border-avocado-300 bg-avocado-50/80' : 'border-wheat-300 bg-wheat-50/80' }}">
                                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                                    <div class="space-y-2 w-full sm:w-2/3">
                                        <div class="flex justify-between items-center">
                                            <h3 class="font-extrabold text-sm text-darkbrown-900">Progress Pencocokan Barcode</h3>
                                            <span class="text-xs font-bold text-darkbrown-700">{{ $assignedCount }}/{{ $totalItems }} Item ({{ $progressPercent }}%)</span>
                                        </div>
                                        <div class="w-full h-3 bg-wheat-200/80 rounded-full overflow-hidden p-0.5 border border-wheat-300/60">
                                            <div class="h-full bg-avocado-600 rounded-full transition-all duration-300" style="width: {{ $progressPercent }}%"></div>
                                        </div>
                                    </div>

                                    <div class="shrink-0">
                                        @if(!$allMatched)
                                            <button wire:click="openScanModal" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-xs text-white bg-avocado-600 hover:bg-avocado-700 active:bg-avocado-800 shadow-sm transition duration-200 cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                                </svg>
                                                <span>Scan / Input Barcode</span>
                                            </button>
                                        @else
                                            <div class="text-avocado-700 font-extrabold text-xs flex items-center gap-1.5 bg-white px-4 py-2.5 rounded-xl border border-avocado-300 shadow-sm">
                                                <svg class="w-4 h-4 text-avocado-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>Semua Unit Cocok! Siap Disetujui</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </template>
    @endif

    <!-- Modern Scan Barcode Modal (Teleported to Body) DENGAN DAFTAR BARCODE UNTUK DICOCOKKAN -->
    @if($showScanModal)
    <template x-teleport="body">
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-darkbrown-950/60 p-4 animate-fade-in">
            <x-card class="w-full max-w-lg shadow-tendaku-lg border-wheat-300 relative bg-white overflow-hidden">
                <div class="flex justify-between items-center border-b border-wheat-200 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-xl bg-avocado-100 text-avocado-700">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-base font-extrabold text-darkbrown-900">Scan & Cocokkan Barcode</h3>
                            <p class="text-[11px] text-darkbrown-500">Scan unit fisik atau klik barcode unit di bawah untuk mencocokkan</p>
                        </div>
                    </div>
                    <button wire:click="$set('showScanModal', false)" class="text-darkbrown-400 hover:text-darkbrown-700 p-1.5 rounded-lg hover:bg-wheat-100 transition cursor-pointer">
                        ✕
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- Form Input Barcode Scanner -->
                    <form wire:submit.prevent="processScan" class="space-y-3">
                        <div>
                            <x-input-label value="Input / Gunakan Scanner Barcode" class="font-bold text-xs text-darkbrown-800" />
                            <div class="flex gap-2 mt-1.5">
                                <x-text-input wire:model="modalBarcode" class="flex-1 font-mono uppercase text-xs tracking-wider" placeholder="Scan barcode disini (contoh: BC-100002 atau TEND-002)..." autofocus />
                                <button type="submit" class="px-4 py-2 rounded-xl font-bold text-xs text-white bg-avocado-600 hover:bg-avocado-700 active:bg-avocado-800 shadow-sm transition shrink-0 cursor-pointer">
                                    Cocokkan
                                </button>
                            </div>
                        </div>

                        @if($scanError)
                            <div class="text-xs text-red-700 font-semibold bg-red-50 p-3 rounded-xl border border-red-200 flex items-center gap-2">
                                <span>{{ $scanError }}</span>
                            </div>
                        @endif

                        @if($scanSuccessMessage)
                            <div class="text-xs text-avocado-800 font-semibold bg-avocado-50 p-3 rounded-xl border border-avocado-200 flex items-center gap-2">
                                <span>{{ $scanSuccessMessage }}</span>
                            </div>
                        @endif
                    </form>

                    <!-- DAFTAR UNIT & BARCODE YANG HARUS DICOCOKKAN DARI PESANAN INI -->
                    <div class="pt-2 border-t border-wheat-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-darkbrown-800 uppercase tracking-wider block">
                                Unit Yang Perlu Dicocokkan Pada Booking Ini:
                            </span>
                            <span class="text-[10px] text-darkbrown-500 font-semibold">Klik barcode untuk langsung verifikasi</span>
                        </div>

                        <div class="max-h-56 overflow-y-auto space-y-1.5 pr-1">
                            @php
                                $currentOrderDetails = $this->selectedOrder ? $this->selectedOrder->details : collect();
                                $currentScanned = $scannedUnits[$this->selectedOrder->id ?? 0] ?? [];
                            @endphp
                            @forelse($currentOrderDetails as $dItem)
                                @php
                                    $allocatedUnit = $dItem->itemUnit;
                                    $isDone = !empty($currentScanned[$dItem->id]);
                                @endphp
                                @if($allocatedUnit)
                                    <div wire:click="quickFillBarcode('{{ $allocatedUnit->barcode }}')"
                                         class="p-2.5 rounded-xl border transition-all flex items-center justify-between cursor-pointer group {{ $isDone ? 'bg-avocado-50/70 border-avocado-300' : 'bg-wheat-50/70 border-wheat-200 hover:bg-avocado-50 hover:border-avocado-300' }}">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <p class="font-bold text-xs {{ $isDone ? 'text-avocado-900' : 'text-darkbrown-900 group-hover:text-avocado-800' }} truncate">
                                                    {{ $dItem->masterItem?->name }}
                                                </p>
                                                @if($isDone)
                                                    <span class="text-[10px] font-bold text-avocado-700 bg-avocado-100 px-1.5 py-0.2 rounded">✓ Sudah Cocok</span>
                                                @endif
                                            </div>
                                            <p class="text-[10px] text-darkbrown-500 font-mono">
                                                Unit: {{ $allocatedUnit->unit_code }} • Kondisi: {{ $allocatedUnit->condition }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <span class="px-2.5 py-1 rounded-lg font-mono font-bold text-xs bg-white border {{ $isDone ? 'border-avocado-300 text-avocado-900' : 'border-wheat-300 text-darkbrown-900 group-hover:border-avocado-400 group-hover:text-avocado-900' }}">
                                                {{ $allocatedUnit->barcode }}
                                            </span>
                                            @if(!$isDone)
                                                <span class="text-[10px] font-bold text-avocado-700 bg-avocado-100 px-2 py-0.5 rounded-md opacity-0 group-hover:opacity-100 transition-opacity">
                                                    Scan Ini ↗
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="p-2.5 rounded-xl border border-amber-200 bg-amber-50/50 flex items-center justify-between">
                                        <p class="text-xs text-amber-800 font-medium">{{ $dItem->masterItem?->name }} (Belum ada unit teralokasi)</p>
                                    </div>
                                @endif
                            @empty
                                <div class="p-4 text-center text-xs text-darkbrown-500 bg-wheat-50 rounded-xl">
                                    Tidak ada item pada pesanan ini.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </x-card>
        </div>
    </template>
    @endif
</div>
