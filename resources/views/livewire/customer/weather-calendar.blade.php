<div class="space-y-6 py-2">
    <!-- Panel Kontrol: Lokasi & Periode (ter-reload otomatis saat diubah) -->
    <section class="card-tendaku p-5 sm:p-6 rounded-2xl bg-white shadow-tendaku border border-wheat-200">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <!-- Pilih Lokasi -->
            <div class="md:col-span-7">
                <label for="weather-spot" class="flex items-center gap-1.5 text-xs font-bold uppercase text-darkbrown-600 mb-1.5 tracking-wider">
                    <x-icon name="map-pin" class="w-3.5 h-3.5" />
                    Lokasi
                </label>
                <select
                    id="weather-spot"
                    wire:model.live="spot"
                    class="input-tendaku w-full py-2.5 px-3 rounded-xl text-sm font-medium border-wheat-300"
                >
                    @foreach ($spotGroups as $regionLabel => $regionSpots)
                        <optgroup label="{{ $regionLabel }}">
                            @foreach ($regionSpots as $spotKey => $spotLabel)
                                <option value="{{ $spotKey }}">{{ $spotLabel }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <!-- Pilih Periode -->
            <div class="md:col-span-5">
                <label for="weather-days" class="flex items-center gap-1.5 text-xs font-bold uppercase text-darkbrown-600 mb-1.5 tracking-wider">
                    <x-icon name="calendar-days" class="w-3.5 h-3.5" />
                    Periode Prakiraan
                </label>
                <select
                    id="weather-days"
                    wire:model.live="days"
                    class="input-tendaku w-full py-2.5 px-3 rounded-xl text-sm font-medium border-wheat-300"
                >
                    @foreach ($periodOptions as $optionValue => $optionLabel)
                        <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2 text-[11px] text-darkbrown-600">
            <span class="inline-flex items-center gap-1.5 bg-wheat-100 border border-wheat-200 px-2.5 py-1 rounded-lg font-semibold">
                <x-icon name="mountain" class="w-3.5 h-3.5 text-avocado-600" />
                {{ $forecast['label'] }}
            </span>

            <span class="inline-flex items-center gap-1.5 bg-wheat-100 border border-wheat-200 px-2.5 py-1 rounded-lg font-semibold">
                <x-icon name="radio" class="w-3.5 h-3.5 text-avocado-600" />
                Sumber data: Open-Meteo
            </span>

            @if (! $forecast['error'])
                <span class="inline-flex items-center gap-1.5 bg-wheat-100 border border-wheat-200 px-2.5 py-1 rounded-lg font-semibold">
                    <x-icon name="clock" class="w-3.5 h-3.5 text-avocado-600" />
                    Diperbarui
                    {{ \Illuminate\Support\Carbon::parse($forecast['generated_at'])->format('d M Y · H:i') }} WIB
                </span>
            @endif

            <!-- Indikator pemuatan saat lokasi/periode diubah -->
            <span
                wire:loading
                wire:target="spot,days"
                class="inline-flex items-center gap-1.5 bg-avocado-50 border border-avocado-200 text-avocado-800 px-2.5 py-1 rounded-lg font-semibold"
            >
                <x-icon name="refresh-cw" class="w-3.5 h-3.5 animate-spin" />
                Memuat prakiraan…
            </span>
        </div>
    </section>

    @if ($forecast['error'])
        <!-- Banner Error: Halaman tetap berjalan saat layanan cuaca gagal -->
        <section class="rounded-2xl border border-red-300 bg-red-50 p-5 flex flex-col md:flex-row md:items-center gap-4 justify-between">
            <div class="flex items-start gap-3">
                <x-icon name="triangle-alert" class="w-6 h-6 text-red-600 shrink-0" />
                <div>
                    <h3 class="text-sm font-extrabold text-red-900">Prakiraan cuaca belum tersedia</h3>
                    <p class="text-xs text-red-800 mt-1 max-w-xl">
                        {{ $forecast['error_message'] ?? 'Layanan cuaca sedang bermasalah.' }}
                        Silakan pilih lokasi lain atau coba beberapa saat lagi — halaman lain tetap bisa digunakan.
                    </p>
                </div>
            </div>
        </section>
    @else
        @php
            $extremeDays = $forecast['extreme_days'];
        @endphp

        <!-- Banner Peringatan Cuaca Ekstrem -->
        @if (count($extremeDays) > 0)
            <section class="rounded-2xl border-2 border-red-400 bg-red-50 p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <x-icon name="cloud-lightning" class="w-6 h-6 text-red-600 shrink-0" />
                    <div class="flex-1">
                        <h3 class="text-base font-extrabold text-red-900">Peringatan Cuaca Ekstrem</h3>
                        <p class="text-xs text-red-800 mt-1">
                            Cuaca ekstrem diperkirakan pada {{ count($extremeDays) }} hari di periode ini.
                            Pertimbangkan menunda perjalanan, menyiapkan pelindung hujan, atau mengencangkan tenda.
                        </p>

                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($extremeDays as $extremeDay)
                                <li class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-red-300 text-xs font-bold text-red-900">
                                    <x-icon :name="$extremeDay['icon']" class="w-3.5 h-3.5" />
                                    {{ \Illuminate\Support\Carbon::parse($extremeDay['date'])->format('d M Y') }}
                                    · {{ $extremeDay['label'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </section>
        @endif

        @if (count($forecast['days']) === 0)
            <!-- Data kosong tanpa error -->
            <section class="rounded-2xl border border-wheat-300 bg-wheat-100 p-6 text-center">
                <p class="text-sm font-semibold text-darkbrown-700">Belum ada data prakiraan untuk periode ini.</p>
                <p class="text-xs text-darkbrown-600 mt-1">Coba pilih lokasi atau periode lain.</p>
            </section>
        @else
            <!-- Kartu Prakiraan Harian -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach ($forecast['days'] as $day)
                    <article @class([
                        'card-tendaku rounded-2xl bg-white shadow-tendaku border p-4 flex flex-col gap-3.5',
                        'border-red-300 ring-2 ring-red-100' => $day['is_extreme'],
                        'border-wheat-200' => ! $day['is_extreme'],
                    ])>
                        <!-- Kepala Kartu: hari/tanggal + badge kondisi -->
                        <div class="space-y-1">
                            <div class="flex items-center justify-between gap-2">
                                <p @class([
                                    'text-[11px] font-bold uppercase tracking-wider',
                                    'text-red-600' => $day['is_extreme'],
                                    'text-darkbrown-500' => ! $day['is_extreme'],
                                ])>
                                    {{ $day['day_name'] }}{{ $day['is_today'] ? ' · Hari Ini' : '' }}
                                </p>

                                <x-weather-badge
                                    :condition="$day['label']"
                                    :icon="$day['icon']"
                                    :extreme="$day['is_extreme']"
                                />
                            </div>

                            <p class="text-sm font-extrabold text-darkbrown-900">{{ $day['date_full'] }}</p>
                        </div>

                        <!-- Suhu utama -->
                        <div @class([
                            'rounded-xl px-3 py-3 border flex items-center justify-between gap-3',
                            'bg-red-50 border-red-200' => $day['is_extreme'],
                            'bg-wheat-100 border-wheat-200' => ! $day['is_extreme'],
                        ])>
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-darkbrown-500">Rentang Suhu</p>
                                <p class="font-extrabold text-darkbrown-900 text-lg leading-tight mt-0.5">{{ $day['temp_range'] }}</p>
                            </div>
                            <x-icon :name="$day['icon']" class="w-7 h-7 {{ $day['is_extreme'] ? 'text-red-500' : 'text-goldenrod-500' }}" />
                        </div>

                        <!-- Rincian metrik dengan ikon Lucide -->
                        <dl class="grid grid-cols-3 gap-2 text-center">
                            <div class="rounded-xl border border-wheat-200 bg-white px-2 py-2">
                                <dt class="flex flex-col items-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-darkbrown-500">
                                    <x-icon name="wind" class="w-3.5 h-3.5 text-avocado-600" />
                                    Angin
                                </dt>
                                <dd class="mt-1 text-xs font-extrabold text-darkbrown-900">
                                    {{ number_format((float) $day['wind_kmh'], 0) }}<span class="font-semibold text-darkbrown-500"> km/j</span>
                                </dd>
                            </div>

                            <div class="rounded-xl border border-wheat-200 bg-white px-2 py-2">
                                <dt class="flex flex-col items-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-darkbrown-500">
                                    <x-icon name="droplets" class="w-3.5 h-3.5 text-avocado-600" />
                                    Hujan
                                </dt>
                                <dd class="mt-1 text-xs font-extrabold text-darkbrown-900">
                                    {{ number_format((float) $day['precipitation_mm'], 1) }}<span class="font-semibold text-darkbrown-500"> mm</span>
                                </dd>
                            </div>

                            <div class="rounded-xl border border-wheat-200 bg-white px-2 py-2">
                                <dt class="flex flex-col items-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-darkbrown-500">
                                    <x-icon name="percent" class="w-3.5 h-3.5 text-avocado-600" />
                                    Peluang
                                </dt>
                                <dd class="mt-1 text-xs font-extrabold text-darkbrown-900">
                                    {{ (int) $day['precipitation_probability'] }}<span class="font-semibold text-darkbrown-500">%</span>
                                </dd>
                            </div>
                        </dl>

                        @if ($day['is_extreme'])
                            <p class="flex items-start gap-1.5 text-[11px] font-bold text-red-700 bg-red-50 border border-red-200 rounded-lg px-2.5 py-1.5">
                                <x-icon name="triangle-alert" class="w-3.5 h-3.5 mt-px shrink-0" />
                                Cuaca ekstrem — siapkan proteksi ekstra.
                            </p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    @endif
</div>
