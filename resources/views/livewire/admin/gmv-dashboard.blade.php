<div class="space-y-6" wire:poll.15s>
    <!-- Filter & Ringkasan GMV -->
    <div class="card-tendaku p-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <div class="mb-1 flex flex-wrap items-center gap-2">
                    <x-badge variant="goldenrod" :dot="true">Performa Finansial</x-badge>
                    <span class="text-xs text-darkbrown-400">Transaksi tervalidasi</span>
                </div>
                <h2 class="text-lg font-extrabold tracking-tight text-darkbrown-900">Ringkasan GMV</h2>
                <p class="mt-0.5 text-xs text-darkbrown-500">
                    Pantau omzet sewa dan kontribusi vendor pada periode yang dipilih.
                </p>
            </div>

            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="period" class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">
                        Periode
                    </label>
                    <select id="period" wire:model.live="period"
                            class="rounded-xl border-wheat-300 bg-wheat-50 py-2 pl-3 pr-8 text-xs font-semibold text-darkbrown-800 shadow-sm transition focus:border-goldenrod-500 focus:ring-goldenrod-500">
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

                <div>
                    <label for="selectedVendorId" class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">
                        Vendor
                    </label>
                    <select id="selectedVendorId" wire:model.live="selectedVendorId"
                            class="max-w-[210px] rounded-xl border-wheat-300 bg-wheat-50 py-2 pl-3 pr-8 text-xs font-semibold text-darkbrown-800 shadow-sm transition focus:border-goldenrod-500 focus:ring-goldenrod-500">
                        <option value="">Semua Vendor</option>
                        @foreach ($allVendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        @if ($period === 'custom')
            <div class="mt-4 flex flex-wrap items-end gap-3 rounded-xl border border-wheat-200 bg-wheat-50/70 p-3.5">
                <div>
                    <label for="startDate" class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">
                        Dari Tanggal
                    </label>
                    <input id="startDate" type="date" wire:model.live="startDate"
                           class="rounded-xl border-wheat-300 bg-white py-1.5 pl-3 pr-2 text-xs font-semibold text-darkbrown-800 shadow-sm focus:border-goldenrod-500 focus:ring-goldenrod-500">
                </div>
                <div>
                    <label for="endDate" class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">
                        Sampai Tanggal
                    </label>
                    <input id="endDate" type="date" wire:model.live="endDate"
                           class="rounded-xl border-wheat-300 bg-white py-1.5 pl-3 pr-2 text-xs font-semibold text-darkbrown-800 shadow-sm focus:border-goldenrod-500 focus:ring-goldenrod-500">
                </div>
            </div>
        @endif
    </div>

    @if ($pendingVendorsCount > 0)
        <x-alert type="warning" title="Pengajuan vendor menunggu persetujuan">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm">
                    Ada <strong>{{ $pendingVendorsCount }} vendor</strong> yang belum dapat beroperasi sampai akun mereka disetujui.
                </p>
                <a href="#kelola-vendor" class="btn-tendaku-gold !px-3 !py-1.5 text-xs shrink-0">
                    Tinjau Pengajuan
                </a>
            </div>
        </x-alert>
    @endif

    <!-- Metrik Utama -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card
            title="GMV Hari Ini"
            :value="'Rp ' . number_format($gmvToday, 0, ',', '.')"
            :trend="($dailyGrowth >= 0 ? '+' : '') . $dailyGrowth . '% vs kemarin'"
            :trendUp="$dailyGrowth >= 0"
            :description="$todayTransactionCount . ' transaksi tervalidasi hari ini'"
            color="avocado"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            title="GMV Bulan Ini"
            :value="'Rp ' . number_format($gmvThisMonth, 0, ',', '.')"
            :trend="($monthlyGrowth >= 0 ? '+' : '') . $monthlyGrowth . '% vs bulan lalu'"
            :trendUp="$monthlyGrowth >= 0"
            :description="now()->isoFormat('MMMM Y')"
            color="goldenrod"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            title="Transaksi Periode Ini"
            :value="$filteredTransactionCount . ' Pesanan'"
            :description="'GMV Rp ' . number_format($filteredGmv, 0, ',', '.')"
            color="sunglow"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            title="Mitra Vendor"
            :value="$totalVendors . ' Toko'"
            :trend="$activeVendorsCount . ' aktif'"
            :trendUp="true"
            :description="$pendingVendorsCount . ' menunggu persetujuan'"
            color="dark"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </x-slot>
        </x-stat-card>
    </div>

    <!-- Tren GMV -->
    <x-card title="Tren GMV 7 Hari Terakhir" subtitle="Pergerakan transaksi tervalidasi per hari, termasuk batas tengah malam.">
        <x-slot name="action">
            <x-badge variant="wheat" size="sm">Confirmed s.d. Completed</x-badge>
        </x-slot>

        @php
            $maxTrendGmv = max(array_column($dailyTrend, 'gmv')) ?: 1;
        @endphp

        <div class="grid grid-cols-7 items-end gap-2 border-b border-wheat-200 pb-3 pt-4 sm:gap-4">
            @foreach ($dailyTrend as $day)
                @php
                    $barHeightPercent = max(8, round(($day['gmv'] / $maxTrendGmv) * 100));
                @endphp

                <div class="group flex flex-col items-center gap-2">
                    <div class="whitespace-nowrap text-[10px] font-bold text-darkbrown-700 opacity-0 transition group-hover:opacity-100 sm:text-xs">
                        Rp {{ number_format($day['gmv'] / 1000, 0) }}k
                    </div>
                    <div class="flex h-32 w-full max-w-[52px] items-end rounded-t-xl bg-wheat-100 p-1 sm:h-36">
                        <div class="w-full rounded-t-lg bg-gradient-to-t from-darkbrown-800 via-avocado-600 to-avocado-400 transition-all duration-300 group-hover:from-darkbrown-900 group-hover:to-avocado-300"
                             style="height: {{ $barHeightPercent }}%;"></div>
                    </div>
                    <div class="text-center text-[10px] font-semibold text-darkbrown-600 sm:text-xs">
                        <div>{{ $day['label'] }}</div>
                        <div class="text-[10px] font-normal text-darkbrown-400">{{ $day['count'] }} sewa</div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-card>

    <!-- Peringkat Vendor -->
    <x-card title="Peringkat & Kontribusi Vendor" subtitle="Performa tenant berdasarkan transaksi sewa yang valid pada periode aktif." padding="p-0" class="overflow-hidden">
        <x-slot name="action">
            <a href="#kelola-vendor" class="text-xs font-bold text-avocado-700 transition hover:text-avocado-800">
                Kelola Vendor &darr;
            </a>
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-y border-wheat-200 bg-wheat-50 font-bold uppercase tracking-wider text-darkbrown-600">
                    <tr>
                        <th class="w-12 px-4 py-3 text-center">#</th>
                        <th class="px-4 py-3">Nama Toko</th>
                        <th class="px-4 py-3">Kota</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Transaksi</th>
                        <th class="px-4 py-3">Kontribusi</th>
                        <th class="px-4 py-3 text-right">Total GMV</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-wheat-200 text-darkbrown-800">
                    @forelse ($vendorRankings as $index => $row)
                        <tr class="transition hover:bg-wheat-50/60">
                            <td class="px-4 py-3.5 text-center font-bold text-darkbrown-500">{{ $index + 1 }}</td>
                            <td class="px-4 py-3.5 font-bold text-darkbrown-950">{{ $row->vendor_name }}</td>
                            <td class="px-4 py-3.5 text-darkbrown-600">{{ $row->vendor_city ?: '—' }}</td>
                            <td class="px-4 py-3.5 text-center">
                                @if ($row->vendor_status === 'active')
                                    <x-badge variant="avocado" size="sm" :dot="true">Aktif</x-badge>
                                @elseif ($row->vendor_status === 'pending')
                                    <x-badge variant="sunglow" size="sm" :dot="true">Pending</x-badge>
                                @else
                                    <x-badge variant="danger" size="sm" :dot="true">Ditangguhkan</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center font-semibold">{{ $row->transaction_count }} pesanan</td>
                            <td class="min-w-[150px] px-4 py-3.5">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-wheat-200">
                                        <div class="h-full rounded-full bg-avocado-500" style="width: {{ $row->percentage }}%;"></div>
                                    </div>
                                    <span class="w-10 text-right text-[11px] font-bold text-darkbrown-600">{{ $row->percentage }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-right text-sm font-extrabold text-darkbrown-950">
                                Rp {{ number_format($row->total_gmv, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-darkbrown-400">
                                Belum ada transaksi yang memenuhi kriteria GMV pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
