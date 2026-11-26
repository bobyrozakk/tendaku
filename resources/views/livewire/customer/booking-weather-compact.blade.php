<div class="space-y-3" aria-live="polite">
    @php
        // Warna per tingkat kewaspadaan (nama kelas ditulis utuh agar ikut ter-compile Tailwind)
        $iconTone = [
            'safe' => 'text-sunglow-500',
            'watch' => 'text-sky-500',
            'warning' => 'text-amber-500',
            'alert' => 'text-orange-500',
            'danger' => 'text-red-500',
        ];
        $labelTone = [
            'safe' => 'text-darkbrown-600',
            'watch' => 'text-sky-800',
            'warning' => 'text-amber-800',
            'alert' => 'text-orange-800',
            'danger' => 'text-red-700',
        ];
    @endphp

    {{-- Ringkasan masa sewa --}}
    @if ($summary)
        <div class="flex items-center gap-3">
            <x-icon :name="$summary['icon']" class="w-10 h-10 {{ $iconTone[$summary['severity']] ?? 'text-sunglow-500' }}" />
            <div class="min-w-0">
                <p class="text-xl font-extrabold text-darkbrown-900 tracking-tight leading-tight">{{ $summary['temp_range'] }}</p>
                <p class="text-xs text-darkbrown-600 truncate">{{ $summary['label'] }} · {{ $locationLabel }}</p>
            </div>
            <span wire:loading wire:target="spot,syncDates"
                  class="ml-auto inline-flex items-center gap-1 rounded-full bg-avocado-100/80 border border-avocado-300/60 px-2 py-0.5 text-[10px] font-bold text-avocado-800 animate-pulse">
                <x-icon name="refresh-cw" class="w-3 h-3 animate-spin" />
                Memperbarui…
            </span>
        </div>
    @else
        <div class="flex items-center gap-3">
            <x-icon name="cloud-sun" class="w-10 h-10 text-sunglow-500" />
            <div class="min-w-0">
                <p class="text-sm font-extrabold text-darkbrown-900 leading-tight">Prakiraan cuaca</p>
                <p class="text-xs text-darkbrown-600 truncate">{{ $locationLabel }}</p>
            </div>
        </div>
    @endif

    @unless ($hasSelectedDates)
        <p class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200/70 px-2.5 py-1 rounded-lg">
            <x-icon name="calendar-plus" class="w-3.5 h-3.5 shrink-0 text-amber-600" />
            <span>Pilih tanggal sewa untuk melihat prakiraan cuaca.</span>
        </p>
    @endunless

    {{-- Peringatan cuaca ekstrem tetap tampil --}}
    @if (count($extremeDays) > 0)
        <div class="rounded-xl border border-red-200 bg-red-50 px-3 py-2.5 flex items-start gap-2.5 text-xs text-red-900">
            <x-icon name="cloud-lightning" class="w-4 h-4 text-red-600 shrink-0 mt-0.5" />
            <p>
                <strong class="font-extrabold">Peringatan cuaca ekstrem ({{ count($extremeDays) }} hari).</strong>
                Siapkan perlengkapan hujan & flysheet tambahan.
            </p>
        </div>
    @endif

    {{-- Kartu prakiraan --}}
    <div class="rounded-2xl bg-white border border-wheat-200 shadow-2xs">
        <div class="flex items-center justify-between px-4 pt-3.5">
            <h4 class="text-sm font-extrabold text-darkbrown-900">Prakiraan Cuaca</h4>
            <span class="w-7 h-7 rounded-full bg-white border border-wheat-200 text-darkbrown-600 inline-flex items-center justify-center"
                  title="Sumber: Open-Meteo{{ $generatedAt ? ' · Diperbarui '.$generatedAt.' WIB' : '' }}. Prakiraan cuaca dapat berubah.">
                <x-icon name="info" class="w-4 h-4" />
                <span class="sr-only">Sumber: Open-Meteo. Prakiraan cuaca dapat berubah.</span>
            </span>
        </div>

        @if ($forecast === null)
            <p class="px-4 py-4 text-xs text-darkbrown-600 leading-relaxed">{{ $noForecastMessage }}</p>
        @elseif ($forecast['error'])
            <p class="px-4 py-4 text-xs text-red-800 leading-relaxed">
                {{ $forecast['error_message'] ?? 'Layanan cuaca sedang bermasalah.' }} Booking tetap bisa dilanjutkan.
            </p>
        @elseif (count($days) === 0)
            <p class="px-4 py-4 text-xs text-darkbrown-600 leading-relaxed">Belum ada data prakiraan untuk periode ini. Coba pilih lokasi lain di bawah.</p>
        @else
            <div class="mt-3 pb-3.5 flex overflow-x-auto snap-x snap-mandatory divide-x divide-wheat-200">
                @foreach ($days as $day)
                    <article class="snap-start grow shrink-0 basis-1/3 min-w-[6.75rem] px-2 text-center space-y-1.5">
                        <p class="text-[11px] font-semibold text-darkbrown-700 whitespace-nowrap">
                            {{ $day['date_compact'] }}
                            @if ($day['is_today'])
                                <span class="block text-[9px] font-black uppercase tracking-wide text-avocado-700">Hari ini</span>
                            @endif
                        </p>
                        <x-icon :name="$day['icon']" class="w-7 h-7 mx-auto {{ $iconTone[$day['severity']] ?? 'text-sunglow-500' }}" />
                        <p class="text-sm font-extrabold text-darkbrown-900 whitespace-nowrap">{{ $day['temp_range'] }}</p>
                        <p class="text-[11px] font-semibold leading-tight {{ $labelTone[$day['severity']] ?? 'text-darkbrown-600' }}">{{ $day['label'] }}</p>
                        <p class="inline-flex items-center gap-1 text-[10px] font-semibold text-sky-700">
                            <x-icon name="droplets" class="w-3 h-3" />
                            {{ (int) $day['precipitation_probability'] }}%
                        </p>
                    </article>
                @endforeach
            </div>
        @endif
    </div>

    @if ($partialMessage)
        <p class="flex items-start gap-1.5 text-[11px] text-darkbrown-600 leading-relaxed">
            <x-icon name="info" class="w-3.5 h-3.5 text-avocado-600 shrink-0 mt-0.5" />
            <span>{{ $partialMessage }}</span>
        </p>
    @endif

    {{-- Lokasi cuaca --}}
    <div class="flex items-center gap-2">
        <label for="booking-weather-spot" class="inline-flex items-center gap-1 text-[11px] font-semibold text-darkbrown-500 shrink-0">
            <x-icon name="map-pin" class="w-3 h-3 text-avocado-600" />
            Lokasi cuaca
        </label>
        <select id="booking-weather-spot" wire:model.live="spot"
                class="flex-1 min-w-0 rounded-lg bg-white border border-wheat-200 py-1.5 pl-2.5 pr-8 text-xs font-bold text-darkbrown-900 focus:border-avocado-500 focus:ring-2 focus:ring-avocado-500/20 cursor-pointer">
            @foreach ($spotGroups as $regionLabel => $regionSpots)
                <optgroup label="{{ $regionLabel }}">
                    @foreach ($regionSpots as $spotKey => $spotLabel)
                        <option value="{{ $spotKey }}">{{ $spotLabel }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>
</div>
