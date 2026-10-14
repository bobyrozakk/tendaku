<x-layouts.vendor>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                    Dashboard Owner
                </h1>
                <p class="text-xs text-darkbrown-500 mt-0.5">
                    Ringkasan performa finansial, utilisasi inventaris alat, dan monitoring operasional toko
                </p>
            </div>

            <!-- Filter Periode Analitik -->
            <div class="flex items-center gap-1.5 bg-white p-1 rounded-2xl border border-wheat-300 shadow-sm self-start sm:self-auto overflow-x-auto">
                <a href="{{ route('vendor.owner.dashboard', ['period' => 'today']) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition duration-150 whitespace-nowrap {{ $selectedPeriod === 'today' ? 'bg-avocado-600 text-white shadow-sm' : 'text-darkbrown-600 hover:bg-wheat-100' }}">
                    Hari Ini
                </a>
                <a href="{{ route('vendor.owner.dashboard', ['period' => '7_days']) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition duration-150 whitespace-nowrap {{ $selectedPeriod === '7_days' ? 'bg-avocado-600 text-white shadow-sm' : 'text-darkbrown-600 hover:bg-wheat-100' }}">
                    7 Hari
                </a>
                <a href="{{ route('vendor.owner.dashboard', ['period' => '30_days']) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition duration-150 whitespace-nowrap {{ $selectedPeriod === '30_days' ? 'bg-avocado-600 text-white shadow-sm' : 'text-darkbrown-600 hover:bg-wheat-100' }}">
                    30 Hari
                </a>
                <a href="{{ route('vendor.owner.dashboard', ['period' => 'this_month']) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition duration-150 whitespace-nowrap {{ $selectedPeriod === 'this_month' ? 'bg-avocado-600 text-white shadow-sm' : 'text-darkbrown-600 hover:bg-wheat-100' }}">
                    Bulan Ini
                </a>
                <a href="{{ route('vendor.owner.dashboard', ['period' => 'this_year']) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold transition duration-150 whitespace-nowrap {{ $selectedPeriod === 'this_year' ? 'bg-avocado-600 text-white shadow-sm' : 'text-darkbrown-600 hover:bg-wheat-100' }}">
                    Tahun Ini
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Kartu Metrik Ringkasan Finansial & Inventaris -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Omzet Harian -->
            <x-stat-card 
                title="Omzet Harian"
                value="Rp {{ number_format($metrics['daily_revenue'], 0, ',', '.') }}"
                description="Total transaksi sewa valid hari ini"
                color="avocado"
            />

            <!-- 2. Omzet Bulanan -->
            <x-stat-card 
                title="Omzet Bulan Ini"
                value="Rp {{ number_format($metrics['monthly_revenue'], 0, ',', '.') }}"
                description="Akumulasi omzet bulan berjalan"
                color="goldenrod"
            />

            <!-- 3. Pendapatan Periode Terpilih -->
            <x-stat-card 
                title="Omzet ({{ $selectedPeriod === 'today' ? 'Hari Ini' : ($selectedPeriod === '7_days' ? '7 Hari' : ($selectedPeriod === '30_days' ? '30 Hari' : ($selectedPeriod === 'this_month' ? 'Bulan Ini' : 'Tahun Ini'))) }})"
                value="Rp {{ number_format($metrics['period_revenue'], 0, ',', '.') }}"
                description="Rentang {{ $metrics['start_date'] }} s.d {{ $metrics['end_date'] }}"
                color="sunglow"
            />

            <!-- 4. Ringkasan Aset & Keterlambatan -->
            <div class="bg-white p-5 rounded-2xl border border-wheat-200 shadow-tendaku-sm flex flex-col justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-darkbrown-500">Monitoring Masalah</p>
                    <div class="mt-2 grid grid-cols-3 gap-2 text-center">
                        <div class="p-2 rounded-xl bg-red-50 border border-red-100">
                            <span class="block text-lg font-black text-red-600">{{ $metrics['damaged_assets_count'] }}</span>
                            <span class="text-[10px] font-bold text-red-700">Rusak</span>
                        </div>
                        <div class="p-2 rounded-xl bg-slate-100 border border-slate-200">
                            <span class="block text-lg font-black text-slate-800">{{ $metrics['lost_assets_count'] }}</span>
                            <span class="text-[10px] font-bold text-slate-700">Hilang</span>
                        </div>
                        <div class="p-2 rounded-xl bg-amber-50 border border-amber-100">
                            <span class="block text-lg font-black text-amber-600">{{ $metrics['overdue_count'] }}</span>
                            <span class="text-[10px] font-bold text-amber-700">Telat</span>
                        </div>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-t border-wheat-100 flex items-center justify-between text-[11px] text-darkbrown-500">
                    <span>Total Unit Fisik:</span>
                    <span class="font-bold text-darkbrown-900">{{ $metrics['total_units_count'] }} Unit</span>
                </div>
            </div>
        </div>

        <!-- Section 2: Tren Pendapatan & Produk Terlaris (Berdampingan Proporsional) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
            <!-- Tren Pendapatan Harian (8 Kolom) -->
            <div class="lg:col-span-8 flex flex-col">
                <x-card title="Tren Pendapatan Harian" subtitle="Performa omzet harian pada periode {{ $metrics['start_date'] }} hingga {{ $metrics['end_date'] }}" class="flex-1 flex flex-col justify-between">
                    @php
                        $maxTotal = $metrics['revenue_trend']->max('total') ?: 1;
                    @endphp

                    @if ($metrics['revenue_trend']->isEmpty() || $metrics['revenue_trend']->sum('total') == 0)
                        <div class="py-12 text-center text-darkbrown-400 my-auto">
                            <svg class="w-12 h-12 mx-auto text-wheat-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                            </svg>
                            <p class="font-bold text-sm text-darkbrown-800">Belum ada catatan omzet pada periode ini.</p>
                            <p class="text-xs text-darkbrown-500">Transaksi rental yang lunas/selesai akan otomatis divisualisasikan di sini.</p>
                        </div>
                    @else
                        <!-- Mini Bar Chart HTML murni -->
                        <div class="pt-4 pb-2 my-auto">
                            <div class="h-48 flex items-end gap-1.5 sm:gap-2 overflow-x-auto pb-4 scrollbar-none">
                                @foreach ($metrics['revenue_trend'] as $point)
                                    @php
                                        $heightPercent = $point['total'] > 0 ? max(8, round(($point['total'] / $maxTotal) * 100)) : 4;
                                    @endphp
                                    <div class="flex-1 min-w-[28px] sm:min-w-[36px] flex flex-col items-center group relative">
                                        <!-- Tooltip -->
                                        <div class="opacity-0 group-hover:opacity-100 transition duration-150 absolute -top-12 z-20 pointer-events-none bg-darkbrown-900 text-white text-[10px] font-semibold py-1 px-2 rounded-lg whitespace-nowrap shadow-lg">
                                            {{ $point['label'] }}: Rp {{ number_format($point['total'], 0, ',', '.') }}
                                        </div>

                                        <!-- Bar -->
                                        <div class="w-full rounded-t-lg transition-all duration-300 {{ $point['total'] > 0 ? 'bg-gradient-to-t from-avocado-600 to-avocado-400 group-hover:from-avocado-700 group-hover:to-avocado-500 shadow-sm' : 'bg-wheat-200' }}"
                                             style="height: {{ $heightPercent }}%;">
                                        </div>

                                        <!-- Date Label -->
                                        <span class="text-[9px] font-semibold text-darkbrown-400 mt-1.5 truncate max-w-full">
                                            {{ $point['label'] }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </x-card>
            </div>

            <!-- Produk Terlaris (4 Kolom) -->
            <div class="lg:col-span-4 flex flex-col">
                <x-card title="Produk Terlaris" subtitle="Peralatan dengan frekuensi sewa tertinggi" class="flex-1 flex flex-col">
                    <div class="space-y-2.5 mt-2 flex-1">
                        @forelse ($metrics['top_products'] as $index => $item)
                            <div class="flex items-center gap-3 p-3 rounded-xl bg-wheat-50 border border-wheat-200">
                                <span class="w-6 h-6 rounded-lg flex items-center justify-center font-black text-xs shrink-0
                                    {{ $index === 0 ? 'bg-goldenrod-500 text-white' : ($index === 1 ? 'bg-wheat-300 text-darkbrown-900' : 'bg-wheat-200 text-darkbrown-600') }}">
                                    #{{ $index + 1 }}
                                </span>

                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-xs text-darkbrown-900 truncate">{{ $item->name }}</p>
                                    <p class="text-[10px] text-darkbrown-500">{{ $item->category_name ?? 'Umum' }}</p>
                                </div>

                                <div class="text-right shrink-0">
                                    <p class="text-xs font-black text-avocado-700">{{ $item->times_rented }}x sewa</p>
                                    <p class="text-[10px] text-darkbrown-500">Rp {{ number_format($item->total_earnings, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="py-12 text-center text-xs text-darkbrown-400 my-auto">
                                Belum ada data penyewaan produk pada periode ini.
                            </div>
                        @endforelse
                    </div>
                </x-card>
            </div>
        </div>

        <!-- Section 3: Monitoring Keterlambatan Pengembalian (Tabel Full Width Luas) -->
        <x-card title="Monitoring Keterlambatan Pengembalian" subtitle="Daftar penyewaan yang terlambat dikembalikan atau berpotensi terkena denda">
            <div class="overflow-x-auto -mx-5 -mb-5 mt-2">
                <table class="w-full text-left text-xs">
                    <thead class="bg-wheat-100/70 border-y border-wheat-200 text-darkbrown-700 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-5">Kode Booking</th>
                            <th class="py-3 px-5">Pelanggan</th>
                            <th class="py-3 px-5">Jadwal Kembali</th>
                            <th class="py-3 px-5">Keterlambatan</th>
                            <th class="py-3 px-5">Denda Telat</th>
                            <th class="py-3 px-5">Status Unit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-wheat-200/60 bg-white">
                        @forelse ($metrics['overdue_rentals'] as $rental)
                            <tr class="hover:bg-wheat-50/70 transition">
                                <td class="py-3 px-5 font-mono font-bold text-darkbrown-900">
                                    {{ $rental->booking_code }}
                                </td>
                                <td class="py-3 px-5">
                                    <p class="font-bold text-darkbrown-900">{{ $rental->customer->name ?? $rental->guest_name ?? 'Pelanggan' }}</p>
                                    <p class="text-[11px] text-darkbrown-500">{{ $rental->customer->phone ?? $rental->guest_phone ?? '-' }}</p>
                                </td>
                                <td class="py-3 px-5 text-darkbrown-700 font-medium">
                                    {{ $rental->return_date ? \Carbon\Carbon::parse($rental->return_date)->format('d M Y') : '-' }}
                                </td>
                                <td class="py-3 px-5">
                                    @if ($rental->days_overdue > 0)
                                        <span class="text-xs font-semibold text-red-600">
                                            Telat {{ $rental->days_overdue }} Hari
                                        </span>
                                    @else
                                        <span class="text-darkbrown-400 font-medium">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-5 font-semibold text-darkbrown-900">
                                    Rp {{ number_format($rental->total_late_fee, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-5">
                                    @if ($rental->status === 'picked_up')
                                        <span class="text-xs font-semibold text-amber-700">Belum Kembali</span>
                                    @elseif ($rental->status === 'completed' || $rental->status === 'returned')
                                        <span class="text-xs font-semibold text-avocado-700">Sudah Dikembalikan</span>
                                    @else
                                        <span class="text-xs font-semibold text-darkbrown-500">{{ $rental->status }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-darkbrown-500">
                                    <div class="flex flex-col items-center justify-center space-y-1">
                                        <svg class="w-8 h-8 text-avocado-500 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="font-bold text-xs text-darkbrown-800">Semua pengembalian tepat waktu!</p>
                                        <p class="text-[11px] text-darkbrown-500">Tidak ada transaksi sewa yang sedang terlambat saat ini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-layouts.vendor>
