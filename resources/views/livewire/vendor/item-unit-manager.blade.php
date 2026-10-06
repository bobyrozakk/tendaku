<div class="space-y-6" x-data="{ activeTab: 'active' }">
    <!-- Filter, Segmented Tab, Search & Add Button — Single Row -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <!-- Segmented Tab Pills -->
        <div class="inline-flex p-1 bg-white rounded-2xl border border-wheat-300 shadow-sm max-w-fit shrink-0">
            <button @click="activeTab = 'active'" 
                :class="activeTab === 'active' ? 'bg-goldenrod-600 text-white shadow-md font-bold' : 'text-darkbrown-600 hover:bg-wheat-100 font-medium'"
                class="flex items-center gap-2 py-2.5 px-5 rounded-xl text-xs transition-all duration-200">
                <svg class="w-4 h-4" :class="activeTab === 'active' ? 'text-white' : 'text-darkbrown-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Unit Aktif</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="activeTab === 'active' ? 'bg-white/20 text-white' : 'bg-wheat-200 text-darkbrown-700'">
                    {{ collect($units)->whereIn('status', ['available', 'rented'])->count() }}
                </span>
            </button>
            <button @click="activeTab = 'maintenance'" 
                :class="activeTab === 'maintenance' ? 'bg-goldenrod-600 text-white shadow-md font-bold' : 'text-darkbrown-600 hover:bg-wheat-100 font-medium'"
                class="flex items-center gap-2 py-2.5 px-5 rounded-xl text-xs transition-all duration-200">
                <svg class="w-4 h-4" :class="activeTab === 'maintenance' ? 'text-white' : 'text-darkbrown-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                <span>Unit Maintenance</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="activeTab === 'maintenance' ? 'bg-white/20 text-white' : 'bg-wheat-200 text-darkbrown-700'">
                    {{ collect($units)->whereIn('status', ['maintenance', 'damaged', 'lost'])->count() }}
                </span>
            </button>
        </div>

        <!-- Search Bar & Tambah Unit Button (Side by Side) -->
        <div class="flex items-center gap-3">
            <div class="relative w-full md:w-72">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-darkbrown-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <x-text-input wire:model.live="search" placeholder="Cari barcode / nama barang..." class="w-full text-xs pl-9 py-2.5 bg-white border-wheat-300 focus:border-avocado-500 rounded-xl" />
            </div>
            <button wire:click="$set('showAddModal', true)" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-xs text-white bg-avocado-600 hover:bg-avocado-700 active:bg-avocado-800 shadow-sm transition duration-200 cursor-pointer shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                <span class="hidden sm:inline">Tambah Unit</span>
            </button>
        </div>
    </div>

    <!-- Tabel Unit Physical Modern -->
    <x-card padding="p-0" class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-wheat-100/70 border-b border-wheat-200 text-darkbrown-700 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-5">Nama Barang</th>
                        <th class="py-3.5 px-5">Kode Unit / Barcode</th>
                        <th class="py-3.5 px-5">Status</th>
                        <th class="py-3.5 px-5">Harga Beli</th>
                        <th class="py-3.5 px-5 text-right">Aksi & Kontrol</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-wheat-200/70 bg-white">
                    @php
                        $filteredUnits = collect($units)->filter(function($u) {
                            if (empty($search)) return true;
                            return stripos($u['item_name'], $search) !== false || stripos($u['unit_code'], $search) !== false;
                        });
                    @endphp

                    @forelse($filteredUnits as $unit)
                        @php
                            $isTabActive = ($unit['status'] === 'available' || $unit['status'] === 'rented');
                            $isTabMaint = ($unit['status'] === 'maintenance' || $unit['status'] === 'damaged' || $unit['status'] === 'lost');
                        @endphp

                        <!-- Baris untuk Unit Aktif -->
                        <tr x-show="activeTab === 'active' && {{ $isTabActive ? 'true' : 'false' }}" class="hover:bg-wheat-50/70 transition-colors">
                            <td class="py-4 px-5 font-bold text-darkbrown-900 text-sm">
                                {{ $unit['item_name'] }}
                            </td>
                            <td class="py-4 px-5">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-wheat-100 text-darkbrown-900 font-mono font-bold text-xs border border-wheat-200">
                                    <svg class="w-3.5 h-3.5 text-darkbrown-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                    {{ $unit['unit_code'] }}
                                </span>
                            </td>
                            <td class="py-4 px-5">
                                @if($unit['status'] === 'available')
                                    <x-badge variant="avocado" :dot="true">Tersedia</x-badge>
                                @elseif($unit['status'] === 'rented')
                                    <x-badge variant="sunglow" :dot="true">Sedang Disewa</x-badge>
                                @endif
                            </td>
                            <td class="py-4 px-5 font-semibold text-darkbrown-800">
                                Rp {{ number_format($unit['purchase_price'], 0, ',', '.') }}
                            </td>
                            <td class="py-4 px-5 text-right">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium text-darkbrown-500 bg-wheat-100/70 border border-wheat-200/80">
                                    <svg class="w-3.5 h-3.5 text-avocado-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    Otomatis Sistem
                                </span>
                            </td>
                        </tr>

                        <!-- Baris untuk Unit Maintenance -->
                        <tr x-show="activeTab === 'maintenance' && {{ $isTabMaint ? 'true' : 'false' }}" class="hover:bg-wheat-50/70 transition-colors">
                            <td class="py-4 px-5 font-bold text-darkbrown-900 text-sm">
                                {{ $unit['item_name'] }}
                            </td>
                            <td class="py-4 px-5">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-wheat-100 text-darkbrown-900 font-mono font-bold text-xs border border-wheat-200">
                                    <svg class="w-3.5 h-3.5 text-darkbrown-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                    {{ $unit['unit_code'] }}
                                </span>
                            </td>
                            <td class="py-4 px-5">
                                @if($unit['status'] === 'maintenance')
                                    <x-badge variant="goldenrod" :dot="true">Dalam Maintenance</x-badge>
                                @else
                                    <x-badge variant="dark" :dot="true">Hilang / Rusak</x-badge>
                                @endif
                            </td>
                            <td class="py-4 px-5 font-semibold text-darkbrown-800">
                                Rp {{ number_format($unit['purchase_price'], 0, ',', '.') }}
                            </td>
                            <td class="py-4 px-5 text-right">
                                <select wire:change="updateStatus({{ $unit['id'] }}, $event.target.value)" class="text-xs font-semibold bg-white border border-wheat-300 rounded-xl px-3 py-1.5 focus:ring-2 focus:ring-avocado-500/30 focus:border-avocado-500 shadow-sm text-darkbrown-800 transition cursor-pointer">
                                    <option value="">Ubah Status Unit...</option>
                                    <option value="available">✓ Selesai Perbaikan (Kembali Tersedia)</option>
                                    <option value="damaged">⚠ Tetap Rusak / Hilang</option>
                                </select>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-darkbrown-500">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <svg class="w-10 h-10 text-wheat-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <p class="font-medium text-sm">Tidak ada unit barang yang ditemukan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Modal Tambah Unit (Teleported to Body) -->
    @if($showAddModal)
    <template x-teleport="body">
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-darkbrown-950/60 p-4 animate-fade-in">
            <x-card class="w-full max-w-md shadow-tendaku-lg border-wheat-300 relative bg-white overflow-hidden">
                <!-- Modal Header -->
                <div class="flex justify-between items-center border-b border-wheat-200 pb-4 mb-5">
                    <div class="flex items-center gap-2.5">
                        <span class="p-2 rounded-xl bg-avocado-100 text-avocado-700">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </span>
                        <h3 class="text-base font-extrabold text-darkbrown-900">Tambah Unit Physical Baru</h3>
                    </div>
                    <button wire:click="$set('showAddModal', false)" class="text-darkbrown-400 hover:text-darkbrown-700 p-1.5 rounded-lg hover:bg-wheat-100 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <form wire:submit="addUnit" class="space-y-4">
                    <div>
                        <x-input-label value="Scan / Input Kode Barcode Unit" class="font-bold text-xs text-darkbrown-800" />
                        <x-text-input wire:model="newItemCode" class="w-full mt-1.5 font-mono text-xs uppercase" placeholder="Contoh: TND-001" autofocus />
                        @error('newItemCode') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <x-input-label value="Harga Beli Unit (Rp)" class="font-bold text-xs text-darkbrown-800" />
                        <x-text-input wire:model="newPurchasePrice" type="number" class="w-full mt-1.5 text-xs" placeholder="Contoh: 500000" />
                        @error('newPurchasePrice') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <x-input-label value="Status Awal Unit" class="font-bold text-xs text-darkbrown-800" />
                        <select wire:model="newStatus" class="w-full mt-1.5 text-xs font-semibold bg-white border border-wheat-300 focus:border-avocado-500 focus:ring-2 focus:ring-avocado-400/40 rounded-xl shadow-sm py-2.5 text-darkbrown-800">
                            <option value="available">Tersedia (Siap Disewa)</option>
                            <option value="maintenance">Maintenance (Dalam Perbaikan)</option>
                        </select>
                        @error('newStatus') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-5 border-t border-wheat-200 mt-6">
                        <button type="button" wire:click="$set('showAddModal', false)" class="px-4 py-2.5 rounded-xl font-bold text-xs text-darkbrown-700 bg-wheat-100 hover:bg-wheat-200 border border-wheat-300 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-xs text-white bg-avocado-600 hover:bg-avocado-700 active:bg-avocado-800 shadow-sm transition">
                            Simpan Unit
                        </button>
                    </div>
                </form>
            </x-card>
        </div>
    </template>
    @endif
</div>