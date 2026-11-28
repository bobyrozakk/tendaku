<div class="contents" aria-live="polite">
    @php
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

    {{-- Judul hanya di mobile: di desktop dropdown sudah berdampingan dengan kalender --}}
    <div class="col-span-full md:hidden flex items-center gap-2 pt-1 text-sm font-bold text-darkbrown-900">
        <x-icon name="cloud-sun" class="w-4 h-4 text-avocado-700" />
        <span>Prakiraan cuaca lokasi camping</span>
    </div>

    <x-dropdown-select
        wire:model.live="spot"
        grouped
        icon="map-pin"
        size="xl"
        label="Lokasi cuaca"
        :options="$spotGroups"
        class="md:col-span-6"
    />

    <div wire:loading.class="opacity-60" wire:target="spot,syncDates" class="col-span-full md:col-span-12 space-y-3 transition-opacity">
        @unless ($hasSelectedDates)
            <p class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200/70 px-2.5 py-1 rounded-lg">
                <x-icon name="calendar-plus" class="w-3.5 h-3.5 shrink-0 text-amber-600" />
                <span>Pilih tanggal sewa untuk melihat prakiraan cuaca.</span>
            </p>
        @endunless

        @if (count($extremeDays) > 0)
            <div class="rounded-xl border border-red-200 bg-red-50 px-3 py-2.5 flex items-start gap-2.5 text-xs text-red-900">
                <x-icon name="cloud-lightning" class="w-4 h-4 text-red-600 shrink-0 mt-0.5" />
                <p>
                    <strong class="font-extrabold">Peringatan cuaca ekstrem ({{ count($extremeDays) }} hari).</strong>
                    Siapkan perlengkapan hujan & flysheet tambahan.
                </p>
            </div>
        @endif

        @if ($forecast === null)
            <p class="rounded-xl border border-wheat-200 bg-wheat-50/70 px-4 py-3 text-xs text-darkbrown-600 leading-relaxed">{{ $noForecastMessage }}</p>
        @elseif ($forecast['error'])
            <p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-800 leading-relaxed">
                {{ $forecast['error_message'] ?? 'Layanan cuaca sedang bermasalah.' }} Booking tetap bisa dilanjutkan.
            </p>
        @elseif (count($days) === 0)
            <p class="rounded-xl border border-wheat-200 bg-wheat-50/70 px-4 py-3 text-xs text-darkbrown-600 leading-relaxed">Belum ada data prakiraan untuk periode ini. Coba pilih lokasi lain.</p>
        @else
            <div wire:key="weather-strip-{{ $spot }}-{{ $days[0]['date'] }}-{{ count($days) }}"
                 x-data="{ picked: '{{ $days[0]['date'] }}' }"
                 class="flex items-center gap-2">
                @if (count($days) > 4)
                    <button type="button" @click="$refs.strip.scrollBy({ left: -$refs.strip.clientWidth * 0.7, behavior: 'smooth' })"
                            aria-label="Hari sebelumnya"
                            class="hidden md:inline-flex w-9 h-9 shrink-0 rounded-full bg-white border border-wheat-300 text-darkbrown-800 items-center justify-center hover:bg-wheat-100 transition">
                        <x-icon name="chevron-left" class="w-4 h-4" />
                    </button>
                @endif
                <div x-ref="strip"
                     class="flex-1 min-w-0 flex gap-2 overflow-x-auto scroll-smooth snap-x snap-mandatory [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($days as $day)
                        <button type="button"
                                @click="picked = '{{ $day['date'] }}'"
                                :aria-pressed="picked === '{{ $day['date'] }}' ? 'true' : 'false'"
                                :class="picked === '{{ $day['date'] }}' ? 'border-avocado-500 bg-avocado-100/70' : 'border-wheat-200 bg-wheat-50/70 hover:border-avocado-300'"
                                class="snap-start grow shrink-0 basis-[calc((100%-0.5rem)/2)] md:basis-[calc((100%-1.5rem)/4)] min-w-0 rounded-xl border px-2 pt-3 pb-2 text-center space-y-1.5 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500/40">
                            <p class="text-xs font-semibold text-darkbrown-800 whitespace-nowrap">
                                {{ $day['date_compact'] }}
                                @if ($day['is_today'])
                                    <span class="block text-xs font-black uppercase tracking-wide text-avocado-700">Hari ini</span>
                                @endif
                            </p>
                            <x-icon :name="$day['icon']" class="w-7 h-7 mx-auto {{ $iconTone[$day['severity']] ?? 'text-sunglow-500' }}" />
                            <p class="text-sm font-extrabold text-darkbrown-900 whitespace-nowrap">{{ $day['temp_range'] }}</p>
                            <p class="text-xs font-semibold leading-tight {{ $labelTone[$day['severity']] ?? 'text-darkbrown-600' }}">{{ $day['label'] }}</p>
                            <p class="inline-flex items-center gap-1 text-xs font-semibold text-sky-700">
                                <x-icon name="droplets" class="w-3 h-3" />
                                {{ (int) $day['precipitation_probability'] }}%
                            </p>
                        </button>
                    @endforeach
                </div>

                @if (count($days) > 4)
                    <button type="button" @click="$refs.strip.scrollBy({ left: $refs.strip.clientWidth * 0.7, behavior: 'smooth' })"
                            aria-label="Hari berikutnya"
                            class="hidden md:inline-flex w-9 h-9 shrink-0 rounded-full bg-white border border-wheat-300 text-darkbrown-800 items-center justify-center hover:bg-wheat-100 transition">
                        <x-icon name="chevron-right" class="w-4 h-4" />
                    </button>
                @endif
            </div>

            <p class="flex items-start gap-1.5 text-xs text-darkbrown-500 leading-relaxed">
                <x-icon name="info" class="w-3.5 h-3.5 text-avocado-700 shrink-0 mt-0.5" />
                <span>
                    Sumber: Open-Meteo{{ $generatedAt ? ' · Diperbarui '.$generatedAt.' WIB' : '' }}.<br>Cuaca dapat berubah sewaktu-waktu, pastikan membawa perlengkapan yang sesuai.
                    @if ($partialMessage)
                        <br>{{ $partialMessage }}
                    @endif
                </span>
            </p>
        @endif
    </div>
</div>
