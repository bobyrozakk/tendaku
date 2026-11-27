@php
    $issueSummary = $dashboard['issue_summary'];
    $damagedCount = (int) ($issueSummary['damaged'] ?? 0);
    $lostCount = (int) ($issueSummary['lost'] ?? 0);
    $overdueIssuesCount = (int) ($issueSummary['overdue'] ?? 0);
    $totalUnitsCount = (int) ($issueSummary['total_units'] ?? 0);
    $hasIssues = (bool) ($issueSummary['has_issues'] ?? false);

    $topProducts = $dashboard['top_products'];
    $overdueRows = $dashboard['overdue_rows'];
    $overdueCount = (int) ($metrics['overdue_count'] ?? 0);

    $statusBadgeClasses = [
        'goldenrod' => 'border-goldenrod-200/90 bg-goldenrod-50 text-goldenrod-800',
        'avocado' => 'border-avocado-200/80 bg-avocado-50 text-avocado-700',
        'neutral' => 'border-wheat-200 bg-wheat-100/70 text-darkbrown-600',
    ];
@endphp

<x-layouts.vendor title="Dashboard Owner - Tendaku">
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1">
                    <h1 class="text-lg sm:text-xl font-bold text-darkbrown-900 tracking-tight">
                        Dashboard Owner
                    </h1>
                    <span class="inline-flex items-center gap-1 rounded-md bg-[#FDFBF7] border border-wheat-200 px-2 py-0.5 text-[11px] font-medium text-darkbrown-700">
                        <x-icon name="calendar" stroke-width="1.75" class="w-3 h-3 text-avocado-700" />
                        <span>{{ $dashboard['date_range_label'] }}</span>
                    </span>
                </div>
                <p class="mt-0.5 text-xs text-darkbrown-500">
                    Ringkasan performa finansial, inventaris alat, dan monitoring operasional toko
                </p>
            </div>

            <div class="self-start lg:self-auto max-w-full">
                <x-vendor.dashboard.period-filter :options="$dashboard['period_options']" />
            </div>
        </div>
    </x-slot>

    <div class="space-y-4">
        {{-- Baris 1: 2 Card Omzet (kiri) & Monitoring Masalah (kanan) --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-stretch">
            <section aria-label="Ringkasan Omzet" class="lg:col-span-8 min-w-0 grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ($dashboard['financial_cards'] as $card)
                    <x-vendor.dashboard.metric-card
                        :title="$card['title']"
                        :value="$card['value']"
                        :description="$card['description']"
                        :icon="$card['icon']"
                        :emphasis="$card['emphasis']"
                    />
                @endforeach
            </section>

            <div class="lg:col-span-4 min-w-0">
                <section
                    aria-labelledby="heading-monitoring-masalah"
                    class="h-full rounded-xl border border-wheat-300/80 bg-white overflow-hidden flex flex-col"
                >
                    <div class="flex items-center justify-between gap-2 px-4 py-2.5 border-b border-wheat-200 bg-[#FDFBF7]/70">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <x-icon
                                    name="shield-alert"
                                    stroke-width="1.75"
                                    class="w-3.5 h-3.5 shrink-0 {{ $hasIssues ? 'text-goldenrod-700' : 'text-avocado-700' }}"
                                />
                                <h2 id="heading-monitoring-masalah" class="text-xs font-bold text-darkbrown-900 tracking-tight truncate">
                                    Monitoring Masalah
                                </h2>
                            </div>
                            <p class="mt-0.5 text-[11px] font-medium text-darkbrown-500 tabular-nums">
                                Total inventaris: <strong class="font-semibold text-darkbrown-800">{{ $totalUnitsCount }} unit fisik</strong>
                            </p>
                        </div>

                        <a
                            href="{{ route('vendor.units') }}"
                            wire:navigate
                            class="inline-flex min-h-[44px] sm:min-h-[26px] items-center gap-0.5 rounded px-1.5 text-[11px] font-semibold text-avocado-700 hover:text-avocado-800 hover:bg-avocado-50 transition-colors duration-150 whitespace-nowrap shrink-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-600"
                        >
                            <span>Kelola Unit</span>
                            <x-icon name="chevron-right" stroke-width="1.75" class="w-3 h-3" />
                        </a>
                    </div>

                    <div class="flex-1 grid grid-cols-3 divide-x divide-wheat-200/70">
                        <div class="px-2.5 py-2.5 flex flex-col items-center justify-center text-center">
                            <span class="font-admin-number text-lg font-extrabold leading-none tabular-nums {{ $damagedCount > 0 ? 'text-red-700' : 'text-darkbrown-500' }}">
                                {{ $damagedCount }}
                            </span>
                            <span class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-medium text-darkbrown-600">
                                <x-icon
                                    name="wrench"
                                    stroke-width="1.75"
                                    class="w-3 h-3 shrink-0 {{ $damagedCount > 0 ? 'text-red-700' : 'text-darkbrown-500' }}"
                                />
                                <span>Rusak</span>
                            </span>
                        </div>

                        <div class="px-2.5 py-2.5 flex flex-col items-center justify-center text-center">
                            <span class="font-admin-number text-lg font-extrabold leading-none tabular-nums {{ $lostCount > 0 ? 'text-darkbrown-900' : 'text-darkbrown-500' }}">
                                {{ $lostCount }}
                            </span>
                            <span class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-medium text-darkbrown-600">
                                <x-icon
                                    name="package-x"
                                    stroke-width="1.75"
                                    class="w-3 h-3 shrink-0 {{ $lostCount > 0 ? 'text-darkbrown-800' : 'text-darkbrown-500' }}"
                                />
                                <span>Hilang</span>
                            </span>
                        </div>

                        <a
                            href="#monitoring-keterlambatan"
                            class="px-2.5 py-2.5 flex flex-col items-center justify-center text-center hover:bg-[#FDFBF7] transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-avocado-600"
                            title="Lihat daftar keterlambatan pengembalian"
                        >
                            <span class="font-admin-number text-lg font-extrabold leading-none tabular-nums {{ $overdueIssuesCount > 0 ? 'text-goldenrod-800' : 'text-darkbrown-500' }}">
                                {{ $overdueIssuesCount }}
                            </span>
                            <span class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-medium text-darkbrown-600">
                                <x-icon
                                    name="clock-alert"
                                    stroke-width="1.75"
                                    class="w-3 h-3 shrink-0 {{ $overdueIssuesCount > 0 ? 'text-goldenrod-700' : 'text-darkbrown-500' }}"
                                />
                                <span>Telat</span>
                            </span>
                        </a>
                    </div>
                </section>
            </div>
        </div>

        {{-- Baris 2: Tren Pendapatan Harian (kiri) & Produk Terlaris (kanan) --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-stretch">
            <div class="lg:col-span-8 min-w-0">
                <x-vendor.dashboard.revenue-chart
                    :chart="$dashboard['revenue_chart']"
                    :selected-period="$selectedPeriod"
                    :period-label="$dashboard['selected_period_label']"
                />
            </div>

            <div class="lg:col-span-4 min-w-0">
                <section
                    aria-labelledby="heading-produk-terlaris"
                    class="h-full rounded-xl border border-wheat-300/80 bg-white overflow-hidden flex flex-col"
                >
                    <div class="flex items-center justify-between gap-2 px-4 py-3 border-b border-wheat-200 bg-[#FDFBF7]/70">
                        <div class="min-w-0">
                            <h2 id="heading-produk-terlaris" class="text-sm font-bold text-darkbrown-900 tracking-tight truncate">
                                Produk Terlaris
                            </h2>
                            <p class="mt-0.5 text-[11px] text-darkbrown-500 truncate">
                                3 alat paling sering disewa
                            </p>
                        </div>

                        <a
                            href="{{ route('vendor.products.index') }}"
                            wire:navigate
                            class="inline-flex min-h-[44px] sm:min-h-[28px] items-center gap-0.5 rounded px-1.5 text-xs font-semibold text-avocado-700 hover:text-avocado-800 hover:bg-avocado-50 transition-colors duration-150 whitespace-nowrap shrink-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-600"
                        >
                            <span>Kelola Produk</span>
                            <x-icon name="chevron-right" stroke-width="1.75" class="w-3.5 h-3.5" />
                        </a>
                    </div>

                    @if (empty($topProducts))
                        <div class="flex-1 flex flex-col items-center justify-center px-4 py-8 text-center">
                            <span class="w-9 h-9 rounded-lg border border-wheat-200 bg-[#FDFBF7] text-darkbrown-500 inline-flex items-center justify-center mb-2">
                                <x-icon name="package-open" stroke-width="1.75" class="w-4 h-4" />
                            </span>
                            <p class="text-xs font-bold text-darkbrown-900">
                                Belum ada data penyewaan produk
                            </p>
                            <p class="mt-0.5 text-[11px] text-darkbrown-500 leading-relaxed">
                                Produk paling sering disewa pada periode terpilih akan tampil di sini.
                            </p>
                        </div>
                    @else
                        <ol class="flex-1 flex flex-col divide-y divide-wheat-200/70">
                            @foreach ($topProducts as $product)
                                @php
                                    $rankClasses = match ($product['rank']) {
                                        1 => 'bg-goldenrod-50 text-goldenrod-800 border-goldenrod-300',
                                        2, 3 => 'bg-[#FDFBF7] text-darkbrown-700 border-wheat-300',
                                        default => 'bg-[#FDFBF7] text-darkbrown-600 border-wheat-200',
                                    };
                                @endphp

                                <li class="flex-1 px-4 py-3 flex flex-col justify-center">
                                    <div class="flex items-start gap-2.5">
                                        <span class="w-6 h-6 rounded border flex items-center justify-center text-[11px] font-bold tabular-nums shrink-0 mt-0.5 {{ $rankClasses }}">
                                            #{{ $product['rank'] }}
                                        </span>

                                        <div class="min-w-0 flex-1">
                                            <p class="text-[13px] font-semibold text-darkbrown-900 leading-snug">
                                                {{ $product['name'] }}
                                            </p>

                                            <div class="mt-1 flex items-center justify-between gap-2 text-xs">
                                                <span class="text-darkbrown-500 truncate">{{ $product['category'] }}</span>
                                                <span class="inline-flex items-center gap-1.5 shrink-0 tabular-nums">
                                                    <span class="font-bold text-avocado-700">{{ $product['times_rented'] }}x</span>
                                                    <span aria-hidden="true" class="text-wheat-400">&middot;</span>
                                                    <span class="font-admin-number font-semibold text-darkbrown-800">{{ $product['earnings_formatted'] }}</span>
                                                </span>
                                            </div>

                                            <div class="mt-2 h-1.5 w-full rounded-full bg-wheat-100 overflow-hidden" aria-hidden="true">
                                                <div
                                                    class="h-full rounded-full {{ $product['rank'] === 1 ? 'bg-goldenrod-500' : 'bg-avocado-500' }}"
                                                    style="width: {{ $product['share_percent'] }}%;"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>
            </div>
        </div>

        {{-- Baris 3: Monitoring Keterlambatan Pengembalian --}}
        <section
            id="monitoring-keterlambatan"
            aria-labelledby="heading-monitoring-keterlambatan"
            class="rounded-xl border border-wheat-300/80 bg-white overflow-hidden scroll-mt-20"
        >
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 px-4 sm:px-5 py-3.5 border-b border-wheat-200">
                <div class="min-w-0">
                    <h2 id="heading-monitoring-keterlambatan" class="text-sm sm:text-base font-bold text-darkbrown-900 tracking-tight">
                        Monitoring Keterlambatan Pengembalian
                    </h2>
                    <p class="text-xs text-darkbrown-500 mt-0.5">
                        Daftar penyewaan yang terlambat dikembalikan atau berpotensi terkena denda
                    </p>
                </div>

                @if ($overdueCount > 0)
                    <span class="inline-flex items-center gap-1.5 self-start sm:self-auto rounded-md border border-wheat-300/80 bg-[#FDFBF7] px-2.5 py-1 text-[11px] font-semibold text-darkbrown-700 whitespace-nowrap">
                        <x-icon name="clock-alert" stroke-width="1.75" class="w-3.5 h-3.5 text-goldenrod-700" />
                        <span class="tabular-nums">{{ $overdueCount }} Perlu Perhatian</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 self-start sm:self-auto rounded-md border border-wheat-200 bg-[#FDFBF7] px-2.5 py-1 text-[11px] font-medium text-avocado-700 whitespace-nowrap">
                        <x-icon name="circle-check" stroke-width="1.75" class="w-3.5 h-3.5" />
                        <span>Semua Tepat Waktu</span>
                    </span>
                @endif
            </div>

            @if (empty($overdueRows))
                <div class="px-5 py-9 text-center">
                    <span class="w-10 h-10 rounded-lg border border-avocado-200/80 bg-avocado-50 text-avocado-700 inline-flex items-center justify-center mb-2.5">
                        <x-icon name="circle-check" stroke-width="1.75" class="w-5 h-5" />
                    </span>
                    <p class="text-sm font-bold text-darkbrown-900">
                        Semua pengembalian tepat waktu!
                    </p>
                    <p class="mt-1 text-xs text-darkbrown-500">
                        Tidak ada transaksi sewa yang sedang terlambat saat ini.
                    </p>
                </div>
            @else
                {{-- Tampilan Mobile (Reflow Card List tanpa horizontal scroll) --}}
                <div class="md:hidden divide-y divide-wheat-200/70">
                    @foreach ($overdueRows as $row)
                        @php
                            $badgeToneClass = $statusBadgeClasses[$row['status_tone']] ?? $statusBadgeClasses['neutral'];
                        @endphp

                        <article class="px-4 py-3.5 space-y-2.5">
                            <div class="flex items-start justify-between gap-2.5">
                                <div class="min-w-0">
                                    <p class="text-[13px] font-semibold text-darkbrown-900 leading-snug truncate">
                                        {{ $row['customer_name'] }}
                                    </p>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-darkbrown-500">
                                        <span class="tabular-nums">{{ $row['customer_phone'] }}</span>
                                        <span aria-hidden="true" class="text-wheat-400">&middot;</span>
                                        <span class="font-mono font-medium text-darkbrown-600">{{ $row['booking_code'] }}</span>
                                    </div>
                                </div>

                                <span class="inline-flex items-center gap-1 rounded border px-2 py-0.5 text-[11px] font-medium leading-tight shrink-0 {{ $badgeToneClass }}">
                                    @if ($row['status_icon'])
                                        <x-icon :name="$row['status_icon']" stroke-width="1.75" class="w-3 h-3" />
                                    @endif
                                    <span>{{ $row['status_label'] }}</span>
                                </span>
                            </div>

                            <dl class="grid grid-cols-3 gap-2 rounded-lg border border-wheat-200/80 bg-[#FDFBF7] px-3 py-2 text-xs">
                                <div>
                                    <dt class="text-[11px] font-medium text-darkbrown-500">Jadwal</dt>
                                    <dd class="mt-0.5 text-xs font-medium text-darkbrown-700 tabular-nums">{{ $row['return_date'] }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[11px] font-medium text-darkbrown-500">Telat</dt>
                                    <dd class="mt-0.5 text-xs tabular-nums {{ $row['days_overdue'] > 0 ? 'font-semibold text-goldenrod-800' : 'font-medium text-darkbrown-500' }}">
                                        {{ $row['days_overdue'] > 0 ? "Telat {$row['days_overdue']} Hari" : '-' }}
                                    </dd>
                                </div>
                                <div class="text-right">
                                    <dt class="text-[11px] font-medium text-darkbrown-500">Denda</dt>
                                    <dd class="mt-0.5 font-admin-number tabular-nums {{ $row['has_late_fee'] ? 'text-xs font-bold text-darkbrown-900' : 'text-xs font-medium text-darkbrown-500' }}">
                                        {{ $row['late_fee_formatted'] }}
                                    </dd>
                                </div>
                            </dl>
                        </article>
                    @endforeach
                </div>

                {{-- Tampilan Tablet & Desktop (Tabel Terstruktur & Compact) --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-[#FDFBF7] border-b border-wheat-200 text-[11px] font-semibold text-darkbrown-500">
                            <tr>
                                <th scope="col" class="py-2.5 pl-5 pr-3">Kode Booking</th>
                                <th scope="col" class="py-2.5 px-3">Pelanggan</th>
                                <th scope="col" class="py-2.5 px-3">Jadwal Kembali</th>
                                <th scope="col" class="py-2.5 px-3">Keterlambatan</th>
                                <th scope="col" class="py-2.5 px-3 text-right">Denda Telat</th>
                                <th scope="col" class="py-2.5 pl-5 pr-5">Status Unit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-wheat-200/60 bg-white">
                            @foreach ($overdueRows as $row)
                                @php
                                    $badgeToneClass = $statusBadgeClasses[$row['status_tone']] ?? $statusBadgeClasses['neutral'];
                                @endphp

                                <tr class="hover:bg-[#FDFBF7]/90 transition-colors duration-150">
                                    <td class="py-2.5 pl-5 pr-3 align-middle whitespace-nowrap">
                                        <span class="font-mono text-[11px] font-medium tracking-tight text-darkbrown-600">
                                            {{ $row['booking_code'] }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 align-middle">
                                        <p class="text-[13px] font-semibold text-darkbrown-900 leading-snug">
                                            {{ $row['customer_name'] }}
                                        </p>
                                        <p class="text-[11px] text-darkbrown-500 leading-tight mt-0.5 tabular-nums">
                                            {{ $row['customer_phone'] }}
                                        </p>
                                    </td>
                                    <td class="py-2.5 px-3 align-middle text-xs font-medium text-darkbrown-700 tabular-nums whitespace-nowrap">
                                        {{ $row['return_date'] }}
                                    </td>
                                    <td class="py-2.5 px-3 align-middle whitespace-nowrap">
                                        @if ($row['days_overdue'] > 0)
                                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-goldenrod-800 tabular-nums">
                                                <x-icon name="clock-alert" stroke-width="1.75" class="w-3.5 h-3.5 text-goldenrod-700" />
                                                <span>Telat {{ $row['days_overdue'] }} Hari</span>
                                            </span>
                                        @else
                                            <span class="text-xs font-medium text-darkbrown-500">-</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 align-middle text-right whitespace-nowrap">
                                        <span class="font-admin-number tabular-nums {{ $row['has_late_fee'] ? 'text-[13px] font-bold text-darkbrown-900' : 'text-xs font-medium text-darkbrown-500' }}">
                                            {{ $row['late_fee_formatted'] }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 pl-5 pr-5 align-middle whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 rounded border px-2 py-0.5 text-[11px] font-medium leading-tight {{ $badgeToneClass }}">
                                            @if ($row['status_icon'])
                                                <x-icon :name="$row['status_icon']" stroke-width="1.75" class="w-3 h-3" />
                                            @endif
                                            <span>{{ $row['status_label'] }}</span>
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-layouts.vendor>
