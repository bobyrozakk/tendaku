<div class="space-y-6" x-data="{ showValidationModal: false, showScanModal: @entangle('showScanModal') }"
    x-on:approve-success.window="showValidationModal = false"
    x-on:approve-validation-failed.window="showValidationModal = true">

    {{-- Flash Toast Notification --}}
    @if(session()->has('pos_message'))
        <div class="flex items-center justify-between gap-3 p-4 rounded-xl bg-avocado-50 border border-avocado-300 text-darkbrown-900 shadow-sm animate-fade-in" role="alert">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-avocado-600 text-white flex items-center justify-center shrink-0">
                    <i data-lucide="check" class="w-4 h-4"></i>
                </div>
                <div>
                    <p class="text-xs font-bold leading-tight">{{ session('pos_message') }}</p>
                    <p class="text-[11px] text-darkbrown-600 mt-0.5">Status pesanan berhasil diperbarui secara sistem.</p>
                </div>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-darkbrown-400 hover:text-darkbrown-700 p-1">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    @endif

    {{-- Top Section: Operational Status Cards (Taste-skill Modern Kasir Standard) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {{-- KPI 1: Antrean Verifikasi (Pending) --}}
        <div class="bg-white p-5 rounded-2xl border border-wheat-200/90 shadow-sm relative overflow-hidden flex flex-col justify-between transition-all duration-150 hover:-translate-y-0.5 hover:shadow-tendaku-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <span class="text-[11px] font-bold text-darkbrown-500 uppercase tracking-wider block">Antrean validasi</span>
                    <p class="text-4xl font-extrabold text-sunglow-700 tracking-tight mt-1 font-admin-number tabular-nums">
                        {{ $kpi['pending'] }}
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-sunglow-100 text-sunglow-800 border border-sunglow-200 flex items-center justify-center shrink-0 shadow-xs">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-wheat-100 flex items-center justify-between text-xs">
                <span class="text-darkbrown-500 font-medium">Perlu scan barcode fisik</span>
                <span class="font-bold {{ $kpi['pending'] > 0 ? 'text-sunglow-800' : 'text-darkbrown-400' }}">
                    {{ $kpi['pending'] > 0 ? 'Membutuhkan kasir' : 'Antrean bersih' }}
                </span>
            </div>
        </div>

        {{-- KPI 2: Tervalidasi Hari Ini (Completed) --}}
        <div class="bg-white p-5 rounded-2xl border border-wheat-200/90 shadow-sm relative overflow-hidden flex flex-col justify-between transition-all duration-150 hover:-translate-y-0.5 hover:shadow-tendaku-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <span class="text-[11px] font-bold text-darkbrown-500 uppercase tracking-wider block">Tervalidasi hari ini</span>
                    <p class="text-4xl font-extrabold text-avocado-700 tracking-tight mt-1 font-admin-number tabular-nums">
                        {{ $kpi['completed'] }}
                    </p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-avocado-100 text-avocado-800 border border-avocado-200 flex items-center justify-center shrink-0 shadow-xs">
                    <i data-lucide="circle-check" class="w-5 h-5"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-wheat-100 flex items-center justify-between text-xs">
                <span class="text-darkbrown-500 font-medium">Siap serah-terima di pickup</span>
                <span class="font-bold text-avocado-700">Terverifikasi</span>
            </div>
        </div>
    </div>

    {{-- Main Workarea: Workspace Validasi & Antrean --}}
    <div class="bg-white rounded-2xl border border-wheat-200/90 shadow-sm overflow-hidden">
        {{-- Section Bar: Search & Segmentation --}}
        <div class="p-4 sm:p-5 border-b border-wheat-200/80 bg-wheat-50/40 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-extrabold text-darkbrown-900 tracking-tight flex items-center gap-2">
                    <i data-lucide="scan-line" class="w-4 h-4 text-avocado-700"></i>
                    <span>Daftar Transaksi Validasi</span>
                </h2>
                <p class="text-xs text-darkbrown-500 mt-0.5">Pilih transaksi untuk meninjau kecocokan barcode unit sewa</p>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                {{-- Search Bar --}}
                <div class="relative w-full sm:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-darkbrown-400">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </div>
                    <input 
                        type="text" 
                        wire:model.live.debounce.250ms="searchOrder" 
                        placeholder="Cari Kode Booking / Nama..." 
                        class="w-full text-xs pl-10 pr-3.5 py-2.5 bg-white border border-wheat-300 rounded-xl text-darkbrown-900 placeholder-darkbrown-400 focus:outline-none focus:ring-2 focus:ring-avocado-500/30 focus:border-avocado-600 transition"
                    />
                </div>

                {{-- Segmented Tabs --}}
                <div class="inline-flex p-1 bg-wheat-200/60 rounded-xl border border-wheat-300/70 shrink-0">
                    <button wire:click="$set('activeTab', 'pending')"
                        class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all duration-150 flex items-center gap-2 cursor-pointer active:scale-[0.97] {{ $activeTab === 'pending' ? 'bg-white text-darkbrown-900 shadow-sm border border-wheat-200' : 'text-darkbrown-600 hover:text-darkbrown-900' }}">
                        <span>Pending</span>
                        <span class="px-1.5 py-0.5 text-[10px] rounded-full font-admin-number font-black {{ $activeTab === 'pending' ? 'bg-sunglow-200 text-sunglow-900' : 'bg-wheat-300/70 text-darkbrown-700' }}">
                            {{ $kpi['pending'] }}
                        </span>
                    </button>
                    <button wire:click="$set('activeTab', 'completed')"
                        class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all duration-150 flex items-center gap-2 cursor-pointer active:scale-[0.97] {{ $activeTab === 'completed' ? 'bg-white text-darkbrown-900 shadow-sm border border-wheat-200' : 'text-darkbrown-600 hover:text-darkbrown-900' }}">
                        <span>Tervalidasi</span>
                        <span class="px-1.5 py-0.5 text-[10px] rounded-full font-admin-number font-black {{ $activeTab === 'completed' ? 'bg-avocado-100 text-avocado-900' : 'bg-wheat-300/70 text-darkbrown-700' }}">
                            {{ $kpi['completed'] }}
                        </span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Orders List (Taste-skill Card Rows with Lift & Tactile Feedback) --}}
        <div class="divide-y divide-wheat-200/70">
            @forelse($orders as $order)
                @php
                    $cust = $order->customer?->name ?? $order->guest_name;
                    $period = $order->pickup_date->format('d M') . ' - ' . $order->return_date->format('d M Y');
                    $isPending = $order->status === 'pending';
                    $isPaid = $order->payments->contains(fn ($p) => $p->status === 'paid');
                    $orderScannedMap = $scannedUnits[(string) $order->id] ?? [];
                    $assignedCount = 0;
                    $totalItems = $order->details->count();
                    foreach ($order->details as $d) {
                        if (!empty($d->item_unit_id) && !empty($orderScannedMap[(string) $d->id])) {
                            $assignedCount++;
                        }
                    }
                    $isReadyToApprove = ($totalItems > 0 && $assignedCount >= $totalItems);
                @endphp
                <div 
                    wire:click="selectOrder({{ $order->id }})"
                    x-on:click="showValidationModal = true"
                    wire:loading.class="opacity-70 cursor-wait pointer-events-none"
                    class="group p-4 sm:p-5 hover:bg-wheat-50/70 active:scale-[0.998] transition-all duration-150 cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    
                    {{-- Left column: Customer & Booking data --}}
                    <div class="flex items-start sm:items-center gap-3.5 min-w-0">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center font-extrabold text-sm shrink-0 {{ $isPending ? 'bg-wheat-100 text-darkbrown-800 border border-wheat-200 group-hover:bg-avocado-50 group-hover:border-avocado-300 group-hover:text-avocado-800' : 'bg-avocado-100 text-avocado-800 border border-avocado-200' }} transition-colors shadow-xs">
                            {{ strtoupper(substr($cust, 0, 1)) }}
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-extrabold text-sm text-darkbrown-900 group-hover:text-avocado-800 transition-colors truncate">
                                    {{ $cust }}
                                </h3>
                                <span class="font-mono text-[11px] font-bold px-2 py-0.5 rounded-md bg-wheat-100 text-darkbrown-700 border border-wheat-200">
                                    {{ $order->booking_code }}
                                </span>
                            </div>

                            <div class="flex items-center gap-3 mt-1 text-xs text-darkbrown-500 flex-wrap">
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-darkbrown-400"></i>
                                    <span>{{ $period }}</span>
                                    <span class="text-darkbrown-400 font-mono">({{ $order->rental_days }} Hari)</span>
                                </span>
                                <span>•</span>
                                <span class="flex items-center gap-1 font-semibold text-darkbrown-700">
                                    <i data-lucide="package" class="w-3.5 h-3.5 text-darkbrown-400"></i>
                                    <span class="font-admin-number font-bold">{{ $totalItems }}</span> Barang
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Right column: Status Indicators & Financials --}}
                    <div class="flex items-center justify-between sm:justify-end gap-4 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-wheat-100">
                        <div class="text-left sm:text-right">
                            <span class="text-[10px] font-bold text-darkbrown-500 uppercase tracking-wider block">Total tagihan</span>
                            <span class="text-base font-extrabold text-darkbrown-900 font-admin-number tabular-nums">
                                Rp {{ number_format($order->grand_total, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($isPending)
                                @if($isPaid)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
                                        <i data-lucide="check" class="w-3 h-3 text-emerald-600"></i>
                                        Lunas
                                    </span>
                                @else
                                    <span class="text-[11px] font-medium text-amber-900 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200">
                                        Belum bayar
                                    </span>
                                @endif

                                @if($isReadyToApprove)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-black text-avocado-900 bg-avocado-100 px-2.5 py-1 rounded-lg border border-avocado-300">
                                        <i data-lucide="check-check" class="w-3 h-3 text-avocado-700"></i>
                                        Siap setujui
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-sunglow-900 bg-sunglow-100 px-2.5 py-1 rounded-lg border border-sunglow-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-sunglow-600 animate-pulse"></span>
                                        Scan unit
                                    </span>
                                @endif
                            @else
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-avocado-900 bg-avocado-100 px-3 py-1 rounded-lg border border-avocado-200">
                                    <i data-lucide="circle-check" class="w-3.5 h-3.5 text-avocado-700"></i>
                                    Tervalidasi
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
                        <p class="font-extrabold text-sm text-darkbrown-800">Tidak ada transaksi ditemukan</p>
                        <p class="text-xs text-darkbrown-500 max-w-sm mx-auto mt-1">
                            {{ $searchOrder ? "Tidak ada pesanan yang sesuai dengan kata kunci '{$searchOrder}'." : 'Semua pesanan pada tab ini sudah selesai diproses.' }}
                        </p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    {{-- MODAL VALIDATION & DETAIL PESANAN --}}
    @if($this->selectedOrder)
        @php
            $custModal = $this->selectedOrder->customer?->name ?? $this->selectedOrder->guest_name;
            $totalItems = $this->selectedOrder->details->count();
            $orderScannedMap = $scannedUnits[(string) $this->selectedOrder->id] ?? [];
            $assignedCount = 0;
            foreach ($this->selectedOrder->details as $d) {
                if (!empty($d->item_unit_id) && !empty($orderScannedMap[(string) $d->id])) {
                    $assignedCount++;
                }
            }
            $allMatched = ($totalItems > 0 && $assignedCount >= $totalItems);
            $progressPercent = $totalItems > 0 ? round(($assignedCount / $totalItems) * 100) : 0;
            $isOrderPending = $this->selectedOrder->status === 'pending';
            $isModalPaid = $this->selectedOrder->payments->contains(fn ($p) => $p->status === 'paid');
        @endphp

        <template x-teleport="body">
            <div
                x-show="showValidationModal"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-darkbrown-950/60 p-4"
                x-on:click.self="showValidationModal = false"
                x-on:keydown.escape.window="showValidationModal = false"
                style="display: none;">

                <div
                    x-show="showValidationModal"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="bg-white rounded-2xl border border-wheat-200 shadow-xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden relative">

                    {{-- Modal Header Bar --}}
                    <div class="px-6 py-4 border-b border-wheat-200 bg-wheat-50/50 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3">
                            <span class="font-mono text-xs font-black px-2.5 py-1 rounded bg-white text-darkbrown-900 border border-wheat-300 shadow-sm">
                                {{ $this->selectedOrder->booking_code }}
                            </span>
                            <div>
                                <h3 class="text-sm font-extrabold text-darkbrown-900 leading-tight">
                                    {{ $custModal }}
                                </h3>
                                <p class="text-[11px] text-darkbrown-500">
                                    {{ $this->selectedOrder->pickup_date->format('d M') }} - {{ $this->selectedOrder->return_date->format('d M Y') }} ({{ $this->selectedOrder->rental_days }} Hari)
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($isOrderPending)
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-sunglow-100 text-sunglow-900 border border-sunglow-200">
                                    Pending Validasi
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-avocado-100 text-avocado-900 border border-avocado-200">
                                    Tervalidasi
                                </span>
                            @endif

                            <button 
                                type="button"
                                x-on:click="showValidationModal = false" 
                                class="p-1.5 rounded-lg text-darkbrown-400 hover:text-darkbrown-800 hover:bg-wheat-200/60 transition-colors cursor-pointer"
                                title="Tutup (Esc)">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Modal Scrollable Body --}}
                    <div class="p-6 overflow-y-auto space-y-5 flex-1">
                        {{-- Quick Info Card: Payment & Item Counts --}}
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="p-3.5 rounded-xl bg-wheat-50/70 border border-wheat-200">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500 block">Total Tagihan</span>
                                <span class="text-base font-extrabold text-darkbrown-900 font-mono tabular-nums">
                                    Rp {{ number_format($this->selectedOrder->grand_total, 0, ',', '.') }}
                                </span>
                            </div>

                            <div class="p-3.5 rounded-xl bg-wheat-50/70 border border-wheat-200">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500 block">Status Pembayaran</span>
                                @if($isModalPaid)
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-800 mt-0.5">
                                        <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                        Lunas (Online/Transfer)
                                    </span>
                                @else
                                    <span class="text-xs font-bold text-amber-800 mt-0.5 block">
                                        Belum Lunas (Bayar di Toko)
                                    </span>
                                @endif
                            </div>

                            <div class="p-3.5 rounded-xl bg-wheat-50/70 border border-wheat-200">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500 block">Pencocokan Barcode</span>
                                <span class="text-xs font-extrabold {{ $allMatched ? 'text-avocado-800' : 'text-sunglow-800' }} mt-0.5 block">
                                    {{ $assignedCount }} dari {{ $totalItems }} Cocok ({{ $progressPercent }}%)
                                </span>
                            </div>
                        </div>

                        {{-- Progress Bar (Jika Status Pending) --}}
                        @if($isOrderPending)
                            <div class="p-4 rounded-xl border {{ $allMatched ? 'border-avocado-200 bg-avocado-50/50' : 'border-wheat-200 bg-wheat-50/50' }} flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="space-y-1.5 flex-1">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="font-bold text-darkbrown-800">Status Pencocokan Fisik Unit</span>
                                        <span class="font-mono font-bold text-darkbrown-600">{{ $progressPercent }}%</span>
                                    </div>
                                    <div class="w-full h-2 bg-wheat-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-avocado-600 rounded-full transition-all duration-200" style="width: {{ $progressPercent }}%"></div>
                                    </div>
                                </div>

                                <div class="shrink-0 flex items-center gap-2">
                                    <div wire:loading wire:target="openScanModal" class="inline-flex items-center text-xs text-darkbrown-500 font-semibold gap-1.5">
                                        <svg class="animate-spin h-4 w-4 text-avocado-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <span>Membuka scanner...</span>
                                    </div>
                                    @if(!$allMatched)
                                        <button 
                                            type="button"
                                            x-on:click="showScanModal = true; $nextTick(() => { $refs.barcodeInput?.focus(); if (typeof window.renderLucideIcons === 'function') window.renderLucideIcons(); })"
                                            wire:click="openScanModal" 
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white bg-avocado-700 hover:bg-avocado-800 active:scale-95 transition cursor-pointer shadow-sm">
                                            <i data-lucide="scan-barcode" class="w-4 h-4"></i>
                                            <span>Scan / Input Barcode</span>
                                        </button>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-avocado-800 bg-white px-3 py-1.5 rounded-lg border border-avocado-300">
                                            <i data-lucide="check" class="w-4 h-4 text-avocado-600"></i>
                                            <span>Semua Unit Cocok!</span>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Table of Order Details --}}
                        <div class="space-y-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-darkbrown-700 block">
                                Rincian Barang & Unit Fisik
                            </span>

                            <div class="overflow-x-auto rounded-xl border border-wheat-200">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-wheat-100 text-darkbrown-800 font-bold uppercase tracking-wider">
                                        <tr>
                                            <th class="py-2.5 px-4">Nama Barang</th>
                                            <th class="py-2.5 px-4">Tarif/Hari</th>
                                            <th class="py-2.5 px-4">Kode Unit & Barcode</th>
                                            <th class="py-2.5 px-4 text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-wheat-200 bg-white">
                                        @foreach($this->selectedOrder->details as $item)
                                            @php
                                                $unit = $item->itemUnit;
                                                $isItemScanned = !empty($orderScannedMap[(string) $item->id]);
                                            @endphp
                                            <tr class="hover:bg-wheat-50/50 transition-colors">
                                                <td class="py-3 px-4 font-bold text-darkbrown-900">
                                                    {{ $item->masterItem?->name }}
                                                </td>
                                                <td class="py-3 px-4 font-mono text-darkbrown-700">
                                                    Rp {{ number_format($item->daily_rate_snapshot, 0, ',', '.') }}
                                                </td>
                                                <td class="py-3 px-4">
                                                    @if($unit)
                                                        <div class="inline-flex items-center gap-1.5">
                                                            <span class="font-mono font-bold text-darkbrown-800 bg-wheat-100 px-2 py-0.5 rounded border border-wheat-200">
                                                                {{ $unit->unit_code }}
                                                            </span>
                                                            <span class="font-mono text-darkbrown-600 bg-wheat-50 px-2 py-0.5 rounded border border-wheat-200">
                                                                {{ $unit->barcode }}
                                                            </span>
                                                        </div>
                                                    @else
                                                        <span class="text-rose-700 text-[11px] font-semibold">
                                                            Belum dialokasikan unit
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="py-3 px-4 text-center">
                                                    @if($isItemScanned)
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                            <i data-lucide="check" class="w-3 h-3 text-emerald-600"></i>
                                                            Cocok
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-sunglow-50 text-sunglow-900 border border-sunglow-200">
                                                            <i data-lucide="clock" class="w-3 h-3 text-sunglow-600"></i>
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
                    </div>

                    {{-- Modal Footer Bar --}}
                    <div class="px-6 py-4 border-t border-wheat-200 bg-wheat-50/70 flex items-center justify-between shrink-0">
                        <button 
                            type="button"
                            x-on:click="showValidationModal = false"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-darkbrown-700 hover:bg-wheat-200/60 transition cursor-pointer">
                            Tutup
                        </button>

                        @if($isOrderPending)
                            <button
                                type="button"
                                wire:click="approveOrder({{ $this->selectedOrder->id }})"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-xs transition duration-150 select-none {{ $allMatched ? 'bg-avocado-700 hover:bg-avocado-800 text-white cursor-pointer shadow-sm active:scale-95' : 'bg-wheat-200 text-darkbrown-400 border border-wheat-300 cursor-not-allowed opacity-70' }}"
                                {{ !$allMatched ? 'disabled' : '' }}>
                                @if(!$allMatched)
                                    <i data-lucide="lock" class="w-4 h-4 text-darkbrown-400"></i>
                                    <span>Setujui Pesanan (Scan Belum Lengkap)</span>
                                @else
                                    <i data-lucide="check" class="w-4 h-4 text-white"></i>
                                    <span>Setujui & Konfirmasi Pesanan</span>
                                @endif
                            </button>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-avocado-100 text-avocado-900 border border-avocado-200">
                                <i data-lucide="check-check" class="w-4 h-4 text-avocado-700"></i>
                                Pesanan Sudah Disetujui
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </template>
    @endif

    {{-- MODAL SCAN & PENCOCOKAN BARCODE CEPAT --}}
    <template x-teleport="body">
        <div 
            x-show="showScanModal"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-on:keydown.escape.window="showScanModal = false"
            x-on:click.self="showScanModal = false"
            class="fixed inset-0 z-[110] flex items-center justify-center bg-darkbrown-950/60 p-4"
            style="display: none;">
            
            <div 
                x-show="showScanModal"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="w-full max-w-lg bg-white rounded-2xl border border-wheat-200 shadow-xl overflow-hidden relative">
                
                {{-- Header --}}
                <div class="px-5 py-4 border-b border-wheat-200 bg-wheat-50/50 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-avocado-100 text-avocado-800 border border-avocado-200 flex items-center justify-center">
                            <i data-lucide="scan-barcode" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-extrabold text-darkbrown-900 leading-tight">Pemindaian Barcode Unit</h3>
                            <p class="text-[11px] text-darkbrown-500">Scan via hardware scanner atau pilih manual</p>
                        </div>
                    </div>
                    <button 
                        type="button"
                        x-on:click="showScanModal = false" 
                        class="p-1.5 rounded-lg text-darkbrown-400 hover:text-darkbrown-700 hover:bg-wheat-200/60 transition cursor-pointer">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    {{-- Input Scanner Box --}}
                    <form wire:submit.prevent="processScan" class="space-y-2">
                        <label class="text-xs font-bold text-darkbrown-800 block">Input Kode / Scan Fisik</label>
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <input 
                                    type="text"
                                    x-ref="barcodeInput"
                                    wire:model="modalBarcode" 
                                    class="w-full font-mono uppercase text-xs rounded-xl py-2.5 px-3 bg-wheat-50/30 border border-wheat-300 text-darkbrown-900 placeholder-darkbrown-400 focus:outline-none focus:ring-2 focus:ring-avocado-500/30 focus:border-avocado-600 transition" 
                                    placeholder="Arahkan scanner ke barcode unit..." 
                                />
                            </div>
                            <button type="submit" class="px-4 py-2.5 rounded-xl font-bold text-xs text-white bg-avocado-700 hover:bg-avocado-800 active:scale-95 transition cursor-pointer shrink-0">
                                Cocokkan
                            </button>
                        </div>

                        {{-- Alert Error --}}
                        @if($scanError)
                            <div class="text-xs text-rose-800 font-bold bg-rose-50 p-2.5 rounded-xl border border-rose-200 flex items-center gap-2">
                                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                                <span>{{ $scanError }}</span>
                            </div>
                        @endif

                        {{-- Alert Success --}}
                        @if($scanSuccessMessage)
                            <div class="text-xs text-emerald-800 font-bold bg-emerald-50 p-2.5 rounded-xl border border-emerald-200 flex items-center gap-2">
                                <i data-lucide="check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                <span>{{ $scanSuccessMessage }}</span>
                            </div>
                        @endif
                    </form>

                    {{-- Manual Unit Click List --}}
                    <div class="pt-3 border-t border-wheat-200 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-darkbrown-500 uppercase tracking-wider block">
                                Daftar Unit Pada Pesanan Ini:
                            </span>
                            <span class="text-[10px] text-darkbrown-400">Klik kartu untuk auto-scan</span>
                        </div>

                        <div class="max-h-52 overflow-y-auto space-y-2 pr-1">
                            @php
                                $currentOrderDetails = $this->selectedOrder ? $this->selectedOrder->details : collect();
                                $currentScanned = $scannedUnits[(string) ($this->selectedOrder->id ?? 0)] ?? [];
                            @endphp
                            @forelse($currentOrderDetails as $dItem)
                                @php
                                    $allocatedUnit = $dItem->itemUnit;
                                    $isDone = !empty($currentScanned[(string) $dItem->id]);
                                @endphp
                                @if($allocatedUnit)
                                    <div wire:click="quickFillBarcode('{{ $allocatedUnit->barcode }}')"
                                         wire:loading.class="opacity-60 pointer-events-none"
                                         class="p-3 rounded-xl border transition flex items-center justify-between cursor-pointer {{ $isDone ? 'bg-emerald-50/50 border-emerald-300' : 'bg-white border-wheat-200 hover:border-avocado-400 hover:bg-wheat-50/50 active:scale-[0.99]' }}">
                                        <div class="min-w-0 pr-2">
                                            <p class="font-bold text-xs text-darkbrown-900 truncate">
                                                {{ $dItem->masterItem?->name }}
                                            </p>
                                            <p class="text-[11px] text-darkbrown-500 font-mono mt-0.5">
                                                Kode: <span class="font-bold text-darkbrown-700">{{ $allocatedUnit->unit_code }}</span> • {{ $allocatedUnit->condition }}
                                            </p>
                                        </div>
                                        <div class="shrink-0 flex items-center gap-2">
                                            <span class="font-mono text-xs px-2 py-1 rounded bg-wheat-100 text-darkbrown-800 border border-wheat-200">
                                                {{ $allocatedUnit->barcode }}
                                            </span>
                                            @if($isDone)
                                                <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <div class="p-3 text-center text-xs text-darkbrown-400">
                                    Tidak ada item pada transaksi ini.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-5 py-3 border-t border-wheat-200 bg-wheat-50/50 flex justify-end">
                    <button 
                        type="button" 
                        x-on:click="showScanModal = false"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-darkbrown-700 hover:bg-wheat-200/60 transition cursor-pointer">
                        Selesai Scan
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
