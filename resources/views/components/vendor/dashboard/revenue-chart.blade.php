@props([
    'chart' => [],
    'selectedPeriod' => '30_days',
    'periodLabel' => '30 Hari',
])

@php
    $hasData = (bool) ($chart['has_data'] ?? false);
    $ticks = $chart['ticks'] ?? [];
    $bars = $chart['bars'] ?? [];
    $scaleIntervals = (int) ($chart['scale_intervals'] ?? 3);
    $pointReadouts = array_map(
        static fn (array $bar): array => [
            'label' => $bar['label'],
            'total' => $bar['formatted_total'],
        ],
        $bars
    );
@endphp

<section
    aria-labelledby="heading-tren-pendapatan"
    x-data="{ activeIndex: null, points: @js($pointReadouts) }"
    x-init="$nextTick(() => { if ($refs.chartScroll) { $refs.chartScroll.scrollLeft = $refs.chartScroll.scrollWidth; } })"
    class="h-full rounded-xl border border-wheat-300/80 bg-white overflow-hidden flex flex-col"
>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 px-4 sm:px-5 py-3 border-b border-wheat-200">
        <div class="min-w-0">
            <h2 id="heading-tren-pendapatan" class="text-sm sm:text-base font-bold text-darkbrown-900 tracking-tight">
                Tren Pendapatan Harian
            </h2>
            <p class="text-[11px] sm:text-xs text-darkbrown-500 mt-0.5">
                Pergerakan omzet harian pada periode {{ strtolower($periodLabel) }}
            </p>
        </div>

        <a
            href="{{ route('vendor.owner.dashboard.export', ['period' => $selectedPeriod]) }}"
            class="inline-flex min-h-[44px] sm:min-h-[32px] items-center justify-center gap-1.5 self-start sm:self-auto rounded-lg border border-avocado-300/90 bg-avocado-50 px-3 py-1.5 text-xs font-semibold text-avocado-700 hover:bg-avocado-100 transition-colors duration-150 whitespace-nowrap focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-600 focus-visible:ring-offset-1"
            title="Unduh rincian omzet harian (Excel)"
        >
            <x-icon name="file-spreadsheet" stroke-width="1.75" class="w-3.5 h-3.5" />
            <span>Export Excel</span>
        </a>
    </div>

    @if (! $hasData)
        <div class="flex-1 flex items-center justify-center px-5 py-10 text-center">
            <div class="max-w-sm">
                <span class="w-10 h-10 rounded-lg border border-wheat-200 bg-[#FDFBF7] text-darkbrown-500 inline-flex items-center justify-center mb-2.5">
                    <x-icon name="chart-column" stroke-width="1.75" class="w-5 h-5" />
                </span>
                <p class="text-sm font-bold text-darkbrown-900">
                    Belum ada catatan omzet pada periode ini
                </p>
                <p class="mt-1 text-xs text-darkbrown-500 leading-relaxed">
                    Transaksi rental yang lunas atau selesai akan otomatis divisualisasikan di sini.
                </p>
                @if ($selectedPeriod !== 'this_year')
                    <a
                        href="{{ route('vendor.owner.dashboard', ['period' => 'this_year']) }}"
                        wire:navigate
                        class="mt-3.5 inline-flex min-h-[44px] sm:min-h-[34px] items-center gap-1.5 rounded-lg border border-wheat-300 bg-[#FDFBF7] px-3 py-1.5 text-xs font-semibold text-darkbrown-800 hover:bg-wheat-100 transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-600"
                    >
                        <span>Lihat Periode Tahun Ini</span>
                        <x-icon name="chevron-right" stroke-width="1.75" class="w-3.5 h-3.5" />
                    </a>
                @endif
            </div>
        </div>
    @else
        {{-- Ringkasan Periode (terintegrasi tanpa nested card) --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-wheat-200/80 border-b border-wheat-200 bg-[#FDFBF7]/80 shrink-0">
            <div class="px-4 sm:px-5 py-2 flex items-baseline justify-between sm:block">
                <p class="text-[11px] font-medium text-darkbrown-500">Total Periode</p>
                <p class="sm:mt-0.5 font-admin-number text-sm font-bold text-darkbrown-900 tabular-nums">
                    {{ $chart['period_total_compact'] }}
                </p>
            </div>
            <div class="px-4 sm:px-5 py-2 flex items-baseline justify-between sm:block">
                <p class="text-[11px] font-medium text-darkbrown-500">Rata-rata / Hari</p>
                <p class="sm:mt-0.5 font-admin-number text-sm font-bold text-darkbrown-900 tabular-nums">
                    {{ $chart['average_per_day_compact'] }}
                </p>
            </div>
            <div class="px-4 sm:px-5 py-2 flex items-baseline justify-between sm:block">
                <p class="text-[11px] font-medium text-darkbrown-500">Hari Tertinggi</p>
                <p class="sm:mt-0.5 font-admin-number text-sm font-bold text-goldenrod-800 tabular-nums">
                    {{ $chart['peak_total_compact'] }}
                    <span class="font-sans text-[11px] font-medium text-darkbrown-500">({{ $chart['peak_label'] }})</span>
                </p>
            </div>
        </div>

        {{-- Histogram Omzet Harian (proporsional ~340-360px total tinggi card) --}}
        <div class="px-4 sm:px-5 pt-4 pb-3 flex-1 flex flex-col justify-center">
            <div class="h-[208px] flex gap-2.5">
                {{-- Sumbu Y --}}
                <div class="w-16 shrink-0 pt-5 pb-[22px] flex flex-col justify-between items-end text-[11px] font-medium text-darkbrown-500 tabular-nums">
                    @foreach ($ticks as $tick)
                        <span class="leading-none">{{ $tick['label'] }}</span>
                    @endforeach
                </div>

                {{-- Area Chart (scroll horizontal hanya di dalam container untuk periode panjang) --}}
                <div x-ref="chartScroll" class="flex-1 min-w-0 overflow-x-auto scrollbar-none">
                    <div class="min-w-full w-max h-full flex flex-col">
                        <div class="relative flex-1">
                            {{-- Garis bantu horizontal --}}
                            <div class="absolute inset-x-0 top-5 bottom-0 flex flex-col justify-between pointer-events-none">
                                @foreach (range(1, $scaleIntervals + 1) as $line)
                                    <div class="border-t {{ $line === $scaleIntervals + 1 ? 'border-wheat-300' : 'border-dashed border-wheat-200' }}"></div>
                                @endforeach
                            </div>

                            {{-- Batang histogram --}}
                            <div class="absolute inset-0 pt-5 flex items-end gap-1.5 sm:gap-2">
                                @foreach ($bars as $index => $bar)
                                    <div class="flex-1 min-w-[44px] sm:min-w-[48px] h-full flex flex-col justify-end items-center group relative">
                                        <button
                                            type="button"
                                            class="w-full max-w-[34px] relative flex items-end rounded-t-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-600 focus-visible:ring-offset-1 cursor-pointer"
                                            style="height: {{ $bar['height_percent'] }}%;"
                                            title="{{ $bar['label'] }}: {{ $bar['formatted_total'] }}"
                                            aria-label="{{ $bar['label'] }}: {{ $bar['formatted_total'] }}"
                                            @mouseenter="activeIndex = {{ $index }}"
                                            @mouseleave="activeIndex = null"
                                            @focus="activeIndex = {{ $index }}"
                                            @blur="activeIndex = null"
                                            @click="activeIndex = activeIndex === {{ $index }} ? null : {{ $index }}"
                                        >
                                            @if ($bar['show_label'])
                                                <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 whitespace-nowrap text-[10px] font-bold leading-none tabular-nums {{ $bar['is_peak'] ? 'text-goldenrod-800' : 'text-darkbrown-700' }}">
                                                    {{ $bar['formatted_compact'] }}
                                                </span>
                                            @endif

                                            <span class="block w-full h-full rounded-t-md transition-colors duration-150 {{ $bar['has_revenue'] ? ($bar['is_peak'] ? 'bg-goldenrod-500 group-hover:bg-goldenrod-600' : 'bg-avocado-500 group-hover:bg-avocado-600') : 'bg-wheat-200 group-hover:bg-wheat-300' }}"></span>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Label Tanggal (min-w cukup agar tanggal seperti 24 Oct tidak terpotong) --}}
                        <div class="mt-2 h-3.5 shrink-0 flex items-center gap-1.5 sm:gap-2">
                            @foreach ($bars as $bar)
                                <span class="flex-1 min-w-[44px] sm:min-w-[48px] text-center text-[11px] font-medium text-darkbrown-500 whitespace-nowrap leading-none">
                                    {{ $bar['label'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Keterangan & Readout Interaktif (Hover/Tap/Keyboard) --}}
        <div class="px-4 sm:px-5 py-2.5 border-t border-wheat-200/80 bg-[#FDFBF7]/50 shrink-0 flex flex-wrap items-center justify-between gap-x-4 gap-y-1.5 text-[11px] text-darkbrown-500">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-sm bg-avocado-500 shrink-0" aria-hidden="true"></span>
                    <span>Omzet harian</span>
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-sm bg-goldenrod-500 shrink-0" aria-hidden="true"></span>
                    <span>Hari tertinggi</span>
                </span>
            </div>

            <div class="font-medium text-darkbrown-600 tabular-nums" aria-live="polite">
                <span x-show="activeIndex !== null" x-cloak>
                    <span x-text="activeIndex !== null ? points[activeIndex].label + ': ' : ''"></span>
                    <strong class="font-bold text-darkbrown-900" x-text="activeIndex !== null ? points[activeIndex].total : ''"></strong>
                </span>
                <span x-show="activeIndex === null">
                    {{ $chart['days_with_revenue'] }} dari {{ $chart['total_days'] }} hari memiliki transaksi
                </span>
            </div>
        </div>
    @endif
</section>
