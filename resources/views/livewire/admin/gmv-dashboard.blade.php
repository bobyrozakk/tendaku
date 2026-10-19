<div class="space-y-6">
    <!-- Header & Filter Bar -->
    <div class="bg-white rounded-2xl p-5 border border-wheat-300 shadow-sm flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-goldenrod-100 text-darkbrown-900 border border-goldenrod-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-goldenrod-600"></span>
                    Sistem Multi-Tenant
                </span>
                <span class="text-xs text-darkbrown-500">• Metrik Finansial Realtime</span>
            </div>
            <h1 class="text-2xl font-black text-darkbrown-900 leading-tight">Dashboard Gross Merchandise Value (GMV)</h1>
            <p class="text-xs text-darkbrown-600 mt-0.5">Pemantauan transaksi terkonfirmasi, omzet sewa harian/bulanan, dan performa vendor.</p>
        </div>

        <!-- Filter Controls -->
        <div class="flex flex-wrap items-center gap-3">
            <!-- Filter Periode -->
            <div class="flex items-center gap-2">
                <label for="period" class="text-xs font-bold text-darkbrown-700 whitespace-nowrap">Periode:</label>
                <select id="period" wire:model.live="period"
                        class="text-xs rounded-xl border-wheat-300 bg-wheat-50/50 text-darkbrown-800 font-semibold focus:border-goldenrod-500 focus:ring-goldenrod-500 py-2 px-3 shadow-sm">
                    <option value="today">Hari Ini</option>
                    <option value="yesterday">Kemarin</option>
                    <option value="7_days">7 Hari Terakhir</option>
                    <option value="30_days">30 Hari Terakhir</option>
                    <option value="this_month">Bulan Ini</option>
                    <option value="last_month">Bulan Lalu</option>
                    <option value="all_time">Sepanjang Waktu</option>
                    <option value="custom">Rentang Kustom</option>
                </select>
            </div>

            <!-- Filter Vendor -->
            <div class="flex items-center gap-2">
                <label for="selectedVendorId" class="text-xs font-bold text-darkbrown-700 whitespace-nowrap">Vendor:</label>
                <select id="selectedVendorId" wire:model.live="selectedVendorId"
                        class="text-xs rounded-xl border-wheat-300 bg-wheat-50/50 text-darkbrown-800 font-semibold focus:border-goldenrod-500 focus:ring-goldenrod-500 py-2 px-3 shadow-sm max-w-[180px]">
                    <option value="">Semua Vendor (Lintas Toko)</option>
                    @foreach ($allVendors as $v)
                        <option value="{{ $v->id }}">{{ $v->name }} ({{ ucfirst($v->status) }})</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if ($period === 'custom')
        <!-- Date Range Inputs -->
        <div class="bg-amber-50/60 border border-amber-200/80 rounded-2xl p-4 flex flex-wrap items-center gap-4">
            <span class="text-xs font-bold text-darkbrown-800">Tentukan Rentang Tanggal:</span>
            <div class="flex items-center gap-2">
                <span class="text-xs text-darkbrown-600">Dari:</span>
                <input type="date" wire:model.live="startDate"
                       class="text-xs rounded-xl border-wheat-300 bg-white font-semibold text-darkbrown-800 focus:border-goldenrod-500 focus:ring-goldenrod-500 py-1.5 px-3">
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-darkbrown-600">Sampai:</span>
                <input type="date" wire:model.live="endDate"
                       class="text-xs rounded-xl border-wheat-300 bg-white font-semibold text-darkbrown-800 focus:border-goldenrod-500 focus:ring-goldenrod-500 py-1.5 px-3">
            </div>
        </div>
    @endif

    <!-- Alert Banner jika ada Vendor Pending Approval -->
    @if ($pendingVendorsCount > 0)
        <div class="bg-gradient-to-r from-amber-500/10 via-goldenrod-500/15 to-transparent border border-goldenrod-400/50 rounded-2xl p-4 flex items-center justify-between gap-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-goldenrod-500 text-darkbrown-900 flex items-center justify-center font-bold text-lg shrink-0 shadow-sm">
                    ⚠️
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-darkbrown-900 leading-tight">
                        Ada {{ $pendingVendorsCount }} Pengajuan Vendor Menunggu Persetujuan!
                    </h3>
                    <p class="text-xs text-darkbrown-600 mt-0.5">
                        Toko baru yang mendaftar tidak dapat beroperasi hingga Super Admin menyetujui akun mereka.
                    </p>
                </div>
            </div>
            <a href="{{ route('admin.vendors.index') }}"
               class="px-4 py-2 rounded-xl text-xs font-extrabold bg-goldenrod-500 hover:bg-goldenrod-600 text-darkbrown-950 transition shadow-sm whitespace-nowrap">
                Tinjau Pengajuan ➔
            </a>
        </div>
    @endif

    <!-- 4 Kartu Metrik Utama -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. GMV Hari Ini -->
        <div class="bg-white rounded-2xl p-5 border border-wheat-300 shadow-sm flex flex-col justify-between relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-avocado-100/50 to-transparent rounded-bl-full pointer-events-none"></div>
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-darkbrown-500 uppercase tracking-wider">GMV Hari Ini</span>
                    <span class="inline-flex items-center gap-1 text-[11px] font-extrabold px-2 py-0.5 rounded-full {{ $dailyGrowth >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                        {{ $dailyGrowth >= 0 ? '▲ +' : '▼ ' }}{{ $dailyGrowth }}%
                    </span>
                </div>
                <div class="text-2xl font-black text-darkbrown-950 tracking-tight">
                    Rp {{ number_format($gmvToday, 0, ',', '.') }}
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-wheat-200/80 flex items-center justify-between text-[11px] text-darkbrown-500">
                <span>Vs. kemarin</span>
                <span class="font-bold text-darkbrown-700">Per 24 jam terakhir</span>
            </div>
        </div>

        <!-- 2. GMV Bulan Ini -->
        <div class="bg-white rounded-2xl p-5 border border-wheat-300 shadow-sm flex flex-col justify-between relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-goldenrod-100/50 to-transparent rounded-bl-full pointer-events-none"></div>
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-darkbrown-500 uppercase tracking-wider">GMV Bulan Ini</span>
                    <span class="inline-flex items-center gap-1 text-[11px] font-extrabold px-2 py-0.5 rounded-full {{ $monthlyGrowth >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                        {{ $monthlyGrowth >= 0 ? '▲ +' : '▼ ' }}{{ $monthlyGrowth }}%
                    </span>
                </div>
                <div class="text-2xl font-black text-darkbrown-950 tracking-tight">
                    Rp {{ number_format($gmvThisMonth, 0, ',', '.') }}
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-wheat-200/80 flex items-center justify-between text-[11px] text-darkbrown-500">
                <span>Vs. bulan lalu</span>
                <span class="font-bold text-darkbrown-700">{{ now()->isoFormat('MMMM Y') }}</span>
            </div>
        </div>

        <!-- 3. GMV Terpilih -->
        <div class="bg-white rounded-2xl p-5 border border-wheat-300 shadow-sm flex flex-col justify-between relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-amber-100/50 to-transparent rounded-bl-full pointer-events-none"></div>
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-darkbrown-500 uppercase tracking-wider">Total Periode Ini</span>
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-wheat-100 text-darkbrown-700">
                        {{ $filteredTransactionCount }} Transaksi
                    </span>
                </div>
                <div class="text-2xl font-black text-darkbrown-950 tracking-tight text-goldenrod-700">
                    Rp {{ number_format($filteredGmv, 0, ',', '.') }}
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-wheat-200/80 flex items-center justify-between text-[11px] text-darkbrown-500">
                <span>Rentang aktif</span>
                <span class="font-bold text-darkbrown-700 truncate max-w-[120px]">
                    {{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d M') : 'Awal' }} - {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d M Y') : 'Sekarang' }}
                </span>
            </div>
        </div>

        <!-- 4. Total Mitra Vendor -->
        <div class="bg-white rounded-2xl p-5 border border-wheat-300 shadow-sm flex flex-col justify-between relative overflow-hidden">
            <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-darkbrown-100/50 to-transparent rounded-bl-full pointer-events-none"></div>
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-darkbrown-500 uppercase tracking-wider">Mitra Vendor</span>
                    <span class="text-[11px] font-extrabold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                        {{ $activeVendorsCount }} Aktif
                    </span>
                </div>
                <div class="text-2xl font-black text-darkbrown-950 tracking-tight">
                    {{ $totalVendors }} <span class="text-sm font-semibold text-darkbrown-500">Toko</span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-wheat-200/80 flex items-center justify-between text-[11px] text-darkbrown-500">
                <span>Pending Approval</span>
                <span class="font-extrabold {{ $pendingVendorsCount > 0 ? 'text-amber-600' : 'text-darkbrown-700' }}">
                    {{ $pendingVendorsCount }} Toko
                </span>
            </div>
        </div>
    </div>

    <!-- Visualizer Tren Harian (7 Hari Terakhir) -->
    <div class="bg-white rounded-2xl p-6 border border-wheat-300 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-extrabold text-darkbrown-900">Aktivitas Tren GMV Harian</h2>
                <p class="text-xs text-darkbrown-500">Pergerakan total transaksi sewa 7 hari terakhir (Batas tengah malam).</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-wheat-100 text-darkbrown-700">
                Status: Confirmed s.d Completed
            </span>
        </div>

        @php
            $maxTrendGmv = max(array_column($dailyTrend, 'gmv')) ?: 1;
        @endphp

        <div class="grid grid-cols-7 gap-2 sm:gap-4 items-end pt-6 pb-2 border-b border-wheat-200">
            @foreach ($dailyTrend as $day)
                @php
                    $barHeightPercent = max(8, round(($day['gmv'] / $maxTrendGmv) * 100));
                @endphp
                <div class="flex flex-col items-center gap-2 group">
                    <div class="text-[10px] sm:text-xs font-bold text-darkbrown-800 opacity-0 group-hover:opacity-100 transition whitespace-nowrap">
                        Rp {{ number_format($day['gmv'] / 1000, 0) }}k
                    </div>
                    <div class="w-full max-w-[48px] bg-wheat-100 rounded-t-xl h-36 flex items-end p-1 relative">
                        <div class="w-full bg-gradient-to-t from-darkbrown-800 via-goldenrod-600 to-goldenrod-400 rounded-t-lg transition-all duration-300 group-hover:from-darkbrown-900 group-hover:to-goldenrod-300"
                             style="height: {{ $barHeightPercent }}%;">
                        </div>
                    </div>
                    <div class="text-[10px] sm:text-xs font-semibold text-darkbrown-600 text-center">
                        <div>{{ $day['label'] }}</div>
                        <div class="text-[10px] text-darkbrown-400 font-normal">{{ $day['count'] }} sewa</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Tabel Kontribusi GMV Per Vendor -->
    <div class="bg-white rounded-2xl border border-wheat-300 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-wheat-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-base font-extrabold text-darkbrown-900">Peringkat & Kontribusi Omzet Vendor</h2>
                <p class="text-xs text-darkbrown-500">Daftar performa tenant vendor berdasarkan transaksi sewa yang valid.</p>
            </div>
            <a href="{{ route('admin.vendors.index') }}" class="text-xs font-bold text-goldenrod-700 hover:text-goldenrod-800 transition">
                Kelola Seluruh Vendor ➔
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-wheat-50 border-b border-wheat-200 text-darkbrown-600 uppercase font-bold tracking-wider">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">#</th>
                        <th class="py-3 px-4">Nama Toko Vendor</th>
                        <th class="py-3 px-4">Kota</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Jumlah Transaksi</th>
                        <th class="py-3 px-4">Pangsa Kontribusi</th>
                        <th class="py-3 px-4 text-right">Total GMV (Rp)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-wheat-200 text-darkbrown-800">
                    @forelse ($vendorRankings as $index => $row)
                        <tr class="hover:bg-wheat-50/50 transition">
                            <td class="py-3.5 px-4 text-center font-bold text-darkbrown-500">
                                {{ $index + 1 }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-darkbrown-950">
                                {{ $row->vendor_name }}
                            </td>
                            <td class="py-3.5 px-4 text-darkbrown-600">
                                {{ $row->vendor_city ?: '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if ($row->vendor_status === 'active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        Aktif
                                    </span>
                                @elseif ($row->vendor_status === 'pending')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                        Pending
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                        Ditangguhkan
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center font-semibold">
                                {{ $row->transaction_count }} pesanan
                            </td>
                            <td class="py-3.5 px-4 min-w-[140px]">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-wheat-200 rounded-full h-2 overflow-hidden">
                                        <div class="bg-goldenrod-500 h-2 rounded-full" style="width: {{ $row->percentage }}%;"></div>
                                    </div>
                                    <span class="text-[11px] font-bold text-darkbrown-600 w-10 text-right">{{ $row->percentage }}%</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-right font-extrabold text-sm text-darkbrown-950">
                                Rp {{ number_format($row->total_gmv, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-darkbrown-400">
                                Belum ada transaksi yang memenuhi kriteria GMV pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
