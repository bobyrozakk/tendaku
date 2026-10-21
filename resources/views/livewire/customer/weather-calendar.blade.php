<div class="space-y-6 py-2">
    <!-- Panel Kontrol: Spot & Periode -->
    <section class="card-tendaku p-6 rounded-2xl bg-white shadow-tendaku border border-wheat-200">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <!-- Pilih Spot Camping -->
            <div class="md:col-span-5">
                <label for="weather-spot" class="block text-xs font-bold uppercase text-darkbrown-600 mb-1.5 tracking-wider">
                    📍 Lokasi Camping
                </label>
                <select
                    id="weather-spot"
                    wire:model.live="spot"
                    class="input-tendaku w-full py-2.5 px-3 rounded-xl text-sm font-medium border-wheat-300"
                >
                    @foreach ($spots as $spotKey => $spotData)
                        <option value="{{ $spotKey }}">{{ $spotData['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Pilih Periode -->
            <div class="md:col-span-4">
                <label for="weather-days" class="block text-xs font-bold uppercase text-darkbrown-600 mb-1.5 tracking-wider">
                    🗓️ Periode Prakiraan
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

            <!-- Tombol Perbarui -->
            <div class="md:col-span-3">
                <button
                    type="button"
                    wire:click="refreshForecast"
                    wire:loading.attr="disabled"
                    class="btn-tendaku-secondary w-full py-2.5 rounded-xl text-sm font-bold"
                >
                    <span wire:loading.remove wire:target="refreshForecast">🔄 Perbarui</span>
                    <span wire:loading wire:target="refreshForecast">Memuat…</span>
                </button>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2 text-[11px] text-darkbrown-600">
            <span class="inline-flex items-center gap-1.5 bg-wheat-100 border border-wheat-200 px-2.5 py-1 rounded-lg font-semibold">
                📡 Sumber data: Open-Meteo
            </span>

            @if (! $forecast['error'])
                <span class="inline-flex items-center gap-1.5 bg-wheat-100 border border-wheat-200 px-2.5 py-1 rounded-lg font-semibold">
                    🕒 Diperbarui:
                    {{ \Illuminate\Support\Carbon::parse($forecast['generated_at'])->format('d M Y · H:i') }} WIB
                </span>
            @endif

            <span class="inline-flex items-center gap-1.5 bg-wheat-100 border border-wheat-200 px-2.5 py-1 rounded-lg font-semibold">
                📍 {{ $forecast['label'] }}
            </span>
        </div>
    </section>

    @if ($forecast['error'])
        <!-- Banner Error: Halaman tetap berjalan saat layanan cuaca gagal -->
        <section class="rounded-2xl border border-red-300 bg-red-50 p-5 flex flex-col md:flex-row md:items-center gap-4 justify-between">
            <div class="flex items-start gap-3">
                <span class="text-2xl leading-none">⚠️</span>
                <div>
                    <h3 class="text-sm font-extrabold text-red-900">Prakiraan cuaca belum tersedia</h3>
                    <p class="text-xs text-red-800 mt-1 max-w-xl">
                        {{ $forecast['error_message'] ?? 'Layanan cuaca sedang bermasalah.' }}
                        Silakan coba lagi beberapa saat lagi — halaman lain tetap bisa digunakan.
                    </p>
                </div>
            </div>

            <button
                type="button"
                wire:click="refreshForecast"
                wire:loading.attr="disabled"
                class="btn-tendaku-outline !py-2 !px-3.5 text-xs font-bold shrink-0"
            >
                🔄 Coba Lagi
            </button>
        </section>
    @else
        @php
            $extremeDays = array_values(array_filter(
                $forecast['days'],
                fn (array $day) => (bool) $day['is_extreme']
            ));
        @endphp

        <!-- Banner Peringatan Cuaca Ekstrem -->
        @if (count($extremeDays) > 0)
            <section class="rounded-2xl border-2 border-red-400 bg-red-50 p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="text-2xl leading-none">⛈️</span>
                    <div class="flex-1">
                        <h3 class="text-base font-extrabold text-red-900">Peringatan Cuaca Ekstrem</h3>
                        <p class="text-xs text-red-800 mt-1">
                            Cuaca ekstrem diperkirakan pada {{ count($extremeDays) }} hari di periode ini.
                            Pertimbangkan menunda perjalanan, menyiapkan pelindung hujan, atau mengencangkan tenda.
                        </p>

                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($extremeDays as $extremeDay)
                                <li class="px-2.5 py-1 rounded-lg bg-white border border-red-300 text-xs font-bold text-red-900">
                                    {{ \Illuminate\Support\Carbon::parse($extremeDay['date'])->format('d M Y') }} · {{ $extremeDay['label'] }}
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
                <p class="text-xs text-darkbrown-600 mt-1">Coba pilih periode lain atau tekan tombol Perbarui.</p>
            </section>
        @else
            <!-- Kartu Prakiraan Harian -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach ($forecast['days'] as $day)
                    @php
                        $date = \Illuminate\Support\Carbon::parse($day['date']);
                        $tempRange = number_format((float) $day['temp_min'], 0) . '° – ' . number_format((float) $day['temp_max'], 0) . '°C';
                    @endphp

                    <article @class([
                        'card-tendaku rounded-2xl bg-white shadow-tendaku border p-5 flex flex-col gap-3',
                        'border-red-300 ring-2 ring-red-100' => $day['is_extreme'],
                        'border-wheat-200' => ! $day['is_extreme'],
                    ])>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-darkbrown-500">
                                    {{ $day['day_name'] }}{{ $day['is_today'] ? ' · Hari Ini' : '' }}
                                </p>
                                <p class="text-sm font-extrabold text-darkbrown-900">{{ $date->format('d M Y') }}</p>
                            </div>

                            <x-weather-badge
                                :condition="$day['label']"
                                :icon="$day['icon']"
                                :temp="$tempRange"
                                :extreme="$day['is_extreme']"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="bg-wheat-100 rounded-xl px-2.5 py-2 border border-wheat-200">
                                <p class="text-darkbrown-500 font-semibold text-[10px] uppercase tracking-wide">Suhu</p>
                                <p class="font-extrabold text-darkbrown-900 mt-0.5">{{ $tempRange }}</p>
                            </div>

                            <div class="bg-wheat-100 rounded-xl px-2.5 py-2 border border-wheat-200">
                                <p class="text-darkbrown-500 font-semibold text-[10px] uppercase tracking-wide">Angin</p>
                                <p class="font-extrabold text-darkbrown-900 mt-0.5">
                                    {{ number_format((float) $day['wind_kmh'], 0) }} km/j
                                </p>
                            </div>

                            <div class="bg-wheat-100 rounded-xl px-2.5 py-2 border border-wheat-200">
                                <p class="text-darkbrown-500 font-semibold text-[10px] uppercase tracking-wide">Curah Hujan</p>
                                <p class="font-extrabold text-darkbrown-900 mt-0.5">
                                    {{ number_format((float) $day['precipitation_mm'], 1) }} mm
                                </p>
                            </div>

                            <div class="bg-wheat-100 rounded-xl px-2.5 py-2 border border-wheat-200">
                                <p class="text-darkbrown-500 font-semibold text-[10px] uppercase tracking-wide">Peluang Hujan</p>
                                <p class="font-extrabold text-darkbrown-900 mt-0.5">
                                    {{ (int) $day['precipitation_probability'] }}%
                                </p>
                            </div>
                        </div>

                        @if ($day['is_extreme'])
                            <p class="text-[11px] font-bold text-red-700 bg-red-50 border border-red-200 rounded-lg px-2.5 py-1.5">
                                ⚠️ Cuaca ekstrem — siapkan proteksi ekstra.
                            </p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    @endif
</div>
