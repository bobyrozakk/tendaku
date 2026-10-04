<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-darkbrown-900">Pengembalian & Inspeksi Barang</h2>
            <p class="text-sm text-darkbrown-600">Kelola proses pengembalian barang (*return*), inspeksi kondisi unit fisik, dan riwayat transaksi.</p>
        </div>
    </div>

    <!-- Navigasi Tab (Return & Inspeksi / Riwayat) -->
    <div class="flex border-b border-wheat-300 gap-6">
        <button wire:click="$set('activeTab', 'return')" 
            class="pb-3 text-sm font-bold border-b-2 transition-colors {{ $activeTab === 'return' ? 'border-avocado-600 text-avocado-700' : 'border-transparent text-darkbrown-600 hover:text-darkbrown-900' }}">
            [ Return & Inspeksi ]
        </button>
        <button wire:click="$set('activeTab', 'history')" 
            class="pb-3 text-sm font-bold border-b-2 transition-colors {{ $activeTab === 'history' ? 'border-avocado-600 text-avocado-700' : 'border-transparent text-darkbrown-600 hover:text-darkbrown-900' }}">
            [ Riwayat ]
        </button>
    </div>

    <!-- KONTEN TAB 1: RETURN & INSPEKSI -->
    @if($activeTab === 'return')
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Kolom Kiri: Daftar Transaksi Aktif -->
            <div class="lg:col-span-5 space-y-4">
                <x-card variant="warm">
                    <x-text-input wire:model.live="searchOrder" placeholder="Cari ID Booking / Nama Penyewa..." class="w-full text-sm" />
                </x-card>

                <div class="space-y-3">
                    @forelse($activeRentals as $rental)
                        <div wire:click="selectRental('{{ $rental['id'] }}')" 
                            class="p-4 rounded-xl border cursor-pointer transition-all {{ $activeOrder && $activeOrder['id'] === $rental['id'] ? 'bg-wheat-100 border-avocado-600 shadow-sm' : 'bg-white border-wheat-300 hover:border-wheat-400' }}">
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-bold text-darkbrown-900 text-sm">{{ $rental['customer'] }}</span>
                                <span class="px-2 py-0.5 rounded text-xs font-mono font-bold bg-wheat-200 text-darkbrown-800">{{ $rental['id'] }}</span>
                            </div>
                            <div class="text-xs text-darkbrown-600 space-y-1">
                                <p>Jatuh Tempo: <span class="font-semibold">{{ $rental['due_date'] }}</span></p>
                                @if($rental['is_late'])
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700">Terlambat (+Rp {{ number_format($rental['late_fee'], 0, ',', '.') }})</span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-avocado-100 text-avocado-800">Tepat Waktu</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm text-darkbrown-500 bg-white rounded-xl border border-wheat-300">
                            Tidak ada transaksi sewa aktif yang perlu diretur saat ini.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Kolom Kanan: Detail Inspeksi & Proses Return -->
            <div class="lg:col-span-7">
                @if($activeOrder)
                    <x-card class="space-y-6">
                        <div class="flex justify-between items-center border-b border-wheat-200 pb-4">
                            <div>
                                <h3 class="text-lg font-bold text-darkbrown-900">Lembar Kerja Inspeksi</h3>
                                <p class="text-xs text-darkbrown-600">Booking ID: <span class="font-mono font-bold">{{ $activeOrder['id'] }}</span> - {{ $activeOrder['customer'] }}</p>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-sunglow-200 text-darkbrown-900">
                                Pegang KTP: {{ $activeOrder['ktp_held'] ? 'Ya' : 'Tidak' }}
                            </span>
                        </div>

                        <!-- Daftar Item untuk Diinspeksi -->
                        <div class="space-y-4">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-darkbrown-500">Pencocokan & Kondisi Barang Fisik</h4>
                            
                            @foreach($activeOrder['items'] as $item)
                                <div class="p-4 rounded-xl bg-wheat-50 border border-wheat-200 space-y-3">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-bold text-sm text-darkbrown-900">{{ $item['name'] }}</p>
                                            <span class="text-xs font-mono text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">{{ $item['barcode'] }}</span>
                                        </div>
                                        @if($item['returned'])
                                            <span class="px-2 py-1 rounded text-xs font-bold bg-avocado-100 text-avocado-800">Sudah Dicek</span>
                                        @else
                                            <span class="px-2 py-1 rounded text-xs font-bold bg-sunglow-100 text-darkbrown-800">Belum Dicek</span>
                                        @endif
                                    </div>

                                    @if(!$item['returned'])
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-wheat-200">
                                            <div>
                                                <label class="text-xs font-semibold text-darkbrown-700">Kondisi Barang:</label>
                                                <select wire:model="inspectionNotes.{{ $item['id'] }}" class="w-full mt-1 text-xs border-wheat-300 rounded-md">
                                                    <option value="good">Baik / Normal</option>
                                                    <option value="damaged">Rusak / Lecet</option>
                                                    <option value="lost">Hilang</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-darkbrown-700">Denda Kerusakan (Opsional):</label>
                                                <input type="number" wire:model.live="damageFees.{{ $item['id'] }}" class="w-full mt-1 text-xs border-wheat-300 rounded-md" placeholder="0" />
                                            </div>
                                        </div>
                                        <div class="flex justify-end pt-2">
                                            <x-primary-button wire:click="markItemReturned({{ $item['id'] }})" variant="avocado" class="!py-1.5 !px-3 text-xs">
                                                Cek Barang & Konfirmasi
                                            </x-primary-button>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <!-- Ringkasan Denda & Tombol Selesai -->
                        <div class="p-4 rounded-xl bg-wheat-100 border border-wheat-300 space-y-2">
                            <div class="flex justify-between text-xs text-darkbrown-700">
                                <span>Denda Keterlambatan:</span>
                                <span class="font-bold">Rp {{ number_format($activeOrder['late_fee'], 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-xs text-darkbrown-700">
                                <span>Total Denda Kerusakan:</span>
                                <span class="font-bold">Rp {{ number_format($this->totalDamageFee, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-bold text-darkbrown-900 pt-2 border-t border-wheat-300">
                                <span>Total Tambahan Biaya:</span>
                                <span>Rp {{ number_format($this->totalCharge, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        @error('general')
                            <p class="text-xs text-red-600 font-semibold">{{ $message }}</p>
                        @enderror

                        <div class="flex justify-end pt-2">
                            <x-primary-button wire:click="completeReturn" variant="avocado" class="w-full sm:w-auto justify-center">
                                Selesai & Serahkan Kembali KTP
                            </x-primary-button>
                        </div>
                    </x-card>
                @else
                <x-card class="h-full flex flex-col items-center justify-center p-12 text-center text-darkbrown-500">
                    <!-- Pembungkus icon agar berada tepat di tengah -->
                    <div class="flex flex-col items-center justify-center space-y-3">
                        <svg class="w-12 h-12 text-wheat-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        <p class="text-sm font-medium">Pilih transaksi sewa di sebelah kiri untuk memulai proses inspeksi dan pengembalian barang.</p>
                    </div>
                </x-card>
                @endif
            </div>
        </div>
    @else
        <!-- KONTEN TAB 2: RIWAYAT TRANSAKSI SELESAI -->
        <div class="space-y-4">
            <!-- Filter & Pencarian Riwayat -->
            <x-card variant="warm">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <x-text-input wire:model.live="searchHistory" placeholder="Cari ID Booking atau Nama Pelanggan..." class="w-full sm:w-1/2 text-sm" />
                    <select wire:model.live="statusFilter" class="w-full sm:w-auto text-xs border-wheat-300 rounded-md shadow-sm">
                        <option value="all">Semua Status Riwayat</option>
                        <option value="Selesai">Selesai & Dikembalikan</option>
                        <option value="Denda">Dengan Catatan Denda</option>
                    </select>
                </div>
            </x-card>

            <!-- Tabel Riwayat -->
            <x-card>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-wheat-300 text-darkbrown-600 bg-wheat-50">
                            <tr>
                                <th class="py-3 px-4 font-semibold">ID Booking</th>
                                <th class="py-3 px-4 font-semibold">Pelanggan</th>
                                <th class="py-3 px-4 font-semibold">Periode Sewa</th>
                                <th class="py-3 px-4 font-semibold">Total</th>
                                <th class="py-3 px-4 font-semibold">Status</th>
                                <th class="py-3 px-4 font-semibold">Tanggal Selesai</th>
                                <th class="py-3 px-4 font-semibold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-wheat-200">
                            @forelse($filteredHistory as $hist)
                                <tr class="hover:bg-wheat-50 transition-colors">
                                    <td class="py-3 px-4 font-mono font-bold text-xs text-darkbrown-800">{{ $hist['id'] }}</td>
                                    <td class="py-3 px-4 font-medium text-darkbrown-900">{{ $hist['customer'] }}</td>
                                    <td class="py-3 px-4 text-xs text-darkbrown-600">{{ $hist['rental_period'] }}</td>
                                    <td class="py-3 px-4 font-semibold">Rp {{ number_format($hist['total'], 0, ',', '.') }}</td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-avocado-100 text-avocado-800">
                                            {{ $hist['status'] }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-xs text-darkbrown-600">{{ $hist['date'] }}</td>
                                    <td class="py-3 px-4 text-right">
                                        <button onclick="alert('Detail Transaksi: {{ $hist['id'] }} - {{ $hist['items'] }}')" class="text-xs font-bold text-avocado-700 hover:text-avocado-900 bg-avocado-50 hover:bg-avocado-100 px-3 py-1.5 rounded-lg transition">
                                            Detail
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-sm text-darkbrown-500">
                                        Tidak ada data riwayat transaksi yang ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    @endif
</div>