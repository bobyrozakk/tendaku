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
                    <x-slot name="action">
                        <a href="{{ route('vendor.owner.dashboard.export', ['period' => $selectedPeriod]) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-avocado-700 bg-avocado-50 border border-avocado-200 hover:bg-avocado-100 transition duration-150 whitespace-nowrap"
                           title="Unduh rincian omzet harian (Excel)">
                            <i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i>
                            Export Excel
                        </a>
                    </x-slot>

                    @php
                        $trend = $metrics['revenue_trend'];
                        $maxTotal = (float) ($trend->max('total') ?? 0);
                        $periodTotal = (float) $trend->sum('total');
                        $daysWithRevenue = $trend->filter(fn (array $point) => $point['total'] > 0)->count();
                        $averagePerDay = $trend->count() > 0 ? $periodTotal / $trend->count() : 0;
                        $peakPoint = $trend->sortByDesc('total')->first();
                        // Label nilai ditampilkan untuk semua bar pada periode pendek;
                        // pada periode panjang hanya bar tertinggi agar tidak bertumpuk.
                        $showAllValueLabels = $trend->count() <= 14;

                        // Skala sumbu Y dibulatkan ke angka "cantik" (mis. 100 rb, 200 rb)
                        // agar garis bantu tidak menampilkan pecahan seperti 52,5 rb.
                        $scaleStep = 100000.0;

                        if ($maxTotal > 0) {
                            for ($exponent = 0; $exponent <= 12; $exponent++) {
                                $magnitude = 10 ** $exponent;
                                $found = null;

                                foreach ([1, 2, 5] as $multiplier) {
                                    $candidate = $multiplier * $magnitude;
                                    $intervals = (int) ceil($maxTotal / $candidate);

                                    // 2-4 interval menghasilkan 3-5 garis bantu yang mudah dibaca.
                                    if ($intervals >= 2 && $intervals <= 4) {
                                        $found = $candidate;

                                        break;
                                    }
                                }

                                if ($found !== null) {
                                    $scaleStep = (float) $found;

                                    break;
                                }
                            }
                        }

                        $scaleMax = $maxTotal > 0
                            ? ceil($maxTotal / $scaleStep) * $scaleStep
                            : $scaleStep;
                        $scaleIntervals = (int) round($scaleMax / $scaleStep);

                        // Tick dari nilai tertinggi turun ke 0.
                        $scaleTicks = collect(range($scaleIntervals, 0))
                            ->map(fn (int $step) => $step * $scaleStep);

                        $formatCompact = function (float $value): string {
                            if ($value >= 1000000) {
                                return 'Rp '.rtrim(rtrim(number_format($value / 1000000, 1, ',', '.'), '0'), ',').' jt';
                            }

                            if ($value >= 1000) {
                                return 'Rp '.rtrim(rtrim(number_format($value / 1000, 1, ',', '.'), '0'), ',').' rb';
                            }

                            return 'Rp '.number_format($value, 0, ',', '.');
                        };
                    @endphp

                    @if ($trend->isEmpty() || $periodTotal == 0)
                        <div class="py-12 text-center text-darkbrown-400 my-auto">
                            <svg class="w-12 h-12 mx-auto text-wheat-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                            </svg>
                            <p class="font-bold text-sm text-darkbrown-800">Belum ada catatan omzet pada periode ini.</p>
                            <p class="text-xs text-darkbrown-500">Transaksi rental yang lunas/selesai akan otomatis divisualisasikan di sini.</p>
                        </div>
                    @else
                        <!-- Ringkasan Periode -->
                        <div class="grid grid-cols-3 gap-2">
                            <div class="rounded-xl bg-avocado-50 border border-avocado-100 px-3 py-2">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-avocado-700">Total Periode</p>
                                <p class="text-sm font-extrabold text-darkbrown-900">{{ $formatCompact($periodTotal) }}</p>
                            </div>
                            <div class="rounded-xl bg-wheat-100/70 border border-wheat-200 px-3 py-2">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-darkbrown-500">Rata-rata / Hari</p>
                                <p class="text-sm font-extrabold text-darkbrown-900">{{ $formatCompact($averagePerDay) }}</p>
                            </div>
                            <div class="rounded-xl bg-sunglow-100/70 border border-sunglow-200 px-3 py-2">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-goldenrod-700">Hari Tertinggi</p>
                                <p class="text-sm font-extrabold text-darkbrown-900">
                                    {{ $formatCompact((float) ($peakPoint['total'] ?? 0)) }}
                                    <span class="text-[10px] font-semibold text-darkbrown-500">({{ $peakPoint['label'] ?? '-' }})</span>
                                </p>
                            </div>
                        </div>

                        <!-- Histogram Omzet Harian -->
                        <div class="mt-5 flex gap-2">
                            <!-- Sumbu Y -->
                            <div class="h-44 w-16 shrink-0 pt-5 flex flex-col justify-between items-end text-[9px] font-semibold text-darkbrown-400">
                                @foreach ($scaleTicks as $tick)
                                    <span class="leading-none">{{ $tick > 0 ? $formatCompact((float) $tick) : '0' }}</span>
                                @endforeach
                            </div>

                            <!-- Area Chart (scroll horizontal untuk periode panjang) -->
                            <div class="flex-1 min-w-0 overflow-x-auto scrollbar-none">
                                <div class="min-w-full w-max">
                                    <!-- Gridline + Bar -->
                                    <div class="relative h-44">
                                        <div class="absolute inset-x-0 top-5 bottom-0 flex flex-col justify-between pointer-events-none">
                                            @foreach (range(1, $scaleIntervals + 1) as $line)
                                                <div class="border-t {{ $line === $scaleIntervals + 1 ? 'border-wheat-300' : 'border-dashed border-wheat-200/80' }}"></div>
                                            @endforeach
                                        </div>

                                        <div class="relative h-full flex items-end gap-1.5 sm:gap-2">
                                            @foreach ($trend as $point)
                                                @php
                                                    $barPercent = $point['total'] > 0
                                                        ? max(6, round(($point['total'] / $scaleMax) * 100, 2))
                                                        : 2;
                                                    $isPeak = $point['total'] > 0 && $point['total'] == $maxTotal;
                                                @endphp

                                                <div class="flex-1 min-w-[24px] sm:min-w-[32px] h-full pt-5 flex flex-col justify-end items-center group relative">
                                                    <!-- Bar + Label Nilai -->
                                                    <div class="w-full max-w-[42px] relative" style="height: {{ $barPercent }}%;"
                                                         title="{{ $point['label'] }}: Rp {{ number_format($point['total'], 0, ',', '.') }}">
                                                        @if ($point['total'] > 0 && ($showAllValueLabels || $isPeak))
                                                            <span class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 whitespace-nowrap text-[10px] font-extrabold leading-none {{ $isPeak ? 'text-goldenrod-700' : 'text-darkbrown-700' }}">
                                                                {{ $formatCompact((float) $point['total']) }}
                                                            </span>
                                                        @endif

                                                        <div class="w-full h-full rounded-t-md transition-all duration-300 {{ $point['total'] > 0 ? ($isPeak ? 'bg-gradient-to-t from-goldenrod-600 to-sunglow-400 group-hover:from-goldenrod-700 group-hover:to-sunglow-300 shadow-sm' : 'bg-gradient-to-t from-avocado-600 to-avocado-400 group-hover:from-avocado-700 group-hover:to-avocado-500 shadow-sm') : 'bg-wheat-300/70' }}">
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Label Tanggal -->
                                    <div class="mt-2 flex gap-1.5 sm:gap-2">
                                        @foreach ($trend as $point)
                                            <span class="flex-1 min-w-[24px] sm:min-w-[32px] text-center text-[9px] font-semibold text-darkbrown-400 truncate">
                                                {{ $point['label'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Keterangan -->
                        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-[10px] text-darkbrown-500">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-sm bg-gradient-to-t from-avocado-600 to-avocado-400"></span>
                                Omzet harian
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-sm bg-gradient-to-t from-goldenrod-600 to-sunglow-400"></span>
                                Hari tertinggi
                            </span>
                            <span class="ml-auto">{{ $daysWithRevenue }} dari {{ $trend->count() }} hari memiliki transaksi</span>
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
