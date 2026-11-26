@php
    $formatCompactGmv = function (float|int $value): string {
        $value = (float) $value;

        if ($value >= 1_000_000_000) {
            return rtrim(rtrim(number_format($value / 1_000_000_000, 1, ',', '.'), '0'), ',').' M';
        }

        if ($value >= 1_000_000) {
            return rtrim(rtrim(number_format($value / 1_000_000, 1, ',', '.'), '0'), ',').' jt';
        }

        if ($value >= 1_000) {
            return rtrim(rtrim(number_format($value / 1_000, 1, ',', '.'), '0'), ',').' rb';
        }

        return number_format($value, 0, ',', '.');
    };

    $trendTotal = array_sum(array_column($dailyTrend, 'gmv'));
    $trendPeak = max(array_column($dailyTrend, 'gmv'));
    $trendScale = $trendPeak > 0 ? $trendPeak : 1;

    $suspendedVendorsCount = max(0, $totalVendors - $activeVendorsCount - $pendingVendorsCount);
    $activeVendorShare = $totalVendors > 0 ? ($activeVendorsCount / $totalVendors) * 100 : 0;
    $pendingVendorShare = $totalVendors > 0 ? ($pendingVendorsCount / $totalVendors) * 100 : 0;
    $suspendedVendorShare = $totalVendors > 0 ? ($suspendedVendorsCount / $totalVendors) * 100 : 0;
@endphp

<div class="space-y-5" wire:poll.30s.visible>
    <!-- Filter & Ringkasan GMV -->
    <section class="card-tendaku overflow-hidden" aria-labelledby="gmv-filter-heading">
        <div class="flex flex-col gap-3 border-b border-wheat-200 bg-wheat-50/60 px-4 py-3.5 sm:px-5 xl:flex-row xl:items-end xl:justify-between">
            <div class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-goldenrod-100 text-goldenrod-800">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </span>
                <h2 id="gmv-filter-heading" class="text-base font-extrabold tracking-tight text-darkbrown-900">
                    Ringkasan GMV
                </h2>
            </div>

            <div class="flex flex-wrap items-end gap-2.5">
                <label for="period" class="flex min-w-0 flex-col gap-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Periode</span>
                    <select id="period" wire:model.live="period"
                            class="w-full rounded-xl border-wheat-300 bg-white py-1.5 pl-3 pr-8 text-xs font-semibold text-darkbrown-800 shadow-sm transition focus:border-goldenrod-500 focus:ring-goldenrod-500 sm:w-auto">
                        <option value="today">Hari Ini</option>
                        <option value="yesterday">Kemarin</option>
                        <option value="7_days">7 Hari Terakhir</option>
                        <option value="30_days">30 Hari Terakhir</option>
                        <option value="this_month">Bulan Ini</option>
                        <option value="last_month">Bulan Lalu</option>
                        <option value="all_time">Sepanjang Waktu</option>
                        <option value="custom">Rentang Kustom</option>
                    </select>
                </label>

                <label for="selectedVendorId" class="flex min-w-0 flex-col gap-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Vendor</span>
                    <select id="selectedVendorId" wire:model.live="selectedVendorId"
                            class="w-full max-w-[220px] rounded-xl border-wheat-300 bg-white py-1.5 pl-3 pr-8 text-xs font-semibold text-darkbrown-800 shadow-sm transition focus:border-goldenrod-500 focus:ring-goldenrod-500 sm:w-auto">
                        <option value="">Semua Vendor</option>
                        @foreach ($allVendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </label>

                <span wire:loading wire:target="period,selectedVendorId,startDate,endDate"
                      class="inline-flex items-center gap-1.5 rounded-lg border border-goldenrod-200 bg-goldenrod-50 px-2 py-1.5 text-[10px] font-bold text-goldenrod-800"
                      role="status">
                    <svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Memperbarui
                </span>
            </div>
        </div>

        @if ($period === 'custom')
            <div class="flex flex-wrap items-end gap-3 border-b border-wheat-200 bg-white px-4 py-3 sm:px-5">
                <label for="startDate" class="flex flex-col gap-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Dari</span>
                    <input id="startDate" type="date" wire:model.live="startDate"
                           class="rounded-xl border-wheat-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-darkbrown-800 shadow-sm focus:border-goldenrod-500 focus:ring-goldenrod-500">
                </label>
                <label for="endDate" class="flex flex-col gap-1">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Sampai</span>
                    <input id="endDate" type="date" wire:model.live="endDate"
                           class="rounded-xl border-wheat-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-darkbrown-800 shadow-sm focus:border-goldenrod-500 focus:ring-goldenrod-500">
                </label>
            </div>
        @endif
    </section>

    @if ($pendingVendorsCount > 0)
        <div class="flex flex-col gap-2 rounded-xl border border-sunglow-200 bg-sunglow-50 px-4 py-3 text-sm text-sunglow-950 sm:flex-row sm:items-center sm:justify-between">
            <p class="inline-flex items-center gap-2 font-semibold">
                <span class="h-2 w-2 rounded-full bg-sunglow-500" aria-hidden="true"></span>
                {{ $pendingVendorsCount }} vendor menunggu persetujuan
            </p>
            <a href="#kelola-vendor" class="btn-tendaku-gold shrink-0 !px-3 !py-1.5 text-xs">
                Tinjau
            </a>
        </div>
    @endif

    <!-- Metrik Utama -->
    <div class="grid grid-cols-1 gap-4 transition-opacity duration-200 sm:grid-cols-2 xl:grid-cols-4"
         wire:loading.class="opacity-60" wire:target="period,selectedVendorId,startDate,endDate">
        <x-stat-card
            title="GMV Hari Ini"
            :value="'Rp ' . number_format($gmvToday, 0, ',', '.')"
            :description="$todayTransactionCount . ' transaksi · ' . ($dailyGrowth >= 0 ? '+' : '') . $dailyGrowth . '%'"
            color="avocado"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            title="GMV Bulan Ini"
            :value="'Rp ' . number_format($gmvThisMonth, 0, ',', '.')"
            :description="now()->isoFormat('MMMM') . ' · ' . ($monthlyGrowth >= 0 ? '+' : '') . $monthlyGrowth . '%'"
            color="goldenrod"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            title="Transaksi"
            :value="$filteredTransactionCount . ' Pesanan'"
            :description="'GMV Rp ' . number_format($filteredGmv, 0, ',', '.')"
            color="sunglow"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            title="Vendor"
            :value="$totalVendors . ' Toko'"
            :description="$activeVendorsCount . ' aktif · ' . $pendingVendorsCount . ' pending'"
            color="dark"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </x-slot>
        </x-stat-card>
    </div>

    <!-- Tren GMV & Status Vendor -->
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <x-card class="xl:col-span-2" padding="p-5" title="Tren GMV">
            <x-slot name="action">
                <div class="hidden items-center gap-3 text-[10px] font-semibold text-darkbrown-500 sm:flex">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-goldenrod-400" aria-hidden="true"></span>
                        Harian
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-avocado-500" aria-hidden="true"></span>
                        Hari ini
                    </span>
                </div>
            </x-slot>

            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Total 7 hari</p>
                    <p class="font-admin-number text-2xl font-extrabold text-darkbrown-900">Rp {{ number_format($trendTotal, 0, ',', '.') }}</p>
                </div>
                <p class="text-[11px] text-darkbrown-500">
                    Puncak
                    <span class="font-bold text-darkbrown-700">Rp {{ number_format($trendPeak, 0, ',', '.') }}</span>
                </p>
            </div>

            <div class="grid grid-cols-7 items-end gap-1.5 border-t border-wheat-200 pt-4 sm:gap-3" aria-hidden="true">
                @foreach ($dailyTrend as $day)
                    @php
                        $barHeightPercent = min(100, max(8, round(($day['gmv'] / $trendScale) * 100)));
                    @endphp

                    <div class="group flex min-w-0 flex-col items-center gap-1.5">
                        <div class="min-h-[14px] w-full whitespace-nowrap text-center text-[9px] font-bold tabular-nums text-darkbrown-600 sm:text-[10px]"
                             title="Rp {{ number_format($day['gmv'], 0, ',', '.') }} · {{ $day['count'] }} sewa">
                            @if ($day['gmv'] > 0)
                                {{ $formatCompactGmv($day['gmv']) }}
                            @else
                                &nbsp;
                            @endif
                        </div>
                        <div class="flex h-28 w-full max-w-[54px] items-end rounded-xl bg-wheat-100 p-1 sm:h-40">
                            <div class="w-full rounded-lg bg-gradient-to-t transition-all duration-300 {{ $loop->last ? 'from-avocado-700 via-avocado-600 to-avocado-400 group-hover:to-avocado-300' : 'from-goldenrod-500 via-goldenrod-400 to-sunglow-300 group-hover:from-goldenrod-600 group-hover:to-sunglow-400' }}"
                                 style="height: {{ $barHeightPercent }}%;"></div>
                        </div>
                        <div class="w-full truncate text-center text-[10px] font-semibold leading-tight {{ $loop->last ? 'text-avocado-800' : 'text-darkbrown-600' }}">
                            {{ $day['label'] }}
                        </div>
                    </div>
                @endforeach
            </div>

            <table class="sr-only">
                <caption>Tren GMV tujuh hari terakhir</caption>
                <thead>
                    <tr>
                        <th scope="col">Hari</th>
                        <th scope="col">GMV</th>
                        <th scope="col">Jumlah sewa</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dailyTrend as $day)
                        <tr>
                            <th scope="row">{{ $day['label'] }}</th>
                            <td>Rp {{ number_format($day['gmv'], 0, ',', '.') }}</td>
                            <td>{{ $day['count'] }} sewa</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-card>

        <x-card padding="p-5" title="Status Vendor">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-admin-number text-3xl font-extrabold text-darkbrown-900">{{ number_format($totalVendors, 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-darkbrown-500">Toko terdaftar</p>
                </div>
                <x-badge :variant="$activeVendorShare >= 60 ? 'avocado' : 'goldenrod'" size="sm" :dot="true">
                    {{ round($activeVendorShare) }}% aktif
                </x-badge>
            </div>

            @if ($totalVendors > 0)
                <div class="mt-4 flex h-2.5 overflow-hidden rounded-full bg-wheat-200"
                     role="img"
                     aria-label="Komposisi vendor: {{ $activeVendorsCount }} aktif, {{ $pendingVendorsCount }} menunggu persetujuan, {{ $suspendedVendorsCount }} ditangguhkan">
                    @if ($activeVendorsCount > 0)
                        <div class="bg-avocado-500" style="width: {{ $activeVendorShare }}%"></div>
                    @endif
                    @if ($pendingVendorsCount > 0)
                        <div class="bg-sunglow-400" style="width: {{ $pendingVendorShare }}%"></div>
                    @endif
                    @if ($suspendedVendorsCount > 0)
                        <div class="bg-darkbrown-400" style="width: {{ $suspendedVendorShare }}%"></div>
                    @endif
                </div>
            @else
                <div class="mt-4 h-2.5 rounded-full bg-wheat-200" aria-hidden="true"></div>
            @endif

            <ul class="mt-4 space-y-2.5 text-xs">
                <li class="flex items-center justify-between gap-3">
                    <span class="inline-flex items-center gap-2 font-semibold text-darkbrown-700">
                        <span class="h-2 w-2 rounded-full bg-avocado-500" aria-hidden="true"></span>
                        Aktif
                    </span>
                    <span class="font-extrabold tabular-nums text-darkbrown-900">{{ $activeVendorsCount }}</span>
                </li>
                <li class="flex items-center justify-between gap-3">
                    <span class="inline-flex items-center gap-2 font-semibold text-darkbrown-700">
                        <span class="h-2 w-2 rounded-full bg-sunglow-400" aria-hidden="true"></span>
                        Menunggu
                    </span>
                    <span class="font-extrabold tabular-nums text-darkbrown-900">{{ $pendingVendorsCount }}</span>
                </li>
                <li class="flex items-center justify-between gap-3">
                    <span class="inline-flex items-center gap-2 font-semibold text-darkbrown-700">
                        <span class="h-2 w-2 rounded-full bg-darkbrown-400" aria-hidden="true"></span>
                        Ditangguhkan
                    </span>
                    <span class="font-extrabold tabular-nums text-darkbrown-900">{{ $suspendedVendorsCount }}</span>
                </li>
            </ul>

            <a href="#kelola-vendor"
               class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2 text-xs font-bold text-darkbrown-700 transition hover:border-avocado-300 hover:bg-avocado-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2">
                Kelola vendor
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
            </a>
        </x-card>
    </div>

    <!-- Kontribusi Vendor (sekunder, dapat dilipat) -->
    <x-card padding="p-0" class="overflow-hidden">
        <details class="group">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 transition hover:bg-wheat-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-avocado-500 [&::-webkit-details-marker]:hidden">
                <div>
                    <h3 class="text-sm font-extrabold tracking-tight text-darkbrown-900 sm:text-base">Kontribusi Vendor</h3>
                    <p class="text-[11px] text-darkbrown-500">Peringkat GMV periode aktif</p>
                </div>
                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-avocado-700">
                    <span class="group-open:hidden">Buka</span>
                    <span class="hidden group-open:inline">Tutup</span>
                    <svg class="h-3.5 w-3.5 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </span>
            </summary>

            <div class="overflow-x-auto border-t border-wheat-200">
                <table class="w-full min-w-[640px] text-left text-xs">
                    <caption class="sr-only">Peringkat vendor berdasarkan GMV pada periode aktif</caption>
                    <thead class="border-b border-wheat-200 bg-wheat-50 font-bold uppercase tracking-wider text-darkbrown-600">
                        <tr>
                            <th scope="col" class="w-12 px-4 py-3 text-center">#</th>
                            <th scope="col" class="px-4 py-3">Vendor</th>
                            <th scope="col" class="px-4 py-3 text-center">Transaksi</th>
                            <th scope="col" class="px-4 py-3">Kontribusi</th>
                            <th scope="col" class="px-4 py-3 text-right">GMV</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-wheat-200 text-darkbrown-800">
                        @forelse ($vendorRankings as $index => $row)
                            <tr class="transition hover:bg-wheat-50/60">
                                <td class="px-4 py-3.5">
                                    <span class="mx-auto flex h-7 w-7 items-center justify-center rounded-lg text-[11px] font-extrabold {{ $index === 0 ? 'bg-sunglow-200 text-darkbrown-900' : ($index === 1 ? 'bg-wheat-200 text-darkbrown-800' : ($index === 2 ? 'bg-goldenrod-100 text-goldenrod-800' : 'bg-wheat-50 text-darkbrown-500')) }}">
                                        {{ $index + 1 }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <p class="max-w-[240px] truncate font-bold text-darkbrown-950" title="{{ $row->vendor_name }}">{{ $row->vendor_name }}</p>
                                    <p class="text-[11px] text-darkbrown-500">{{ $row->vendor_city ?: '—' }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-center font-semibold">{{ $row->transaction_count }}</td>
                                <td class="min-w-[150px] px-4 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-wheat-200"
                                             role="progressbar"
                                             aria-valuenow="{{ $row->percentage }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100"
                                             aria-label="Kontribusi {{ $row->vendor_name }} sebesar {{ $row->percentage }} persen">
                                            <div class="h-full rounded-full bg-gradient-to-r from-goldenrod-500 to-sunglow-400" style="width: {{ $row->percentage }}%;"></div>
                                        </div>
                                        <span class="w-10 text-right text-[11px] font-bold tabular-nums text-darkbrown-600">{{ $row->percentage }}%</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right text-sm font-extrabold tabular-nums text-darkbrown-950">
                                    Rp {{ number_format($row->total_gmv, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center">
                                    <p class="text-sm font-bold text-darkbrown-700">Belum ada transaksi.</p>
                                    <button type="button"
                                            wire:click="$set('period', 'this_month')"
                                            class="btn-tendaku-secondary mt-3 !px-3.5 !py-2 text-xs">
                                        Tampilkan bulan ini
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </details>
    </x-card>
</div>
