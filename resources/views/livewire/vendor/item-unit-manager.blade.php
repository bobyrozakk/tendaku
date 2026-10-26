<div class="space-y-6">

    <!-- Top Filter Controls -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-wheat-200 shadow-tendaku-sm space-y-4">
        <!-- Segmented Tab Pills: Status Filter -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 scrollbar-none">
            <button wire:click="setTab('all')"
                class="flex items-center gap-2 py-2 px-4 rounded-xl text-xs transition duration-200 shrink-0 font-bold
                {{ $filterStatus === 'all' ? 'bg-darkbrown-800 text-white shadow-sm' : 'text-darkbrown-600 hover:bg-wheat-100' }}">
                <span>Semua Unit</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $filterStatus === 'all' ? 'bg-white/20 text-white' : 'bg-wheat-200 text-darkbrown-800' }}">
                    {{ $totalAll }}
                </span>
            </button>

            <button wire:click="setTab('available')"
                class="flex items-center gap-2 py-2 px-4 rounded-xl text-xs transition duration-200 shrink-0 font-bold
                {{ $filterStatus === 'available' ? 'bg-avocado-600 text-white shadow-sm' : 'text-darkbrown-600 hover:bg-wheat-100' }}">
                <span class="w-2 h-2 rounded-full bg-avocado-300"></span>
                <span>Tersedia</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $filterStatus === 'available' ? 'bg-white/20 text-white' : 'bg-wheat-200 text-darkbrown-800' }}">
                    {{ $totalAvailable }}
                </span>
            </button>

            <button wire:click="setTab('rented')"
                class="flex items-center gap-2 py-2 px-4 rounded-xl text-xs transition duration-200 shrink-0 font-bold
                {{ $filterStatus === 'rented' ? 'bg-sunglow-500 text-darkbrown-900 shadow-sm' : 'text-darkbrown-600 hover:bg-wheat-100' }}">
                <span class="w-2 h-2 rounded-full bg-sunglow-800"></span>
                <span>Disewa</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $filterStatus === 'rented' ? 'bg-darkbrown-900/20 text-darkbrown-900' : 'bg-wheat-200 text-darkbrown-800' }}">
                    {{ $totalRented }}
                </span>
            </button>

            <button wire:click="setTab('maintenance')"
                class="flex items-center gap-2 py-2 px-4 rounded-xl text-xs transition duration-200 shrink-0 font-bold
                {{ $filterStatus === 'maintenance' ? 'bg-goldenrod-600 text-white shadow-sm' : 'text-darkbrown-600 hover:bg-wheat-100' }}">
                <span class="w-2 h-2 rounded-full bg-goldenrod-200"></span>
                <span>Perawatan</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $filterStatus === 'maintenance' ? 'bg-white/20 text-white' : 'bg-wheat-200 text-darkbrown-800' }}">
                    {{ $totalMaintenance }}
                </span>
            </button>

            <button wire:click="setTab('retired')"
                class="flex items-center gap-2 py-2 px-4 rounded-xl text-xs transition duration-200 shrink-0 font-bold
                {{ $filterStatus === 'retired' ? 'bg-darkbrown-600 text-white shadow-sm' : 'text-darkbrown-600 hover:bg-wheat-100' }}">
                <span>Dipensiunkan</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] {{ $filterStatus === 'retired' ? 'bg-white/20 text-white' : 'bg-wheat-200 text-darkbrown-800' }}">
                    {{ $totalRetired }}
                </span>
            </button>
        </div>

        <!-- Filter Row: Search, Product Dropdown, Condition Dropdown, Add Button -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
            <!-- Search Input -->
            <div class="lg:col-span-4 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-darkbrown-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search"
                       type="text"
                       placeholder="Cari barcode / kode unit / produk..."
                       class="w-full pl-9 pr-4 py-2 text-xs bg-wheat-50 border border-wheat-300 rounded-xl focus:ring-2 focus:ring-avocado-500/20 focus:border-avocado-500 text-darkbrown-900 placeholder:text-darkbrown-400 font-medium transition" />
            </div>

            <!-- Filter by Product -->
            <div class="lg:col-span-3">
                <select wire:model.live="filterProduct"
                        class="w-full py-2 px-3 text-xs bg-wheat-50 border border-wheat-300 rounded-xl focus:ring-2 focus:ring-avocado-500/20 focus:border-avocado-500 text-darkbrown-900 font-medium transition">
                    <option value="">Semua Produk</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter by Condition -->
            <div class="lg:col-span-3">
                <select wire:model.live="filterCondition"
                        class="w-full py-2 px-3 text-xs bg-wheat-50 border border-wheat-300 rounded-xl focus:ring-2 focus:ring-avocado-500/20 focus:border-avocado-500 text-darkbrown-900 font-medium transition">
                    <option value="all">Semua Kondisi Fisik</option>
                    <option value="excellent">Sangat Baik (Excellent)</option>
                    <option value="good">Baik (Good)</option>
                    <option value="fair">Cukup (Fair)</option>
                    <option value="damaged">Rusak (Damaged)</option>
                    <option value="lost">Hilang (Lost)</option>
                </select>
            </div>

            <!-- Add Unit Button -->
            <div class="lg:col-span-2 flex justify-end">
                <button wire:click="openAddModal"
                        class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl font-bold text-xs text-white bg-avocado-600 hover:bg-avocado-700 active:bg-avocado-800 shadow-sm transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Unit</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Units Table -->
    <x-card padding="p-0" class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-wheat-100/70 border-b border-wheat-200 text-darkbrown-700 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-5">Produk Induk</th>
                        <th class="py-3.5 px-5">Kode Unit / Barcode</th>
                        <th class="py-3.5 px-5">Status Operasional</th>
                        <th class="py-3.5 px-5">Kondisi Fisik</th>
                        <th class="py-3.5 px-5">Pembelian</th>
                        <th class="py-3.5 px-5 text-right">Aksi & Kontrol</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-wheat-200/70 bg-white">
                    @forelse ($units as $unit)
                        <tr class="hover:bg-wheat-50/70 transition-colors">
                            <!-- Produk Induk -->
                            <td class="py-3.5 px-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl overflow-hidden bg-wheat-100 shrink-0 border border-wheat-200">
                                        @if ($unit->masterItem && $unit->masterItem->image_url)
                                            <img src="{{ $unit->masterItem->image_url }}" alt="{{ $unit->masterItem->name }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-wheat-400">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-bold text-darkbrown-900 text-sm">
                                            {{ $unit->masterItem->name ?? 'Produk Tidak Diketahui' }}
                                        </p>
                                        <p class="text-[11px] text-darkbrown-500 font-medium">
                                            Tarif: Rp {{ number_format($unit->masterItem->daily_rate ?? 0, 0, ',', '.') }}/hari
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Kode Unit & Barcode -->
                            <td class="py-3.5 px-5">
                                <div class="space-y-1">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-wheat-100 text-darkbrown-900 font-mono font-bold text-xs border border-wheat-200">
                                        {{ $unit->unit_code }}
                                    </span>
                                    @if ($unit->barcode)
                                        <div class="flex items-center gap-1 text-[11px] text-darkbrown-500 font-mono">
                                            <svg class="w-3.5 h-3.5 text-darkbrown-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                            </svg>
                                            <span>{{ $unit->barcode }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Status Operasional -->
                            <td class="py-3.5 px-5">
                                @if ($unit->status === 'available')
                                    <span class="text-xs font-semibold text-avocado-700">Tersedia</span>
                                @elseif ($unit->status === 'rented')
                                    <span class="text-xs font-semibold text-amber-700">Sedang Disewa</span>
                                @elseif ($unit->status === 'maintenance')
                                    <span class="text-xs font-semibold text-yellow-700">Perawatan</span>
                                @elseif ($unit->status === 'retired')
                                    <span class="text-xs font-semibold text-darkbrown-400">Dipensiunkan</span>
                                @endif
                            </td>

                            <!-- Kondisi Fisik -->
                            <td class="py-3.5 px-5">
                                @if ($unit->condition === 'excellent')
                                    <span class="text-xs font-semibold text-emerald-700">Sangat Baik</span>
                                @elseif ($unit->condition === 'good')
                                    <span class="text-xs font-semibold text-green-700">Baik</span>
                                @elseif ($unit->condition === 'fair')
                                    <span class="text-xs font-semibold text-amber-700">Cukup</span>
                                @elseif ($unit->condition === 'damaged')
                                    <span class="text-xs font-semibold text-red-600">Rusak</span>
                                @elseif ($unit->condition === 'lost')
                                    <span class="text-xs font-semibold text-darkbrown-400">Hilang</span>
                                @endif
                            </td>

                            <!-- Pembelian -->
                            <td class="py-3.5 px-5">
                                <p class="font-semibold text-darkbrown-800">
                                    {{ $unit->purchase_price ? 'Rp ' . number_format($unit->purchase_price, 0, ',', '.') : '-' }}
                                </p>
                                <p class="text-[11px] text-darkbrown-500">
                                    {{ $unit->purchase_date ? $unit->purchase_date->format('d M Y') : 'Tgl tidak dicatat' }}
                                </p>
                            </td>

                            <!-- Aksi & Kontrol -->
                            <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5 justify-end">
                                    <button wire:click="openEditModal({{ $unit->id }})"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-avocado-700 bg-avocado-50 hover:bg-avocado-100 transition"
                                            title="Edit Unit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                    </button>

                                    <button wire:click="openDeleteModal({{ $unit->id }})"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-600 bg-red-50 hover:bg-red-100 transition"
                                            title="Hapus Unit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-darkbrown-500">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <svg class="w-12 h-12 text-wheat-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p class="font-bold text-sm text-darkbrown-800">Tidak ada unit fisik yang cocok.</p>
                                    <p class="text-xs text-darkbrown-500">Ubah kata kunci pencarian atau tambahkan unit fisik baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($units->hasPages())
            <div class="p-4 border-t border-wheat-200">
                {{ $units->links() }}
            </div>
        @endif
    </x-card>

    <!-- Modal Tambah Unit -->
    @if ($showAddModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-darkbrown-950/60 p-4 animate-fade-in backdrop-blur-sm">
            <x-card class="w-full max-w-lg shadow-tendaku-lg border-wheat-300 relative bg-white overflow-hidden max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center border-b border-wheat-200 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-xl bg-avocado-100 text-avocado-700">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </span>
                        <h3 class="text-base font-extrabold text-darkbrown-900">Tambah Unit Fisik Baru</h3>
                    </div>
                    <button wire:click="$set('showAddModal', false)" class="text-darkbrown-400 hover:text-darkbrown-700 p-1 rounded-lg">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="addUnit" class="space-y-4">
                    <!-- Produk Induk -->
                    <div>
                        <x-input-label value="Produk Induk *" class="font-bold text-xs text-darkbrown-800" />
                        <select wire:model.live="masterItemId" wire:change="autoGenerateUnitCode"
                                class="w-full mt-1.5 text-xs font-semibold bg-white border border-wheat-300 focus:border-avocado-500 rounded-xl py-2 px-3 text-darkbrown-800">
                            <option value="">Pilih Produk...</option>
                            @foreach ($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                        @error('masterItemId') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <!-- Kode Unit & Barcode -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <div class="flex items-center justify-between">
                                <x-input-label value="Kode Unit *" class="font-bold text-xs text-darkbrown-800" />
                                <button type="button" wire:click="autoGenerateUnitCode" class="text-[10px] text-avocado-700 font-bold hover:underline">
                                    Auto Generate
                                </button>
                            </div>
                            <x-text-input wire:model="unitCode" class="w-full mt-1.5 font-mono text-xs uppercase" placeholder="Contoh: TND-001" />
                            @error('unitCode') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <x-input-label value="Barcode (Opsional)" class="font-bold text-xs text-darkbrown-800" />
                                <button type="button" wire:click="generateBarcode" class="text-[10px] text-avocado-700 font-bold hover:underline">
                                    Buat Barcode
                                </button>
                            </div>
                            <x-text-input wire:model="barcode" class="w-full mt-1.5 font-mono text-xs" placeholder="Contoh: BC-829103" />
                            @error('barcode') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Status & Kondisi -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-input-label value="Status Operasional *" class="font-bold text-xs text-darkbrown-800" />
                            <select wire:model="status" class="w-full mt-1.5 text-xs font-semibold bg-white border border-wheat-300 rounded-xl py-2 px-3 text-darkbrown-800">
                                <option value="available">Tersedia (Siap Sewa)</option>
                                <option value="maintenance">Perawatan (Maintenance)</option>
                                <option value="retired">Dipensiunkan (Retired)</option>
                            </select>
                            @error('status') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <x-input-label value="Kondisi Fisik *" class="font-bold text-xs text-darkbrown-800" />
                            <select wire:model="condition" class="w-full mt-1.5 text-xs font-semibold bg-white border border-wheat-300 rounded-xl py-2 px-3 text-darkbrown-800">
                                <option value="excellent">Sangat Baik (Like New)</option>
                                <option value="good">Baik (Good)</option>
                                <option value="fair">Cukup (Fair)</option>
                                <option value="damaged">Rusak (Damaged)</option>
                                <option value="lost">Hilang (Lost)</option>
                            </select>
                            @error('condition') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Tanggal Pembelian & Harga Beli -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-input-label value="Tanggal Pembelian" class="font-bold text-xs text-darkbrown-800" />
                            <x-text-input type="date" wire:model="purchaseDate" class="w-full mt-1.5 text-xs" />
                            @error('purchaseDate') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <x-input-label value="Harga Beli (Rp)" class="font-bold text-xs text-darkbrown-800" />
                            <x-text-input type="number" wire:model="purchasePrice" class="w-full mt-1.5 text-xs" placeholder="Contoh: 450000" />
                            @error('purchasePrice') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Catatan Tambahan -->
                    <div>
                        <x-input-label value="Catatan Fisik / Nomor Serial Pabrik" class="font-bold text-xs text-darkbrown-800" />
                        <textarea wire:model="notes" rows="2" class="w-full mt-1.5 text-xs bg-white border border-wheat-300 rounded-xl p-2.5 text-darkbrown-800 focus:ring-avocado-500" placeholder="Keterangan kondisi atau tanda khusus unit..."></textarea>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex justify-end gap-2 pt-4 border-t border-wheat-200">
                        <button type="button" wire:click="$set('showAddModal', false)" class="px-4 py-2 rounded-xl text-xs font-bold text-darkbrown-700 bg-wheat-100 hover:bg-wheat-200 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-avocado-600 hover:bg-avocado-700 shadow-sm transition">
                            Simpan Unit
                        </button>
                    </div>
                </form>
            </x-card>
        </div>
    @endif

    <!-- Modal Edit Unit -->
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-darkbrown-950/60 p-4 animate-fade-in backdrop-blur-sm">
            <x-card class="w-full max-w-lg shadow-tendaku-lg border-wheat-300 relative bg-white overflow-hidden max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center border-b border-wheat-200 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-xl bg-goldenrod-100 text-goldenrod-700">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </span>
                        <h3 class="text-base font-extrabold text-darkbrown-900">Edit Unit Fisik</h3>
                    </div>
                    <button wire:click="$set('showEditModal', false)" class="text-darkbrown-400 hover:text-darkbrown-700 p-1 rounded-lg">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="updateUnit" class="space-y-4">
                    <div>
                        <x-input-label value="Produk Induk *" class="font-bold text-xs text-darkbrown-800" />
                        <select wire:model="masterItemId"
                                class="w-full mt-1.5 text-xs font-semibold bg-white border border-wheat-300 focus:border-avocado-500 rounded-xl py-2 px-3 text-darkbrown-800">
                            @foreach ($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                        @error('masterItemId') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-input-label value="Kode Unit *" class="font-bold text-xs text-darkbrown-800" />
                            <x-text-input wire:model="unitCode" class="w-full mt-1.5 font-mono text-xs uppercase" />
                            @error('unitCode') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <x-input-label value="Barcode" class="font-bold text-xs text-darkbrown-800" />
                            <x-text-input wire:model="barcode" class="w-full mt-1.5 font-mono text-xs" />
                            @error('barcode') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-input-label value="Status Operasional *" class="font-bold text-xs text-darkbrown-800" />
                            <select wire:model="status" class="w-full mt-1.5 text-xs font-semibold bg-white border border-wheat-300 rounded-xl py-2 px-3 text-darkbrown-800">
                                <option value="available">Tersedia (Siap Sewa)</option>
                                <option value="rented">Sedang Disewa (Rented)</option>
                                <option value="maintenance">Perawatan (Maintenance)</option>
                                <option value="retired">Dipensiunkan (Retired)</option>
                            </select>
                            @error('status') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <x-input-label value="Kondisi Fisik *" class="font-bold text-xs text-darkbrown-800" />
                            <select wire:model="condition" class="w-full mt-1.5 text-xs font-semibold bg-white border border-wheat-300 rounded-xl py-2 px-3 text-darkbrown-800">
                                <option value="excellent">Sangat Baik (Like New)</option>
                                <option value="good">Baik (Good)</option>
                                <option value="fair">Cukup (Fair)</option>
                                <option value="damaged">Rusak (Damaged)</option>
                                <option value="lost">Hilang (Lost)</option>
                            </select>
                            @error('condition') <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-input-label value="Tanggal Pembelian" class="font-bold text-xs text-darkbrown-800" />
                            <x-text-input type="date" wire:model="purchaseDate" class="w-full mt-1.5 text-xs" />
                        </div>

                        <div>
                            <x-input-label value="Harga Beli (Rp)" class="font-bold text-xs text-darkbrown-800" />
                            <x-text-input type="number" wire:model="purchasePrice" class="w-full mt-1.5 text-xs" />
                        </div>
                    </div>

                    <div>
                        <x-input-label value="Catatan Tambahan" class="font-bold text-xs text-darkbrown-800" />
                        <textarea wire:model="notes" rows="2" class="w-full mt-1.5 text-xs bg-white border border-wheat-300 rounded-xl p-2.5 text-darkbrown-800"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t border-wheat-200">
                        <button type="button" wire:click="$set('showEditModal', false)" class="px-4 py-2 rounded-xl text-xs font-bold text-darkbrown-700 bg-wheat-100 hover:bg-wheat-200 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-avocado-600 hover:bg-avocado-700 shadow-sm transition">
                            Perbarui Unit
                        </button>
                    </div>
                </form>
            </x-card>
        </div>
    @endif

    <!-- Modal Konfirmasi Hapus Unit -->
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-darkbrown-950/60 p-4 animate-fade-in backdrop-blur-sm">
            <x-card class="w-full max-w-sm shadow-tendaku-lg border-wheat-300 relative bg-white text-center p-6">
                <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 mx-auto flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 class="text-base font-extrabold text-darkbrown-900 mb-1">Hapus Unit Fisik?</h3>
                <p class="text-xs text-darkbrown-600 mb-6">
                    Tindakan ini akan menghapus unit fisik dari inventaris toko Anda. Riwayat sewa lampau tetap aman tersimpan.
                </p>

                <div class="flex justify-center gap-2">
                    <button type="button" wire:click="$set('showDeleteModal', false)" class="px-4 py-2 rounded-xl text-xs font-bold text-darkbrown-700 bg-wheat-100 hover:bg-wheat-200 transition">
                        Batal
                    </button>
                    <button type="button" wire:click="deleteUnit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-red-600 hover:bg-red-700 shadow-sm transition">
                        Ya, Hapus Unit
                    </button>
                </div>
            </x-card>
        </div>
    @endif
</div>