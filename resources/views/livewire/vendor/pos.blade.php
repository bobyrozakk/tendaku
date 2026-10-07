<div class="space-y-6" x-data="{ showValidationModal: false }">
    <!-- Header & Top KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Card 1: Pending Validasi -->
        <div class="bg-white p-5 rounded-2xl border border-wheat-200/90 shadow-tendaku-sm flex items-center justify-between relative overflow-hidden">
            <div class="space-y-1">
                <span class="text-xs font-bold text-darkbrown-500 uppercase tracking-wider block">Pending Validasi</span>
                <p class="text-3xl font-extrabold text-sunglow-600 tracking-tight">{{ $kpi['pending'] }}</p>
                <p class="text-[11px] text-darkbrown-500">Pesanan butuh alokasi unit</p>
            </div>
            <div class="p-3 rounded-2xl bg-sunglow-100 text-sunglow-700 border border-sunglow-200 shrink-0">
                <svg class="w-7 h-7 text-sunglow-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- Card 2: Pickup Hari Ini -->
        <div class="bg-white p-5 rounded-2xl border border-wheat-200/90 shadow-tendaku-sm flex items-center justify-between relative overflow-hidden">
            <div class="space-y-1">
                <span class="text-xs font-bold text-darkbrown-500 uppercase tracking-wider block">Pickup Hari Ini</span>
                <p class="text-3xl font-extrabold text-avocado-600 tracking-tight">{{ $kpi['pickup_today'] }}</p>
                <p class="text-[11px] text-darkbrown-500">Pesanan siap diambil</p>
            </div>
            <div class="p-3 rounded-2xl bg-avocado-100 text-avocado-700 border border-avocado-200 shrink-0">
                <svg class="w-7 h-7 text-avocado-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
            </div>
        </div>

        <!-- Card 3: Retur Hari Ini -->
        <div class="bg-white p-5 rounded-2xl border border-wheat-200/90 shadow-tendaku-sm flex items-center justify-between relative overflow-hidden">
            <div class="space-y-1">
                <span class="text-xs font-bold text-darkbrown-500 uppercase tracking-wider block">Retur Hari Ini</span>
                <p class="text-3xl font-extrabold text-goldenrod-600 tracking-tight">{{ $kpi['return_today'] }}</p>
                <p class="text-[11px] text-darkbrown-500">Jatuh tempo pengembalian</p>
            </div>
            <div class="p-3 rounded-2xl bg-goldenrod-100 text-goldenrod-700 border border-goldenrod-200 shrink-0">
                <svg class="w-7 h-7 text-goldenrod-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Full-width Validation Queue -->
    <x-card padding="p-0">
        <!-- Card Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-5 pt-5 pb-4 border-b border-wheat-200">
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-xl bg-wheat-100 text-darkbrown-800 border border-wheat-200">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </span>
                <div>
                    <h2 class="font-extrabold text-base text-darkbrown-900">Antrean Validasi</h2>
                    <p class="text-[11px] text-darkbrown-500">Klik pada pesanan untuk membuka panel validasi</p>
                </div>
            </div>

            <!-- Segmented Filter Tabs -->
            <div class="inline-flex p-1 bg-wheat-100/80 rounded-2xl border border-wheat-200/80">
                <button wire:click="$set('activeTab', 'all')"
                    class="px-4 py-2 text-center text-xs font-bold rounded-xl transition-all duration-200 {{ $activeTab === 'all' ? 'bg-white text-darkbrown-900 shadow-sm border border-wheat-200' : 'text-darkbrown-600 hover:text-darkbrown-900' }}">
                    Semua
                </button>
                <button wire:click="$set('activeTab', 'pending')"
                    class="px-4 py-2 text-center text-xs font-bold rounded-xl transition-all duration-200 flex items-center gap-1.5 {{ $activeTab === 'pending' ? 'bg-white text-darkbrown-900 shadow-sm border border-wheat-200' : 'text-darkbrown-600 hover:text-darkbrown-900' }}">
                    <span>Menunggu</span>
                    <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-sunglow-100 text-darkbrown-900 border border-sunglow-200">{{ $kpi['pending'] }}</span>
                </button>
                <button wire:click="$set('activeTab', 'ready')"
                    class="px-4 py-2 text-center text-xs font-bold rounded-xl transition-all duration-200 {{ $activeTab === 'ready' ? 'bg-white text-darkbrown-900 shadow-sm border border-wheat-200' : 'text-darkbrown-600 hover:text-darkbrown-900' }}">
                    Siap Ambil
                </button>
            </div>
        </div>

        <!-- Order Grid / Table -->
        <div class="p-5">
            @forelse($this->filteredOrders as $order)
                <div
                    wire:click="selectOrder('{{ $order['id'] }}')"
                    x-on:click="showValidationModal = true"
                    class="group flex items-center justify-between p-4 mb-3 border rounded-2xl cursor-pointer transition-all duration-200 hover:border-avocado-400 hover:shadow-tendaku-sm bg-white border-wheat-200/90 hover:bg-wheat-50/50 last:mb-0">

                    <!-- Left: Customer info -->
                    <div class="flex items-center gap-4">
                        <!-- Avatar / Icon -->
                        <div class="p-2.5 rounded-xl bg-wheat-100 border border-wheat-200 text-darkbrown-700 group-hover:bg-avocado-50 group-hover:border-avocado-200 group-hover:text-avocado-700 transition-colors shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-0.5">
                                <h4 class="font-extrabold text-darkbrown-900 text-sm">{{ $order['customer'] }}</h4>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-goldenrod-100 text-goldenrod-900 border border-goldenrod-200">{{ $order['tier'] }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-[11px] text-darkbrown-500">
                                <span class="font-mono font-bold bg-wheat-100 text-darkbrown-700 px-1.5 py-0.5 rounded border border-wheat-200">{{ $order['id'] }}</span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $order['duration'] }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Stats + Status + Action -->
                    <div class="flex items-center gap-4 shrink-0">
                        <div class="text-right hidden sm:block">
                            <p class="text-xs font-bold text-darkbrown-800">{{ collect($order['items'])->sum('qty') }} Unit</p>
                            <p class="text-[10px] text-darkbrown-500">{{ count($order['items']) }} Item</p>
                        </div>
                        @if($order['status'] === 'pending')
                            <x-badge variant="sunglow" size="sm" :dot="true">Pending Validasi</x-badge>
                        @else
                            <x-badge variant="avocado" size="sm" :dot="true">Siap Pickup</x-badge>
                        @endif
                        <!-- Arrow -->
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
                    <p class="text-[11px]">Belum ada transaksi di kategori antrean ini.</p>
                </div>
            @endforelse
        </div>
    </x-card>

    <!-- ==========================================
         VALIDATION DETAIL MODAL (Center Popup Teleported to Body)
         ========================================== -->
    @if($selectedOrder)
        @php
            $totalAllocated = 0;
            $totalScanned = 0;
            foreach($selectedOrder['items'] as $item) {
                $totalAllocated += count($item['allocated_units']);
                $totalScanned += count($item['scanned_units']);
            }
            $allMatched = ($totalAllocated > 0 && $totalAllocated === $totalScanned);
            $progressPercent = $totalAllocated > 0 ? round(($totalScanned / $totalAllocated) * 100) : 0;
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
                                    {{ $selectedOrder['id'] }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-goldenrod-100 text-goldenrod-900 border border-goldenrod-200">
                                    {{ $selectedOrder['tier'] }}
                                </span>
                            </div>
                            <h2 class="text-xl font-extrabold text-darkbrown-900 tracking-tight">Validasi: {{ $selectedOrder['customer'] }}</h2>
                            <p class="text-xs text-darkbrown-600 mt-0.5">Status Pembayaran: <span class="text-avocado-700 font-bold">{{ $selectedOrder['payment_status'] }}</span></p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($selectedOrder['status'] === 'pending')
                                <button
                                    wire:click="approveOrder('{{ $selectedOrder['id'] }}')"
                                    x-on:click="if ($wire.get('allMatched') || {{ $allMatched ? 'true' : 'false' }}) { showValidationModal = false }"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs transition duration-200 shadow-sm {{ $allMatched ? 'bg-avocado-600 hover:bg-avocado-700 text-white cursor-pointer shadow-tendaku-avocado-glow' : 'bg-gray-200 text-gray-400 cursor-not-allowed border border-gray-300' }}"
                                    {{ !$allMatched ? 'disabled' : '' }}>
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Konfirmasi Siap Ambil</span>
                                </button>
                            @else
                                <x-badge variant="avocado" size="lg" :dot="true">Siap Pickup</x-badge>
                            @endif
                            <!-- Close button -->
                            <button x-on:click="showValidationModal = false" class="p-2 rounded-xl text-darkbrown-400 hover:text-darkbrown-700 hover:bg-wheat-100 transition border border-transparent hover:border-wheat-200">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-5">
                        <!-- Allocation Table -->
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <h3 class="font-bold text-sm text-darkbrown-900 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-avocado-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    Alokasi Unit Fisik & Pencocokan
                                </h3>
                                <span class="text-xs font-semibold text-darkbrown-600">Total: {{ $totalAllocated }} Unit</span>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-wheat-200">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-wheat-100/70 text-darkbrown-800 font-bold uppercase tracking-wider">
                                        <tr>
                                            <th class="py-3 px-4">Nama Barang</th>
                                            <th class="py-3 px-4">Jumlah Needed</th>
                                            <th class="py-3 px-4">Unit Dialokasikan</th>
                                            <th class="py-3 px-4 text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-wheat-200 bg-white">
                                        @foreach($selectedOrder['items'] as $item)
                                            <tr class="hover:bg-wheat-50/70 transition-colors">
                                                <td class="py-3.5 px-4 font-bold text-darkbrown-900 text-sm">
                                                    {{ $item['name'] }}
                                                </td>
                                                <td class="py-3.5 px-4 font-semibold text-darkbrown-800">
                                                    {{ $item['qty'] }} unit
                                                </td>
                                                <td class="py-3.5 px-4">
                                                    <div class="flex flex-wrap gap-1.5">
                                                        @foreach($item['allocated_units'] as $barcode)
                                                            @php
                                                                $isScanned = in_array($barcode, $item['scanned_units']);
                                                            @endphp
                                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-mono font-bold border transition-all {{ $isScanned ? 'bg-avocado-100 text-avocado-900 border-avocado-300 shadow-sm' : 'bg-wheat-100 text-darkbrown-700 border-wheat-200' }}">
                                                                {{ $barcode }}
                                                                @if($isScanned)
                                                                    <svg class="w-3.5 h-3.5 text-avocado-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                                    </svg>
                                                                @endif
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </td>
                                                <td class="py-3.5 px-4 text-center">
                                                    <x-badge variant="sunglow" size="sm">
                                                        {{ $item['status'] }}
                                                    </x-badge>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Barcode Matching Meter -->
                        @if($selectedOrder['status'] === 'pending')
                        <div class="p-5 rounded-2xl border transition-all {{ $allMatched ? 'border-avocado-300 bg-avocado-50/80' : 'border-wheat-300 bg-wheat-50/80' }}">
                            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                                <div class="space-y-2 w-full sm:w-2/3">
                                    <div class="flex justify-between items-center">
                                        <h3 class="font-extrabold text-sm text-darkbrown-900">Progress Pencocokan Barcode</h3>
                                        <span class="text-xs font-bold text-darkbrown-700">{{ $totalScanned }}/{{ $totalAllocated }} Unit ({{ $progressPercent }}%)</span>
                                    </div>
                                    <!-- Visual Progress Bar -->
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
                                            <span>Semua Unit Cocok!</span>
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

    <!-- Modern Scan Barcode Modal (Teleported to Body) -->
    @if($showScanModal)
    <template x-teleport="body">
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-darkbrown-950/60 p-4 animate-fade-in">
            <x-card class="w-full max-w-sm shadow-tendaku-lg border-wheat-300 relative bg-white overflow-hidden">
                <div class="flex justify-between items-center border-b border-wheat-200 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-xl bg-avocado-100 text-avocado-700">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                        </span>
                        <h3 class="text-base font-extrabold text-darkbrown-900">Input Barcode Manual</h3>
                    </div>
                    <button wire:click="$set('showScanModal', false)" class="text-darkbrown-400 hover:text-darkbrown-700 p-1.5 rounded-lg hover:bg-wheat-100 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="processScan" class="space-y-4">
                    <div>
                        <x-input-label value="Scan / Masukkan Kode Barcode" class="font-bold text-xs text-darkbrown-800" />
                        <x-text-input wire:model="modalBarcode" class="w-full mt-1.5 font-mono uppercase text-xs tracking-wider" placeholder="Contoh: A-001" autofocus />
                    </div>

                    @if($scanError)
                        <div class="text-xs text-red-700 font-semibold bg-red-50 p-3 rounded-xl border border-red-200 flex items-center gap-2">
                            <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $scanError }}</span>
                        </div>
                    @endif

                    @if($scanSuccessMessage)
                        <div class="text-xs text-avocado-800 font-semibold bg-avocado-50 p-3 rounded-xl border border-avocado-200 flex items-center gap-2">
                            <svg class="w-4 h-4 text-avocado-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>{{ $scanSuccessMessage }}</span>
                        </div>
                    @endif

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl font-bold text-xs text-white bg-avocado-600 hover:bg-avocado-700 active:bg-avocado-800 shadow-sm transition">
                            Verifikasi Barcode
                        </button>
                    </div>
                </form>
            </x-card>
        </div>
    </template>
    @endif
</div>
