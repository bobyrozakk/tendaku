@php
    $current = $summary['current'];
    $delta = $summary['delta'];
    $chartMax = max(array_column($chart['points'], 'value')) ?: 1;
    $chartCount = count($chart['points']);
    $chartTabs = [
        'daily' => 'Harian',
        'weekly' => 'Mingguan',
        'monthly' => 'Bulanan',
        'yearly' => 'Tahunan',
    ];
@endphp

<div class="space-y-6">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .card-tendaku, .rounded-2xl { box-shadow: none !important; }
        }
    </style>

    <!-- Filter -->
    <div class="no-print card-tendaku p-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <div class="mb-1 flex flex-wrap items-center gap-2">
                    <x-badge variant="goldenrod" :dot="true">Filter Laporan</x-badge>
                    <span class="text-xs text-darkbrown-400">Semua angka mengikuti filter di bawah</span>
                </div>
                <h2 class="text-lg font-extrabold tracking-tight text-darkbrown-900">Pendapatan Platform</h2>
                <p class="mt-0.5 text-xs text-darkbrown-500">
                    Pilih vendor, periode, metode, status escrow, dan tipe pembayaran.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="resetFilters"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2 text-xs font-semibold text-darkbrown-700 transition hover:border-goldenrod-300 hover:bg-goldenrod-50">
                    Reset
                </button>
                <a href="{{ $exportUrl }}"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-avocado-600 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-avocado-700">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export CSV
                </a>
                <button type="button" onclick="window.print()"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-darkbrown-700 bg-darkbrown-800 px-3 py-2 text-xs font-bold text-wheat-100 transition hover:bg-darkbrown-900">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zM7 9V5a2 2 0 012-2h6a2 2 0 012 2v4" />
                    </svg>
                    Print
                </button>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div>
                <label for="period" class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Periode</label>
                <select id="period" wire:model.live="period"
                        class="w-full rounded-xl border-wheat-300 bg-wheat-50 py-2 pl-3 pr-8 text-xs font-semibold text-darkbrown-800 shadow-sm transition focus:border-goldenrod-500 focus:ring-goldenrod-500">
                    <option value="today">Hari Ini</option>
                    <option value="yesterday">Kemarin</option>
                    <option value="7_days">7 Hari Terakhir</option>
                    <option value="30_days">30 Hari Terakhir</option>
                    <option value="this_month">Bulan Ini</option>
                    <option value="last_month">Bulan Lalu</option>
                    <option value="this_year">Tahun Ini</option>
                    <option value="all_time">Sepanjang Waktu</option>
                    <option value="custom">Rentang Kustom</option>
                </select>
            </div>

            <div>
                <label for="selectedVendorId" class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Vendor</label>
                <select id="selectedVendorId" wire:model.live="selectedVendorId"
                        class="w-full rounded-xl border-wheat-300 bg-wheat-50 py-2 pl-3 pr-8 text-xs font-semibold text-darkbrown-800 shadow-sm transition focus:border-goldenrod-500 focus:ring-goldenrod-500">
                    <option value="">Semua Vendor</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="paymentMethod" class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Metode</label>
                <select id="paymentMethod" wire:model.live="paymentMethod"
                        class="w-full rounded-xl border-wheat-300 bg-wheat-50 py-2 pl-3 pr-8 text-xs font-semibold text-darkbrown-800 shadow-sm transition focus:border-goldenrod-500 focus:ring-goldenrod-500">
                    <option value="">Semua Metode</option>
                    <option value="qris">QRIS</option>
                    <option value="cash">Tunai</option>
                    <option value="transfer">Transfer</option>
                    <option value="midtrans">Midtrans (QRIS/VA)</option>
                </select>
            </div>

            <div>
                <label for="escrowStatus" class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Status Escrow</label>
                <select id="escrowStatus" wire:model.live="escrowStatus"
                        class="w-full rounded-xl border-wheat-300 bg-wheat-50 py-2 pl-3 pr-8 text-xs font-semibold text-darkbrown-800 shadow-sm transition focus:border-goldenrod-500 focus:ring-goldenrod-500">
                    <option value="">Semua Status</option>
                    <option value="held">Ditahan</option>
                    <option value="released">Dicairkan</option>
                    <option value="refunded">Dikembalikan</option>
                    <option value="void">Void</option>
                    <option value="not_applicable">Tidak berlaku</option>
                </select>
            </div>

            <div>
                <label for="paymentType" class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Tipe Pembayaran</label>
                <select id="paymentType" wire:model.live="paymentType"
                        class="w-full rounded-xl border-wheat-300 bg-wheat-50 py-2 pl-3 pr-8 text-xs font-semibold text-darkbrown-800 shadow-sm transition focus:border-goldenrod-500 focus:ring-goldenrod-500">
                    <option value="">Semua Tipe</option>
                    <option value="dp">DP</option>
                    <option value="settlement">Pelunasan</option>
                    <option value="fine">Denda</option>
                    <option value="deposit">Deposit</option>
                    <option value="deposit_refund">Refund Deposit</option>
                    <option value="refund">Refund</option>
                </select>
            </div>
        </div>

        @if ($period === 'custom')
            <div class="mt-3 flex flex-wrap items-end gap-3 rounded-xl border border-wheat-200 bg-wheat-50/70 p-3.5">
                <div>
                    <label for="startDate" class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Dari Tanggal</label>
                    <input id="startDate" type="date" wire:model.live="startDate"
                           class="rounded-xl border-wheat-300 bg-white py-1.5 pl-3 pr-2 text-xs font-semibold text-darkbrown-800 shadow-sm focus:border-goldenrod-500 focus:ring-goldenrod-500">
                </div>
                <div>
                    <label for="endDate" class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Sampai Tanggal</label>
                    <input id="endDate" type="date" wire:model.live="endDate"
                           class="rounded-xl border-wheat-300 bg-white py-1.5 pl-3 pr-2 text-xs font-semibold text-darkbrown-800 shadow-sm focus:border-goldenrod-500 focus:ring-goldenrod-500">
                </div>
            </div>
        @endif
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card
            title="Komisi Hari Ini"
            :value="'Rp ' . number_format($kpis['commission_today'], 0, ',', '.')"
            :trend="($kpis['commission_today_delta'] >= 0 ? '+' : '') . $kpis['commission_today_delta'] . '% vs kemarin'"
            :trendUp="$kpis['commission_today_delta'] >= 0"
            description="Akrual komisi DP/pelunasan hari ini"
            color="avocado"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            title="Komisi 7 Hari"
            :value="'Rp ' . number_format($kpis['commission_7_days'], 0, ',', '.')"
            :trend="($kpis['commission_7_days_delta'] >= 0 ? '+' : '') . $kpis['commission_7_days_delta'] . '% vs 7 hari sebelumnya'"
            :trendUp="$kpis['commission_7_days_delta'] >= 0"
            description="Akumulasi komisi tujuh hari terakhir"
            color="goldenrod"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            title="Komisi Bulan Ini"
            :value="'Rp ' . number_format($kpis['commission_this_month'], 0, ',', '.')"
            :trend="($kpis['commission_this_month_delta'] >= 0 ? '+' : '') . $kpis['commission_this_month_delta'] . '% vs bulan lalu'"
            :trendUp="$kpis['commission_this_month_delta'] >= 0"
            :description="now()->isoFormat('MMMM Y')"
            color="sunglow"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            title="Komisi Tahun Ini"
            :value="'Rp ' . number_format($kpis['commission_this_year'], 0, ',', '.')"
            :trend="($kpis['commission_this_year_delta'] >= 0 ? '+' : '') . $kpis['commission_this_year_delta'] . '% vs tahun lalu'"
            :trendUp="$kpis['commission_this_year_delta'] >= 0"
            :description="now()->format('Y')"
            color="dark"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            title="Escrow Ditahan"
            :value="'Rp ' . number_format($kpis['escrow_held_total'], 0, ',', '.')"
            description="Dana QRIS yang masih ditahan (semua periode)"
            color="goldenrod"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            title="Piutang Komisi Tunai"
            :value="'Rp ' . number_format($kpis['cash_receivable'], 0, ',', '.')"
            description="Komisi tunai accrued yang belum di-net-off"
            color="sunglow"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            title="Laba Platform (Periode)"
            :value="'Rp ' . number_format($current['platform_net'], 0, ',', '.')"
            :trend="($delta['platform_net'] >= 0 ? '+' : '') . $delta['platform_net'] . '% vs periode sebelumnya'"
            :trendUp="$delta['platform_net'] >= 0"
            description="Komisi - MDR standar - estimasi biaya transfer"
            color="avocado"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
            </x-slot>
        </x-stat-card>

        <x-stat-card
            title="Batch Menunggu Approval"
            :value="$kpis['pending_batches'] . ' Batch'"
            description="Payout bulan ini Rp 0 · modul batch settlement NTN-67"
            color="dark"
        >
            <x-slot name="icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
            </x-slot>
        </x-stat-card>
    </div>

    <!-- Ringkasan periode terpilih -->
    <x-card title="Ringkasan Periode Terpilih" subtitle="Angka mengikuti seluruh filter aktif, termasuk metode, escrow, dan tipe pembayaran.">
        <x-slot name="action">
            <x-badge variant="wheat" size="sm">
                {{ $summary['range']['start']?->isoFormat('D MMM Y') ?? 'Awal' }}
                –
                {{ $summary['range']['end']?->isoFormat('D MMM Y') ?? 'Sekarang' }}
            </x-badge>
        </x-slot>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
            <div class="rounded-xl border border-wheat-200 bg-wheat-50/60 p-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Pendapatan Kotor</p>
                <p class="mt-1 text-lg font-extrabold text-darkbrown-900">Rp {{ number_format($current['gross_revenue'], 0, ',', '.') }}</p>
                <p class="text-[10px] text-darkbrown-400">Tanpa denda</p>
            </div>
            <div class="rounded-xl border border-wheat-200 bg-wheat-50/60 p-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Komisi</p>
                <p class="mt-1 text-lg font-extrabold text-avocado-700">Rp {{ number_format($current['commission'], 0, ',', '.') }}</p>
                <p class="text-[10px] text-darkbrown-400">{{ $current['transaction_count'] }} transaksi</p>
            </div>
            <div class="rounded-xl border border-wheat-200 bg-wheat-50/60 p-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">MDR Standar</p>
                <p class="mt-1 text-lg font-extrabold text-goldenrod-700">Rp {{ number_format($current['gateway_fee_total'], 0, ',', '.') }}</p>
                <p class="text-[10px] text-darkbrown-400">Beban platform</p>
            </div>
            <div class="rounded-xl border border-wheat-200 bg-wheat-50/60 p-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Escrow Ditahan</p>
                <p class="mt-1 text-lg font-extrabold text-darkbrown-900">Rp {{ number_format($current['escrow_held'], 0, ',', '.') }}</p>
                <p class="text-[10px] text-darkbrown-400">Sesuai filter</p>
            </div>
            <div class="rounded-xl border border-wheat-200 bg-wheat-50/60 p-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Laba Platform</p>
                <p class="mt-1 text-lg font-extrabold {{ $current['platform_net'] < 0 ? 'text-red-600' : 'text-darkbrown-900' }}">
                    Rp {{ number_format($current['platform_net'], 0, ',', '.') }}
                </p>
                <p class="text-[10px] text-darkbrown-400">Transfer Rp {{ number_format($current['transfer_fee_total'], 0, ',', '.') }}</p>
            </div>
        </div>

        <p class="mt-3 text-[11px] text-darkbrown-400">
            Laba platform = komisi - MDR standar - (biaya transfer {{ 'Rp ' . number_format($current['transfer_fee'], 0, ',', '.') }} × {{ $current['vendor_count'] }} vendor). Batch settlement final mengikuti NTN-67.
        </p>
    </x-card>

    <!-- Chart -->
    <x-card title="Tren Komisi Platform" subtitle="Komisi diakui berdasarkan waktu pembayaran. Chart mengikuti filter vendor/metode/tipe, bukan periode tabel.">
        <x-slot name="action">
            <div class="no-print flex flex-wrap gap-1 rounded-xl bg-wheat-100 p-1">
                @foreach ($chartTabs as $tabKey => $tabLabel)
                    <button type="button" wire:click="$set('chartTab', '{{ $tabKey }}')"
                            class="rounded-lg px-2.5 py-1.5 text-[11px] font-bold transition {{ $chartTab === $tabKey ? 'bg-white text-darkbrown-900 shadow-sm' : 'text-darkbrown-500 hover:text-darkbrown-800' }}">
                        {{ $tabLabel }}
                    </button>
                @endforeach
            </div>
        </x-slot>

        <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-500">Total Komisi Chart</p>
                <p class="text-2xl font-extrabold text-darkbrown-900">Rp {{ number_format($chart['total'], 0, ',', '.') }}</p>
            </div>
            <p class="text-[11px] text-darkbrown-400">
                {{ $chart['start']->isoFormat('D MMM Y') }} – {{ $chart['end']->isoFormat('D MMM Y') }}
            </p>
        </div>

        <div class="overflow-x-auto pb-2">
            <div class="flex min-w-[680px] items-end gap-2 border-b border-wheat-200 pb-3 pt-4 sm:gap-3"
                 style="display: grid; grid-template-columns: repeat({{ $chartCount }}, minmax(0, 1fr));">
                @foreach ($chart['points'] as $point)
                    @php
                        $barHeightPercent = max(6, round(($point['value'] / $chartMax) * 100));
                    @endphp

                    <div class="group flex min-w-0 flex-col items-center gap-2">
                        <div class="whitespace-nowrap text-[9px] font-bold text-darkbrown-700 opacity-0 transition group-hover:opacity-100 sm:text-[10px]"
                             title="Rp {{ number_format($point['value'], 0, ',', '.') }} · {{ $point['count'] }} transaksi">
                            Rp {{ number_format($point['value'] / 1000, 0) }}k
                        </div>
                        <div class="flex h-28 w-full max-w-[52px] items-end rounded-t-xl bg-wheat-100 p-1 sm:h-36">
                            <div class="w-full rounded-t-lg bg-gradient-to-t from-darkbrown-800 via-avocado-600 to-avocado-400 transition-all duration-300 group-hover:from-darkbrown-900 group-hover:to-avocado-300"
                                 style="height: {{ $barHeightPercent }}%;"
                                 title="Rp {{ number_format($point['value'], 0, ',', '.') }} · {{ $point['count'] }} transaksi"></div>
                        </div>
                        <div class="w-full text-center text-[9px] font-semibold leading-tight text-darkbrown-600 sm:text-[10px]">
                            <div class="truncate">
                                {{ ($chartTab === 'daily' && $loop->index % 3 !== 0 && ! $loop->last) ? '' : $point['label'] }}
                            </div>
                            <div class="text-[9px] font-normal text-darkbrown-400">{{ $point['count'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-card>

    <!-- Ringkasan per vendor -->
    <x-card title="Ringkasan per Vendor" subtitle="GMV dari rental valid; komisi, escrow, dan payout mengikuti filter transaksi." padding="p-0" class="overflow-hidden">
        <x-slot name="action">
            <x-badge variant="wheat" size="sm">Payout masih estimasi</x-badge>
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-y border-wheat-200 bg-wheat-50 font-bold uppercase tracking-wider text-darkbrown-600">
                    <tr>
                        <th class="px-4 py-3">Vendor</th>
                        <th class="px-4 py-3 text-right">GMV</th>
                        <th class="px-4 py-3">Komisi</th>
                        <th class="px-4 py-3 text-right">Escrow</th>
                        <th class="px-4 py-3 text-right">Payout (estimasi)</th>
                        <th class="px-4 py-3 text-right">Piutang Tunai</th>
                        <th class="px-4 py-3 text-center">Transaksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-wheat-200 text-darkbrown-800">
                    @forelse ($vendorSummaries as $row)
                        <tr class="transition hover:bg-wheat-50/60">
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-avocado-100 text-[10px] font-extrabold text-avocado-700">
                                        {{ mb_substr($row['vendor_name'], 0, 2) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-bold text-darkbrown-950">{{ $row['vendor_name'] }}</p>
                                        <p class="text-[10px] text-darkbrown-400">{{ $row['vendor_city'] ?: '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-right font-semibold">Rp {{ number_format($row['gmv'], 0, ',', '.') }}</td>
                            <td class="min-w-[150px] px-4 py-3.5">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-wheat-200">
                                        <div class="h-full rounded-full bg-avocado-500" style="width: {{ $row['commission_percentage'] }}%;"></div>
                                    </div>
                                    <span class="w-20 text-right text-[11px] font-bold text-darkbrown-700">Rp {{ number_format($row['commission_total'], 0, ',', '.') }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-right font-semibold">Rp {{ number_format($row['escrow_held'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right font-extrabold {{ $row['payout_estimate'] < 0 ? 'text-red-600' : 'text-darkbrown-950' }}">
                                Rp {{ number_format($row['payout_estimate'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                @if ($row['cash_receivable'] > 0)
                                    <x-badge variant="danger" size="sm">Rp {{ number_format($row['cash_receivable'], 0, ',', '.') }}</x-badge>
                                @else
                                    <span class="text-darkbrown-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center font-semibold">{{ $row['transaction_count'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-darkbrown-400">
                                Belum ada transaksi paid pada filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Transaksi -->
    <x-card title="Transaksi Pendapatan" subtitle="50 transaksi terbaru sesuai filter. Export CSV memuat seluruh baris, bukan hanya yang tampil." padding="p-0" class="overflow-hidden">
        <x-slot name="action">
            <x-badge variant="wheat" size="sm">{{ $current['transaction_count'] }} total</x-badge>
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-y border-wheat-200 bg-wheat-50 font-bold uppercase tracking-wider text-darkbrown-600">
                    <tr>
                        <th class="px-4 py-3">Kode Booking</th>
                        <th class="px-4 py-3">Vendor</th>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3">Metode</th>
                        <th class="px-4 py-3 text-right">Nominal</th>
                        <th class="px-4 py-3 text-right">Komisi</th>
                        <th class="px-4 py-3 text-right">MDR</th>
                        <th class="px-4 py-3 text-center">Escrow</th>
                        <th class="px-4 py-3 text-center">Batch</th>
                        <th class="px-4 py-3">Dibayar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-wheat-200 text-darkbrown-800">
                    @forelse ($transactions as $transaction)
                        <tr class="transition hover:bg-wheat-50/60">
                            <td class="px-4 py-3.5 font-mono text-[11px] font-bold text-darkbrown-950">{{ $transaction['booking_code'] }}</td>
                            <td class="px-4 py-3.5">{{ $transaction['vendor_name'] }}</td>
                            <td class="px-4 py-3.5">
                                <span class="font-semibold">{{ $paymentTypeLabels[$transaction['payment_type']] ?? $transaction['payment_type'] }}</span>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex flex-wrap items-center gap-1">
                                    <span>{{ $paymentMethodLabels[$transaction['payment_method']] ?? $transaction['payment_method'] }}</span>
                                    @if ($transaction['payment_channel'])
                                        <span class="rounded-md bg-wheat-100 px-1.5 py-0.5 text-[9px] font-bold uppercase text-darkbrown-500">{{ $transaction['payment_channel'] }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-right font-semibold">Rp {{ number_format($transaction['amount'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right font-bold text-avocado-700">Rp {{ number_format($transaction['commission_amount'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-right text-darkbrown-600">Rp {{ number_format($transaction['gateway_fee_amount'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3.5 text-center">
                                @if ($transaction['escrow_status'] === 'held')
                                    <x-badge variant="sunglow" size="sm" :dot="true">Ditahan</x-badge>
                                @elseif ($transaction['escrow_status'] === 'released')
                                    <x-badge variant="avocado" size="sm" :dot="true">Dicairkan</x-badge>
                                @elseif (in_array($transaction['escrow_status'], ['refunded', 'void'], true))
                                    <x-badge variant="danger" size="sm" :dot="true">{{ $escrowStatusLabels[$transaction['escrow_status']] }}</x-badge>
                                @else
                                    <span class="text-[11px] text-darkbrown-400">Tidak berlaku</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center text-[11px] text-darkbrown-500">
                                {{ $transaction['settlement_id'] ? '#'.$transaction['settlement_id'] : '—' }}
                            </td>
                            <td class="px-4 py-3.5 text-[11px] text-darkbrown-500">
                                {{ $transaction['paid_at']?->isoFormat('D MMM Y HH:mm') ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-10 text-center text-darkbrown-400">
                                Belum ada transaksi paid pada filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Placeholder batch -->
    <x-card title="Batch Settlement" subtitle="Batch mingguan, approval, dan bukti transfer dikerjakan pada tiket NTN-67.">
        <div class="flex flex-col gap-3 rounded-2xl border border-dashed border-wheat-300 bg-wheat-50/60 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-bold text-darkbrown-800">Belum ada batch settlement pada fase ini.</p>
                <p class="mt-1 text-xs text-darkbrown-500">
                    KPI payout dan batch sengaja ditampilkan nol agar dashboard tidak mengklaim data yang belum tersedia.
                </p>
            </div>
            <x-badge variant="wheat">Menunggu NTN-67</x-badge>
        </div>
    </x-card>
</div>
