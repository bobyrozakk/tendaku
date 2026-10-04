<div class="space-y-6" x-data="{ activeTab: 'active' }">
    <!-- Pilihan Tab Navigasi -->
    <div class="flex border-b border-wheat-300 gap-4">
        <button @click="activeTab = 'active'" 
            :class="activeTab === 'active' ? 'border-avocado-600 text-avocado-700 font-bold' : 'border-transparent text-darkbrown-600 hover:text-darkbrown-900'"
            class="py-2 px-4 border-b-2 transition-colors text-sm">
            Unit Aktif
        </button>
        <button @click="activeTab = 'maintenance'" 
            :class="activeTab === 'maintenance' ? 'border-goldenrod-600 text-goldenrod-700 font-bold' : 'border-transparent text-darkbrown-600 hover:text-darkbrown-900'"
            class="py-2 px-4 border-b-2 transition-colors text-sm">
            Unit Maintenance
        </button>
    </div>

    <!-- Pencarian -->
    <x-card variant="warm">
        <div class="flex items-center gap-4">
            <x-text-input wire:model.live="search" placeholder="Cari barcode / nama barang..." class="w-full md:w-1/2" />
        </div>
    </x-card>

    <!-- Tabel Unit -->
    <x-card>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-wheat-300 text-darkbrown-600 bg-wheat-50">
                    <tr>
                        <th class="py-3 px-4 font-semibold">Nama Barang</th>
                        <th class="py-3 px-4 font-semibold">Kode Unit / Barcode</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold">Harga Beli</th>
                        <th class="py-3 px-4 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-wheat-200">
                    @foreach($units as $unit)
                        @php
                            $isTabActive = ($unit['status'] === 'available' || $unit['status'] === 'rented');
                            $isTabMaint = ($unit['status'] === 'maintenance' || $unit['status'] === 'damaged');
                        @endphp

                        <!-- Baris untuk Unit Aktif -->
                        <tr x-show="activeTab === 'active' && {{ $isTabActive ? 'true' : 'false' }}" class="hover:bg-wheat-50 transition-colors">
                            <td class="py-3 px-4 font-medium text-darkbrown-800">{{ $unit['item_name'] }}</td>
                            <td class="py-3 px-4">
                                <span class="bg-slate-100 text-slate-700 px-2 py-1 rounded font-mono text-xs border border-slate-200">
                                    {{ $unit['unit_code'] }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                @if($unit['status'] === 'available')
                                    <x-badge variant="avocado" :dot="true">Tersedia</x-badge>
                                @elseif($unit['status'] === 'rented')
                                    <x-badge variant="sunglow" :dot="true">Disewa</x-badge>
                                @endif
                            </td>
                            <td class="py-3 px-4">Rp {{ number_format($unit['purchase_price'], 0, ',', '.') }}</td>
                            <td class="py-3 px-4 text-right text-xs text-slate-400 font-medium">
                                (Otomatis Sistem)
                            </td>
                        </tr>

                        <!-- Baris untuk Unit Maintenance -->
                        <tr x-show="activeTab === 'maintenance' && {{ $isTabMaint ? 'true' : 'false' }}" class="hover:bg-wheat-50 transition-colors">
                            <td class="py-3 px-4 font-medium text-darkbrown-800">{{ $unit['item_name'] }}</td>
                            <td class="py-3 px-4">
                                <span class="bg-slate-100 text-slate-700 px-2 py-1 rounded font-mono text-xs border border-slate-200">
                                    {{ $unit['unit_code'] }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                @if($unit['status'] === 'maintenance')
                                    <x-badge variant="goldenrod" :dot="true">Maintenance</x-badge>
                                @else
                                    <x-badge variant="dark" :dot="true">Hilang/Rusak</x-badge>
                                @endif
                            </td>
                            <td class="py-3 px-4">Rp {{ number_format($unit['purchase_price'], 0, ',', '.') }}</td>
                            <td class="py-3 px-4 text-right">
                                <!-- Dropdown interaktif untuk mengubah status selesai perbaikan -->
                                <select wire:change="updateStatus({{ $unit['id'] }}, $event.target.value)" class="text-xs border-wheat-300 focus:border-avocado-500 rounded-md shadow-sm">
                                    <option value="">Ubah Status...</option>
                                    <option value="available">Selesai (Kembali Tersedia)</option>
                                    <option value="damaged">Tetap Rusak / Hilang</option>
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Modal Tambah Unit -->
    @if($showAddModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-darkbrown-900/50 backdrop-blur-sm p-4">
        <x-card class="w-full max-w-md shadow-tendaku-lg">
            <h3 class="text-lg font-bold text-darkbrown-900 mb-4">Tambah Unit Baru</h3>
            
            <form wire:submit="addUnit" class="space-y-4">
                <div>
                    <x-input-label value="Scan / Input Kode Barcode" />
                    <x-text-input wire:model="newItemCode" class="w-full mt-1 font-mono" placeholder="Mis: TND-001" autofocus />
                    @error('newItemCode') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-input-label value="Harga Beli (Rp)" />
                    <x-text-input wire:model="newPurchasePrice" type="number" class="w-full mt-1" placeholder="Mis: 500000" />
                    @error('newPurchasePrice') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <x-input-label value="Status Awal" />
                    <select wire:model="newStatus" class="w-full mt-1 border-wheat-300 focus:border-avocado-500 focus:ring-avocado-500 rounded-md shadow-sm">
                        <option value="available">Tersedia</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                    @error('newStatus') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-wheat-200 mt-6">
                    <x-primary-button type="button" variant="dark" wire:click="$set('showAddModal', false)">Batal</x-primary-button>
                    <x-primary-button type="submit" variant="avocado">Simpan Unit</x-primary-button>
                </div>
            </form>
        </x-card>
    </div>
    @endif
</div>