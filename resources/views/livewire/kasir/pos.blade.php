<div class="space-y-6" x-data="{ showValidationModal: false }">
    @if(session()->has('pos_message'))
        <div class="p-4 rounded-2xl bg-avocado-50 border border-avocado-300 text-avocado-900 flex items-center justify-between shadow-sm animate-fade-in">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-avocado-600 text-white flex items-center justify-center font-bold">
                    <i data-lucide="check" class="w-4 h-4 text-white"></i>
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
                <i data-lucide="clock" class="w-7 h-7 text-sunglow-700"></i>
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
                <i data-lucide="circle-check" class="w-7 h-7 text-avocado-700"></i>
            </div>
        </div>
    </div>

    <!-- Full-width Validation Queue -->
    <x-card padding="p-0">
        <!-- Card Header & Navigation Tabs (Hanya Pending & Completed) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-5 pt-5 pb-4 border-b border-wheat-200">
            <div class="flex items-center gap-3">
                <span class="p-2.5 rounded-xl bg-wheat-100 text-darkbrown-800 border border-wheat-200">
                    <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                </span>
                <div>
                    <h2 class="font-extrabold text-base text-darkbrown-900">Validasi & Pencocokan Barcode</h2>
                    <p class="text-[11px] text-darkbrown-500">Scan barcode unit fisik toko sebelum pesanan disetujui untuk sewa</p>
                </div>
            </div>

            <!-- Search Bar via kode_booking -->
            <div class="w-full sm:w-64 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-darkbrown-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
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
                            <i data-lucide="user" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-0.5">
                                <h4 class="font-extrabold text-darkbrown-900 text-sm">{{ $cust }}</h4>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-wheat-100 text-darkbrown-700 border border-wheat-200">Customer</span>
                            </div>
                            <div class="flex items-center gap-3 text-[11px] text-darkbrown-500">
                                <span class="font-mono font-bold bg-wheat-100 text-darkbrown-700 px-1.5 py-0.5 rounded border border-wheat-200">{{ $order->booking_code }}</span>
                                <span class="flex items-center gap-1">
                                    <i data-lucide="clock" class="w-3 h-3 text-darkbrown-400"></i>
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
                            <i data-lucide="chevron-right" class="w-4 h-4"></i>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16 text-xs text-darkbrown-500 space-y-2">
                    <i data-lucide="inbox" class="w-12 h-12 mx-auto text-wheat-400"></i>
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
                    class="bg-white rounded-3xl border border-wheat-200/90 shadow-2xl shadow-darkbrown-950/20 w-full max-w-3xl max-h-[90vh] overflow-y-auto relative">

                    <!-- Dedicated Close (X) Button di Pojok Kanan Atas Modal (Terpisah dari Aksi) -->
                    <button 
                        type="button"
                        x-on:click="showValidationModal = false" 
                        class="absolute top-4 right-4 z-20 p-2.5 rounded-full text-darkbrown-400 hover:text-darkbrown-800 bg-wheat-100/60 hover:bg-wheat-200/80 transition-all duration-150 cursor-pointer shadow-sm group focus:outline-none focus:ring-2 focus:ring-wheat-400"
                        title="Tutup (Esc)">
                        <i data-lucide="x" class="w-4 h-4 transition-transform group-hover:rotate-90 duration-200"></i>
                    </button>

                    <!-- Modal Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-6 pt-6 pb-4 border-b border-wheat-200/80 sticky top-0 bg-white/95 backdrop-blur-md z-10 rounded-t-3xl pr-16 sm:pr-16">
                        <div>
                            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-wheat-100 text-darkbrown-900 border border-wheat-200 shadow-inner">
                                    {{ $this->selectedOrder->booking_code }}
                                </span>
                                @if($isOrderPending)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-300 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        Pending Validasi
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-300 shadow-sm">
                                        <i data-lucide="circle-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                        Completed (Disetujui)
                                    </span>
                                @endif
                            </div>
                            <h2 class="text-xl font-black text-darkbrown-900 tracking-tight">Validasi: {{ $custModal }}</h2>
                            <p class="text-xs text-darkbrown-600 mt-0.5 flex items-center gap-1.5">
                                <span>Total Tagihan:</span>
                                <span class="text-avocado-800 font-extrabold text-sm px-2 py-0.5 rounded-md bg-avocado-50 border border-avocado-200/80">Rp {{ number_format($this->selectedOrder->grand_total, 0, ',', '.') }}</span>
                            </p>
                        </div>
                        
                        <!-- Action Button Area -->
                        <div class="flex items-center gap-2">
                            @if($isOrderPending)
                                <div class="relative group/actionBtn">
                                    <button
                                        type="button"
                                        wire:click="approveOrder({{ $this->selectedOrder->id }})"
                                        x-on:click="if ({{ $allMatched ? 'true' : 'false' }}) { showValidationModal = false }"
                                        class="inline-flex items-center gap-2.5 px-5 py-2.5 rounded-xl font-extrabold text-xs transition-all duration-200 select-none {{ $allMatched ? 'bg-gradient-to-r from-emerald-600 to-avocado-600 hover:from-emerald-500 hover:to-avocado-500 text-white cursor-pointer shadow-lg shadow-emerald-600/30 ring-2 ring-emerald-400/50 hover:scale-[1.02] active:scale-[0.98]' : 'bg-wheat-100 text-darkbrown-400 border border-wheat-300/80 cursor-not-allowed opacity-80' }}"
                                        {{ !$allMatched ? 'disabled' : '' }}>
                                        @if(!$allMatched)
                                            <!-- Gembok Ikon Saat Disabled -->
                                            <i data-lucide="lock" class="w-4 h-4 text-amber-600 shrink-0"></i>
                                            <span>Setujui Pesanan</span>
                                        @else
                                            <!-- Ikon Checklist Saat Aktif Siap Disetujui -->
                                            <span class="p-1 rounded-md bg-white/20">
                                                <i data-lucide="check" class="w-3.5 h-3.5 text-white"></i>
                                            </span>
                                            <span>Setujui Pesanan (Completed)</span>
                                        @endif
                                    </button>

                                    <!-- Tooltip saat disabled -->
                                    @if(!$allMatched)
                                        <div class="pointer-events-none absolute right-0 -bottom-9 whitespace-nowrap z-30 opacity-0 group-hover/actionBtn:opacity-100 transition-all duration-150 transform translate-y-1 group-hover/actionBtn:translate-y-0">
                                            <span class="bg-darkbrown-900 text-white text-[10px] font-bold px-2.5 py-1.5 rounded-lg shadow-md border border-darkbrown-700 flex items-center gap-1.5">
                                                <i data-lucide="alert-triangle" class="w-3 h-3 text-amber-400"></i>
                                                Scan semua item terlebih dahulu untuk menyetujui
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-sm">
                                    <i data-lucide="check-check" class="w-4 h-4 text-emerald-600"></i>
                                    Pesanan Disetujui
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-5">
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <h3 class="font-bold text-sm text-darkbrown-900 flex items-center gap-2">
                                    <i data-lucide="box" class="w-4 h-4 text-avocado-600"></i>
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
                                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-emerald-50 text-emerald-900 border border-emerald-300 shadow-sm">
                                                                <span>{{ $unit->unit_code }}</span>
                                                                <span class="bg-white/95 px-1.5 py-0.5 rounded text-emerald-800 border border-emerald-200 shadow-inner">{{ $unit->barcode }}</span>
                                                                <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                                            </span>
                                                        @else
                                                            <div class="inline-flex items-center gap-2">
                                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-amber-50/70 text-amber-900 border border-dashed border-amber-300">
                                                                    <span>{{ $unit->unit_code }}</span>
                                                                    <span class="bg-white px-1.5 py-0.5 rounded text-darkbrown-800 border border-wheat-300">{{ $unit->barcode }}</span>
                                                                </span>
                                                            </div>
                                                        @endif
                                                    @else
                                                        <span class="text-rose-700 bg-rose-50 px-2.5 py-1 rounded-lg border border-rose-200 font-semibold text-[11px] inline-flex items-center gap-1.5">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                                            Belum dialokasikan unit
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="py-3.5 px-4 text-center">
                                                    @if($isItemScanned)
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-sm">
                                                            <i data-lucide="check" class="w-3 h-3 text-emerald-600"></i>
                                                            Cocok
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-amber-500/15 text-amber-900 border border-amber-400 shadow-sm animate-pulse">
                                                            <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-600 shrink-0"></i>
                                                            Menunggu Scan
                                                        </span>
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
                                                <i data-lucide="scan-barcode" class="w-4 h-4"></i>
                                                <span>Scan / Input Barcode</span>
                                            </button>
                                        @else
                                            <div class="text-avocado-700 font-extrabold text-xs flex items-center gap-1.5 bg-white px-4 py-2.5 rounded-xl border border-avocado-300 shadow-sm">
                                                <i data-lucide="check-check" class="w-4 h-4 text-avocado-600"></i>
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
        <div class="fixed inset-0 z-[110] flex items-center justify-center bg-darkbrown-950/65 backdrop-blur-sm p-4 animate-fade-in"
             x-on:click.self="$wire.set('showScanModal', false)">
            <div class="w-full max-w-lg bg-white rounded-3xl border border-wheat-200/90 shadow-2xl shadow-darkbrown-950/25 relative overflow-hidden">
                <!-- Dedicated Close (X) Button di Pojok Kanan Atas Modal (Terpisah dari Aksi Utama) -->
                <button 
                    type="button"
                    wire:click="$set('showScanModal', false)" 
                    class="absolute top-4 right-4 z-20 p-2.5 rounded-full text-darkbrown-400 hover:text-darkbrown-800 bg-wheat-100/70 hover:bg-wheat-200/90 transition-all duration-150 cursor-pointer shadow-sm group focus:outline-none focus:ring-2 focus:ring-wheat-400"
                    title="Tutup (Esc)">
                    <i data-lucide="x" class="w-4 h-4 transition-transform group-hover:rotate-90 duration-200"></i>
                </button>

                <!-- Modal Header -->
                <div class="flex items-center gap-3 px-6 pt-6 pb-4 border-b border-wheat-200/80 pr-16 bg-white">
                    <span class="p-2.5 rounded-2xl bg-gradient-to-br from-avocado-100 to-avocado-200/60 text-avocado-800 border border-avocado-300/80 shadow-sm shrink-0">
                        <i data-lucide="scan-barcode" class="w-5 h-5"></i>
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-base font-black text-darkbrown-900 tracking-tight leading-tight">Scan & Cocokkan Barcode</h3>
                        <p class="text-xs text-darkbrown-500 mt-0.5 truncate">Scan barcode atau klik unit di bawah untuk verifikasi</p>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Form Input Barcode Scanner -->
                    <form wire:submit.prevent="processScan" class="space-y-2.5">
                        <div>
                            <x-input-label value="Input / Gunakan Scanner Barcode" class="font-bold text-xs text-darkbrown-800" />
                            <div class="flex gap-2 mt-1.5">
                                <div class="relative flex-1">
                                    <input 
                                        type="text"
                                        wire:model="modalBarcode" 
                                        class="w-full font-mono uppercase text-xs tracking-wider rounded-xl transition duration-150 py-2.5 px-3.5 bg-wheat-50/40 focus:bg-white focus:outline-none focus:ring-2 {{ $scanError ? 'border-rose-500 text-rose-900 focus:ring-rose-400 focus:border-rose-500 bg-rose-50/30' : 'border-wheat-300 text-darkbrown-900 focus:ring-avocado-500 focus:border-avocado-500' }}" 
                                        placeholder="Scan barcode atau ketik kode unit..." 
                                        autofocus 
                                    />
                                    @if($scanError)
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-rose-500">
                                            <i data-lucide="alert-circle" class="w-4 h-4"></i>
                                        </div>
                                    @endif
                                </div>
                                <button type="submit" class="px-5 py-2.5 rounded-xl font-extrabold text-xs text-white bg-gradient-to-r from-emerald-600 to-avocado-600 hover:from-emerald-500 hover:to-avocado-500 active:scale-95 shadow-md shadow-avocado-600/25 transition shrink-0 cursor-pointer">
                                    Cocokkan
                                </button>
                            </div>
                        </div>

                        <!-- Error Feedback Box -->
                        @if($scanError)
                            <div class="text-xs text-rose-800 font-bold bg-rose-50/90 p-3 rounded-xl border border-rose-200/90 flex items-center gap-2.5 shadow-sm animate-fade-in">
                                <div class="w-5 h-5 rounded-full bg-rose-200 text-rose-800 flex items-center justify-center font-bold text-[11px] shrink-0">
                                    <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="leading-tight">{{ $scanError }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- Success Feedback Box -->
                        @if($scanSuccessMessage)
                            <div class="text-xs text-emerald-900 font-bold bg-emerald-50/90 p-3 rounded-xl border border-emerald-200/90 flex items-center gap-2.5 shadow-sm animate-fade-in">
                                <div class="w-5 h-5 rounded-full bg-emerald-200 text-emerald-800 flex items-center justify-center font-bold text-[11px] shrink-0">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="leading-tight">{{ $scanSuccessMessage }}</p>
                                </div>
                            </div>
                        @endif
                    </form>

                    <!-- DAFTAR UNIT & BARCODE YANG HARUS DICOCOKKAN DARI PESANAN INI -->
                    <div class="pt-3 border-t border-wheat-200/80 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-darkbrown-800 uppercase tracking-wider block">
                                Unit Yang Perlu Dicocokkan:
                            </span>
                            <span class="text-[10px] text-darkbrown-500 font-medium">Klik badge untuk cocokan</span>
                        </div>

                        <div class="max-h-56 overflow-y-auto space-y-2 pr-1">
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
                                         class="p-3 rounded-2xl border transition-all duration-150 flex items-center justify-between cursor-pointer group {{ $isDone ? 'bg-emerald-50/60 border-emerald-300 shadow-sm' : 'bg-wheat-50/50 border-wheat-300/80 hover:bg-avocado-50/60 hover:border-avocado-400 hover:shadow-sm' }}">
                                        <div class="min-w-0 pr-2">
                                            <div class="flex items-center gap-2">
                                                <p class="font-extrabold text-xs {{ $isDone ? 'text-emerald-950' : 'text-darkbrown-900 group-hover:text-avocado-900' }} truncate">
                                                    {{ $dItem->masterItem?->name }}
                                                </p>
                                                @if($isDone)
                                                    <span class="text-[10px] font-extrabold text-emerald-800 bg-emerald-100/90 border border-emerald-300/80 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                                        <i data-lucide="check" class="w-3 h-3 text-emerald-600"></i>
                                                        Cocok
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-[11px] text-darkbrown-500 font-mono mt-0.5">
                                                Unit: <span class="font-bold text-darkbrown-700">{{ $allocatedUnit->unit_code }}</span> • Kondisi: {{ $allocatedUnit->condition }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <!-- Tombol Interaktif / Badge Barcode Yang Jelas Bisa Diklik -->
                                            <button 
                                                type="button"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-mono font-black text-xs transition-all duration-150 shadow-sm {{ $isDone ? 'bg-white text-emerald-800 border-2 border-emerald-300' : 'bg-white text-darkbrown-900 border-2 border-wheat-300 group-hover:border-avocado-500 group-hover:bg-avocado-600 group-hover:text-white group-hover:shadow-md group-hover:scale-105 active:scale-95' }}">
                                                @if(!$isDone)
                                                    <i data-lucide="scan-barcode" class="w-3.5 h-3.5 text-avocado-600 group-hover:text-white transition-colors"></i>
                                                @endif
                                                <span>{{ $allocatedUnit->barcode }}</span>
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="p-3 rounded-2xl border border-amber-200 bg-amber-50/50 flex items-center justify-between">
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
            </div>
        </div>
    </template>
    @endif
</div>

