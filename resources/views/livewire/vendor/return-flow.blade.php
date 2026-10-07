<div class="space-y-5" x-data="{ showHistoryModal: false, selectedHistory: null }">

    <!-- Modern Separated Pill Tab Navigation -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="inline-flex p-1 bg-white rounded-2xl border border-wheat-300 shadow-sm max-w-fit">
            <button wire:click="$set('activeTab', 'return')"
                class="flex items-center gap-2 py-2.5 px-5 rounded-xl text-xs transition-all duration-200 {{ $activeTab === 'return' ? 'bg-goldenrod-600 text-white shadow-md font-bold' : 'text-darkbrown-600 hover:bg-wheat-100 font-medium' }}">
                <svg class="w-4 h-4 {{ $activeTab === 'return' ? 'text-white' : 'text-darkbrown-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span>Return & Inspeksi</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $activeTab === 'return' ? 'bg-white/20 text-white' : 'bg-wheat-200 text-darkbrown-700' }}">
                    {{ count($activeRentals) }}
                </span>
            </button>
            <button wire:click="$set('activeTab', 'history')"
                class="flex items-center gap-2 py-2.5 px-5 rounded-xl text-xs transition-all duration-200 {{ $activeTab === 'history' ? 'bg-goldenrod-600 text-white shadow-md font-bold' : 'text-darkbrown-600 hover:bg-wheat-100 font-medium' }}">
                <svg class="w-4 h-4 {{ $activeTab === 'history' ? 'text-white' : 'text-darkbrown-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <span>Riwayat Transaksi</span>
            </button>
        </div>
    </div>

    <!-- KONTEN TAB 1: RETURN & INSPEKSI -->
    @if($activeTab === 'return')
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Kolom Kiri: Daftar Transaksi Aktif -->
            <div class="lg:col-span-5 space-y-4">
                <x-card variant="warm" padding="p-4">
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <h3 class="font-extrabold text-xs text-darkbrown-900 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-avocado-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                                Transaksi Sewa Aktif
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-wheat-200 text-darkbrown-800 border border-wheat-300">
                                {{ count($activeRentals) }} Transaksi
                            </span>
                        </div>

                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-darkbrown-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <x-text-input wire:model.live="searchOrder" placeholder="Cari ID Booking / Nama Penyewa..." class="w-full text-xs pl-9 py-2 bg-white border-wheat-300 rounded-xl" />
                        </div>
                    </div>
                </x-card>

                <div class="space-y-3">
                    @forelse($activeRentals as $rental)
                        <div wire:click="selectRental('{{ $rental['id'] }}')"
                            class="p-4 rounded-2xl border cursor-pointer transition-all duration-200 relative overflow-hidden {{ $activeOrder && $activeOrder['id'] === $rental['id'] ? 'bg-white border-avocado-500 shadow-tendaku ring-2 ring-avocado-500/20' : 'bg-white border-wheat-200/90 hover:border-wheat-300 hover:shadow-tendaku-sm' }}">
                            <!-- Active Bar Indicator -->
                            <div class="absolute left-0 top-0 bottom-0 w-1.5 {{ $activeOrder && $activeOrder['id'] === $rental['id'] ? 'bg-avocado-600' : 'bg-transparent' }}"></div>

                            <div class="flex justify-between items-start mb-2 pl-1">
                                <span class="font-extrabold text-darkbrown-900 text-sm flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-goldenrod-500"></span>
                                    {{ $rental['customer'] }}
                                </span>
                                <span class="px-2 py-0.5 rounded-lg text-[11px] font-mono font-bold bg-wheat-100 text-darkbrown-800 border border-wheat-200">{{ $rental['id'] }}</span>
                            </div>
                            <div class="text-xs text-darkbrown-600 space-y-2 pl-1">
                                <p class="text-[11px] text-darkbrown-600 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-darkbrown-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>Jatuh Tempo: <strong>{{ $rental['due_date'] }}</strong></span>
                                </p>
                                <div class="pt-2 border-t border-wheat-200/70 flex justify-between items-center">
                                    @if($rental['is_late'])
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800 border border-red-200">
                                            ⚠️ Terlambat (+Rp {{ number_format($rental['late_fee'], 0, ',', '.') }})
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">
                                            ✓ Tepat Waktu
                                        </span>
                                    @endif
                                    <span class="text-[11px] font-bold text-darkbrown-500">{{ count($rental['items']) }} Item</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-xs text-darkbrown-500 bg-white rounded-2xl border border-wheat-200 space-y-2">
                            <svg class="w-10 h-10 mx-auto text-wheat-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="font-bold text-darkbrown-700">Tidak ada sewa aktif</p>
                            <p class="text-[11px]">Tidak ada transaksi sewa aktif yang perlu diretur saat ini.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Kolom Kanan: Detail Inspeksi & Proses Return -->
            <div class="lg:col-span-7">
                @if($activeOrder)
                    <x-card class="space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-wheat-200 pb-4">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-wheat-100 text-darkbrown-900 border border-wheat-200">
                                        {{ $activeOrder['id'] }}
                                    </span>
                                </div>
                                <h3 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight">Lembar Kerja Inspeksi: {{ $activeOrder['customer'] }}</h3>
                            </div>
                            <div>
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-bold {{ $activeOrder['ktp_held'] ? 'bg-sunglow-100 text-darkbrown-900 border border-sunglow-300' : 'bg-wheat-100 text-darkbrown-700' }}">
                                    🪪 Pegang KTP Fisik: {{ $activeOrder['ktp_held'] ? 'Ya (Jaminan)' : 'Tidak' }}
                                </span>
                            </div>
                        </div>

                        <!-- Daftar Item untuk Diinspeksi -->
                        <div class="space-y-4">
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-darkbrown-500 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-avocado-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                Pencocokan & Kondisi Unit Fisik
                            </h4>

                            @foreach($activeOrder['items'] as $item)
                                <div class="p-4 rounded-2xl bg-wheat-50/70 border border-wheat-200/80 space-y-3">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-extrabold text-sm text-darkbrown-900">{{ $item['name'] }}</p>
                                            <span class="inline-flex items-center gap-1 text-xs font-mono font-bold text-darkbrown-700 bg-white px-2.5 py-0.5 rounded-md border border-wheat-200 mt-1">
                                                <svg class="w-3 h-3 text-darkbrown-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                                </svg>
                                                {{ $item['barcode'] }}
                                            </span>
                                        </div>
                                        @if($item['returned'])
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">
                                                ✓ Sudah Dicek
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-bold bg-sunglow-100 text-darkbrown-900 border border-sunglow-200">
                                                ⏳ Belum Dicek
                                            </span>
                                        @endif
                                    </div>

                                    @if(!$item['returned'])
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3 border-t border-wheat-200/80">
                                            <div>
                                                <label class="text-xs font-bold text-darkbrown-800 block mb-1">Kondisi Fisik Unit:</label>
                                                <select wire:model="inspectionNotes.{{ $item['id'] }}" class="w-full text-xs font-semibold bg-white border border-wheat-300 rounded-xl py-2 px-3 focus:ring-2 focus:ring-avocado-500/30 focus:border-avocado-500 text-darkbrown-800 shadow-sm">
                                                    <option value="good">✓ Baik / Normal</option>
                                                    <option value="damaged">⚠️ Rusak / Lecet</option>
                                                    <option value="lost">❌ Hilang / Hancur</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="text-xs font-bold text-darkbrown-800 block mb-1">Denda Kerusakan (Rp):</label>
                                                <x-text-input type="number" wire:model.live="damageFees.{{ $item['id'] }}" class="w-full text-xs py-2 bg-white border-wheat-300 rounded-xl" placeholder="0" />
                                            </div>
                                        </div>
                                        <div class="flex justify-end pt-2">
                                            <button wire:click="markItemReturned({{ $item['id'] }})" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-avocado-600 hover:bg-avocado-700 active:bg-avocado-800 shadow-sm transition">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>Cek Barang & Konfirmasi</span>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <!-- Ringkasan Financial Denda & Total -->
                        <div class="p-5 rounded-2xl bg-wheat-100/70 border border-wheat-200 space-y-2.5">
                            <div class="flex justify-between text-xs text-darkbrown-700 font-medium">
                                <span>Denda Keterlambatan:</span>
                                <span class="font-bold">Rp {{ number_format($activeOrder['late_fee'], 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-xs text-darkbrown-700 font-medium">
                                <span>Total Denda Kerusakan:</span>
                                <span class="font-bold">Rp {{ number_format($this->totalDamageFee, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-extrabold text-darkbrown-900 pt-3 border-t border-wheat-300/80">
                                <span>Total Biaya Tambahan:</span>
                                <span class="text-base text-avocado-700">Rp {{ number_format($this->totalCharge, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        @error('general')
                            <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 font-bold flex items-center gap-2">
                                <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror

                        <div class="flex justify-end pt-2">
                            <button wire:click="completeReturn" class="w-full sm:w-auto py-3 px-6 rounded-xl font-extrabold text-xs tracking-wider uppercase text-white bg-avocado-600 hover:bg-avocado-700 active:bg-avocado-800 shadow-sm transition duration-200 flex items-center justify-center gap-2 cursor-pointer shadow-tendaku-avocado-glow">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Selesai & Serahkan Kembali KTP</span>
                            </button>
                        </div>
                    </x-card>
                @else
                    <x-card class="h-full flex flex-col items-center justify-center p-12 text-center text-darkbrown-500 min-h-[400px]">
                        <div class="p-4 rounded-full bg-wheat-100 mb-3 border border-wheat-200">
                            <svg class="w-10 h-10 text-wheat-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        </div>
                        <p class="text-base font-extrabold text-darkbrown-900">Pilih Transaksi Sewa di Sebelah Kiri</p>
                        <p class="text-xs text-darkbrown-500 max-w-sm mt-1">Pilih salah satu penyewa aktif untuk memulai lembar kerja inspeksi dan serah terima KTP kembali.</p>
                    </x-card>
                @endif
            </div>
        </div>
    @else
        <!-- KONTEN TAB 2: RIWAYAT TRANSAKSI SELESAI -->
        <div class="space-y-4">
            <!-- Filter & Pencarian Riwayat Modern -->
            <x-card variant="warm" padding="p-4">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="relative w-full sm:w-1/2">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-darkbrown-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <x-text-input wire:model.live="searchHistory" placeholder="Cari ID Booking atau Nama Pelanggan..." class="w-full text-xs pl-9 py-2.5 bg-white border-wheat-300 rounded-xl" />
                    </div>

                    <div class="w-full sm:w-auto">
                        <select wire:model.live="statusFilter" class="w-full text-xs font-semibold bg-white border border-wheat-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-avocado-500/30 focus:border-avocado-500 text-darkbrown-800 shadow-sm cursor-pointer">
                            <option value="all">Semua Status Riwayat</option>
                            <option value="Selesai">✓ Selesai & Dikembalikan</option>
                            <option value="Denda">⚠️ Dengan Catatan Denda</option>
                        </select>
                    </div>
                </div>
            </x-card>

            <!-- Tabel Riwayat Modern -->
            <x-card padding="p-0" class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-wheat-100/70 border-b border-wheat-200 text-darkbrown-700 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="py-3.5 px-5">ID Booking</th>
                                <th class="py-3.5 px-5">Pelanggan</th>
                                <th class="py-3.5 px-5">Periode Sewa</th>
                                <th class="py-3.5 px-5">Total Pembayaran</th>
                                <th class="py-3.5 px-5">Status</th>
                                <th class="py-3.5 px-5">Tanggal Selesai</th>
                                <th class="py-3.5 px-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-wheat-200/70 bg-white">
                            @forelse($filteredHistory as $hist)
                                <tr class="hover:bg-wheat-50/70 transition-colors">
                                    <td class="py-4 px-5">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-wheat-100 text-darkbrown-900 border border-wheat-200">
                                            {{ $hist['id'] }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-5 font-bold text-darkbrown-900 text-sm">{{ $hist['customer'] }}</td>
                                    <td class="py-4 px-5 text-darkbrown-600 font-medium">{{ $hist['rental_period'] }}</td>
                                    <td class="py-4 px-5 font-extrabold text-darkbrown-900 text-sm">Rp {{ number_format($hist['total'], 0, ',', '.') }}</td>
                                    <td class="py-4 px-5">
                                        <x-badge :variant="str_contains($hist['status'], 'Denda') ? 'goldenrod' : 'avocado'" :dot="true">
                                            {{ $hist['status'] }}
                                        </x-badge>
                                    </td>
                                    <td class="py-4 px-5 text-darkbrown-600 font-medium">{{ $hist['date'] }}</td>
                                    <td class="py-4 px-5 text-right">
                                        <button @click="selectedHistory = {{ json_encode($hist) }}; showHistoryModal = true" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-avocado-800 bg-avocado-100 hover:bg-avocado-200 border border-avocado-200 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span>Detail</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-darkbrown-500">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <svg class="w-10 h-10 text-wheat-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <p class="font-bold text-sm text-darkbrown-700">Tidak ada riwayat transaksi</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    @endif

    <!-- Modern History Detail Modal (Teleported to Body) -->
    <template x-teleport="body">
        <div x-show="showHistoryModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-darkbrown-950/60 p-4 animate-fade-in" @keydown.escape.window="showHistoryModal = false" style="display: none;">
            <div class="w-full max-w-md bg-white rounded-2xl border border-wheat-300 shadow-tendaku-lg overflow-hidden p-6 space-y-5" @click.away="showHistoryModal = false">
                <div class="flex justify-between items-center border-b border-wheat-200 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-xl bg-avocado-100 text-avocado-700">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </span>
                        <h3 class="text-base font-extrabold text-darkbrown-900">Detail Transaksi Riwayat</h3>
                    </div>
                    <button @click="showHistoryModal = false" class="text-darkbrown-400 hover:text-darkbrown-700 p-1.5 rounded-lg hover:bg-wheat-100 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <template x-if="selectedHistory">
                    <div class="space-y-4 text-xs">
                        <div class="p-3.5 rounded-xl bg-wheat-50 border border-wheat-200 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-darkbrown-500 font-semibold">ID Booking:</span>
                                <span class="font-mono font-bold text-darkbrown-900" x-text="selectedHistory.id"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-darkbrown-500 font-semibold">Nama Pelanggan:</span>
                                <span class="font-bold text-darkbrown-900" x-text="selectedHistory.customer"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-darkbrown-500 font-semibold">Periode Sewa:</span>
                                <span class="font-medium text-darkbrown-800" x-text="selectedHistory.rental_period"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-darkbrown-500 font-semibold">Tanggal Selesai:</span>
                                <span class="font-medium text-darkbrown-800" x-text="selectedHistory.date"></span>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-xl bg-wheat-50 border border-wheat-200 space-y-1">
                            <span class="text-darkbrown-500 font-bold block text-[11px] uppercase tracking-wider">Item Disewa:</span>
                            <p class="font-semibold text-darkbrown-900 text-xs" x-text="selectedHistory.items"></p>
                        </div>

                        <div class="flex justify-between items-center p-3.5 rounded-xl bg-avocado-50 border border-avocado-200 text-xs">
                            <span class="font-bold text-darkbrown-800">Total Transaksi:</span>
                            <span class="font-extrabold text-sm text-avocado-800" x-text="'Rp ' + Number(selectedHistory.total).toLocaleString('id-ID')"></span>
                        </div>
                    </div>
                </template>

                <div class="flex justify-end pt-2">
                    <button @click="showHistoryModal = false" class="w-full py-2.5 px-4 rounded-xl font-bold text-xs text-darkbrown-800 bg-wheat-100 hover:bg-wheat-200 border border-wheat-300 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>
