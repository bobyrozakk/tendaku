<div class="space-y-6">
    <!-- Top KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <x-card class="border-l-4 border-l-sunglow-500">
            <h3 class="text-sm font-semibold text-darkbrown-500 uppercase">Pending Validasi</h3>
            <p class="text-3xl font-bold text-sunglow-600 mt-2">{{ $kpi['pending'] }}</p>
        </x-card>
        <x-card class="border-l-4 border-l-avocado-500">
            <h3 class="text-sm font-semibold text-darkbrown-500 uppercase">Pickup Hari Ini</h3>
            <p class="text-3xl font-bold text-avocado-600 mt-2">{{ $kpi['pickup_today'] }}</p>
        </x-card>
        <x-card class="border-l-4 border-l-avocado-500">
            <h3 class="text-sm font-semibold text-darkbrown-500 uppercase">Retur Hari Ini</h3>
            <p class="text-3xl font-bold text-avocado-600 mt-2">{{ $kpi['return_today'] }}</p>
        </x-card>
    </div>

    <!-- Main Validation Queue Area -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Panel: Incoming Orders -->
        <div class="lg:col-span-1 space-y-4">
            <x-card class="h-full flex flex-col">
                <x-slot name="header">
                    <h2 class="font-bold text-lg text-darkbrown-900">Antrean Validasi</h2>
                </x-slot>

                <!-- Filter Tabs -->
                <div class="flex space-x-2 border-b border-wheat-300 pb-2 mb-4">
                    <button wire:click="$set('activeTab', 'all')" class="px-3 py-1 text-sm font-semibold rounded {{ $activeTab === 'all' ? 'bg-sunglow-200 text-darkbrown-900' : 'text-darkbrown-500 hover:bg-wheat-100' }}">Semua</button>
                    <button wire:click="$set('activeTab', 'pending')" class="px-3 py-1 text-sm font-semibold rounded {{ $activeTab === 'pending' ? 'bg-sunglow-200 text-darkbrown-900' : 'text-darkbrown-500 hover:bg-wheat-100' }}">Menunggu ({{ $kpi['pending'] }})</button>
                    <button wire:click="$set('activeTab', 'ready')" class="px-3 py-1 text-sm font-semibold rounded {{ $activeTab === 'ready' ? 'bg-sunglow-200 text-darkbrown-900' : 'text-darkbrown-500 hover:bg-wheat-100' }}">Siap Ambil</button>
                </div>

                <!-- Order List -->
                <div class="flex-1 overflow-y-auto max-h-[600px] space-y-3">
                    @forelse($this->filteredOrders as $order)
                        <div wire:click="selectOrder('{{ $order['id'] }}')" 
                             class="p-3 border rounded-lg cursor-pointer transition-colors 
                                    {{ $selectedOrder && $selectedOrder['id'] === $order['id'] ? 'border-avocado-500 bg-avocado-50' : 'border-wheat-200 hover:border-sunglow-400 bg-white' }}">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h4 class="font-bold text-darkbrown-900">{{ $order['customer'] }}</h4>
                                    <span class="text-xs bg-goldenrod-100 text-goldenrod-800 px-2 py-0.5 rounded">{{ $order['tier'] }}</span>
                                </div>
                                <span class="text-xs font-semibold text-darkbrown-500">{{ $order['id'] }}</span>
                            </div>
                            <p class="text-sm text-darkbrown-700 mb-2">Durasi: {{ $order['duration'] }}</p>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-darkbrown-600">Items: {{ collect($order['items'])->sum('qty') }} Unit</span>
                                @if($order['status'] === 'pending')
                                    <x-badge variant="sunglow">Pending</x-badge>
                                @else
                                    <x-badge variant="avocado">Siap Ambil</x-badge>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 text-darkbrown-500">
                            Tidak ada pesanan di kategori ini.
                        </div>
                    @endforelse
                </div>
            </x-card>
        </div>

        <!-- Right Panel: Validation & Matching -->
        <div class="lg:col-span-2">
            <x-card class="h-full relative">
                @if($selectedOrder)
                    @php
                        $totalAllocated = 0;
                        $totalScanned = 0;
                        foreach($selectedOrder['items'] as $item) {
                            $totalAllocated += count($item['allocated_units']);
                            $totalScanned += count($item['scanned_units']);
                        }
                        $allMatched = ($totalAllocated > 0 && $totalAllocated === $totalScanned);
                    @endphp

                    <div class="flex justify-between items-start border-b border-wheat-300 pb-4 mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-darkbrown-900">Validasi Pesanan: {{ $selectedOrder['id'] }}</h2>
                            <p class="text-darkbrown-600">Pelanggan: <strong>{{ $selectedOrder['customer'] }}</strong> ({{ $selectedOrder['tier'] }})</p>
                            <p class="text-darkbrown-600">Pembayaran: <span class="text-avocado-700 font-bold">{{ $selectedOrder['payment_status'] }}</span></p>
                        </div>
                        <div>
                            @if($selectedOrder['status'] === 'pending')
                                <div class="flex gap-2">
                                    <x-primary-button variant="sunglow" wire:click="approveOrder('{{ $selectedOrder['id'] }}')" :disabled="!$allMatched" class="{{ !$allMatched ? 'opacity-50 cursor-not-allowed' : '' }}">
                                        Konfirmasi Persiapan
                                    </x-primary-button>
                                </div>
                            @else
                                <x-badge variant="avocado" class="px-4 py-2 text-sm">Siap Pickup</x-badge>
                            @endif
                        </div>
                    </div>

                    <!-- Allocation Table -->
                    <div class="space-y-4">
                        <h3 class="font-bold text-darkbrown-800">Alokasi Unit Fisik</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm border-collapse border border-wheat-200">
                                <thead class="bg-wheat-100 text-darkbrown-800">
                                    <tr>
                                        <th class="py-2 px-3 border border-wheat-200">Barang</th>
                                        <th class="py-2 px-3 border border-wheat-200">Dibutuhkan</th>
                                        <th class="py-2 px-3 border border-wheat-200">Unit Dialokasikan</th>
                                        <th class="py-2 px-3 border border-wheat-200 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($selectedOrder['items'] as $item)
                                        <tr class="bg-white hover:bg-wheat-50 transition-colors">
                                            <td class="py-3 px-3 font-medium border border-wheat-200">{{ $item['name'] }}</td>
                                            <td class="py-3 px-3 border border-wheat-200">{{ $item['qty'] }} unit</td>
                                            <td class="py-3 px-3 border border-wheat-200">
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($item['allocated_units'] as $barcode)
                                                        @php
                                                            $isScanned = in_array($barcode, $item['scanned_units']);
                                                        @endphp
                                                        <span class="px-2 py-1 rounded text-xs font-mono border {{ $isScanned ? 'bg-avocado-100 text-avocado-800 border-avocado-200' : 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                                            {{ $barcode }}
                                                            @if($isScanned)
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 inline ml-1" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                                            @endif
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 text-center border border-wheat-200">
                                                <span class="text-xs font-bold text-sunglow-700 bg-sunglow-100 px-2 py-1 rounded">{{ $item['status'] }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Barcode Matching Section -->
                    @if($selectedOrder['status'] === 'pending')
                    <div class="mt-6 p-4 rounded-lg border {{ $allMatched ? 'border-avocado-300 bg-avocado-50' : 'border-wheat-300 bg-wheat-50' }}">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                            <div>
                                <h3 class="font-bold text-darkbrown-800 mb-1">Pencocokan Unit Barcode</h3>
                                <p class="text-sm text-darkbrown-600 font-semibold">{{ $totalScanned }}/{{ $totalAllocated }} unit berhasil dicocokkan</p>
                            </div>
                            @if(!$allMatched)
                                <x-primary-button variant="avocado" wire:click="openScanModal">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                    Scan / Input Barcode
                                </x-primary-button>
                            @else
                                <div class="text-avocado-600 font-bold flex items-center gap-1 bg-white px-3 py-1.5 rounded-lg border border-avocado-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Semua Cocok
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif

                @else
                    <div class="h-full flex flex-col items-center justify-center text-darkbrown-400 py-20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4 text-wheat-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <p>Pilih pesanan di antrean kiri untuk memvalidasi dan memproses barang.</p>
                    </div>
                @endif
            </x-card>
        </div>
    </div>

    <!-- Scan Modal -->
    @if($showScanModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-darkbrown-900/50 backdrop-blur-sm p-4">
        <x-card class="w-full max-w-sm shadow-tendaku-lg relative">
            <button wire:click="$set('showScanModal', false)" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
            <h3 class="text-lg font-bold text-darkbrown-900 mb-4">Input Barcode Manual</h3>
            
            <form wire:submit.prevent="processScan" class="space-y-4">
                <div>
                    <x-input-label value="Masukkan Kode Barcode" />
                    <x-text-input wire:model="modalBarcode" class="w-full mt-1 font-mono uppercase" placeholder="Mis: A-001" autofocus />
                </div>
                
                @if($scanError)
                    <div class="text-sm text-red-600 font-semibold bg-red-50 p-2 rounded border border-red-200">
                        {{ $scanError }}
                    </div>
                @endif
                
                @if($scanSuccessMessage)
                    <div class="text-sm text-avocado-700 font-semibold bg-avocado-50 p-2 rounded border border-avocado-200">
                        {{ $scanSuccessMessage }}
                    </div>
                @endif

                <div class="flex justify-end pt-2">
                    <x-primary-button type="submit" variant="avocado" class="w-full justify-center">Verifikasi Barcode</x-primary-button>
                </div>
            </form>
        </x-card>
    </div>
    @endif
</div>
