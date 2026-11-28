<div class="rounded-2xl border border-wheat-200/90 bg-gradient-to-b from-white via-wheat-50/40 to-wheat-50/70 p-4 sm:p-5 md:p-6 shadow-sm shadow-darkbrown-900/5 space-y-4" aria-live="polite">
    @php
        $tones = [
            'safe' => [
                'card' => 'border-avocado-200 bg-avocado-50/70',
                'accent' => 'bg-avocado-500',
                'eyebrow' => 'text-avocado-800',
                'icon_box' => 'bg-white/80 border-avocado-200 text-avocado-700',
                'badge' => 'bg-white/90 border-avocado-200/80 text-avocado-900',
                'label' => 'text-avocado-900',
                'divider' => 'border-avocado-200/80',
                'meta' => 'text-avocado-800',
            ],
            'watch' => [
                'card' => 'border-sky-200 bg-sky-50/80',
                'accent' => 'bg-sky-500',
                'eyebrow' => 'text-sky-800',
                'icon_box' => 'bg-white/80 border-sky-200 text-sky-700',
                'badge' => 'bg-white/90 border-sky-200/80 text-sky-900',
                'label' => 'text-sky-900',
                'divider' => 'border-sky-200/80',
                'meta' => 'text-sky-800',
            ],
            'warning' => [
                'card' => 'border-amber-300 bg-amber-50',
                'accent' => 'bg-amber-500',
                'eyebrow' => 'text-amber-800',
                'icon_box' => 'bg-white/80 border-amber-200 text-amber-700',
                'badge' => 'bg-white/90 border-amber-200/80 text-amber-900',
                'label' => 'text-amber-900',
                'divider' => 'border-amber-200/80',
                'meta' => 'text-amber-800',
            ],
            'alert' => [
                'card' => 'border-orange-300 bg-orange-50',
                'accent' => 'bg-orange-500',
                'eyebrow' => 'text-orange-800',
                'icon_box' => 'bg-white/80 border-orange-200 text-orange-700',
                'badge' => 'bg-white/90 border-orange-200/80 text-orange-900',
                'label' => 'text-orange-900',
                'divider' => 'border-orange-200/80',
                'meta' => 'text-orange-800',
            ],
            'danger' => [
                'card' => 'border-red-300 bg-red-50 ring-1 ring-red-100',
                'accent' => 'bg-red-500',
                'eyebrow' => 'text-red-800',
                'icon_box' => 'bg-white/90 border-red-200 text-red-600',
                'badge' => 'bg-white/95 border-red-200 text-red-900 font-bold',
                'label' => 'text-red-900',
                'divider' => 'border-red-200/80',
                'meta' => 'text-red-800',
            ],
        ];
    @endphp

    {{-- Kepala panel: judul, rentang masa sewa, dan pemilih lokasi --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div class="flex items-start gap-3.5 min-w-0">
            <span class="w-11 h-11 rounded-2xl bg-gradient-to-br from-avocado-100 to-avocado-200/60 border border-avocado-300/50 text-avocado-800 flex items-center justify-center shadow-2xs shrink-0">
                <x-icon name="cloud-sun" class="w-6 h-6" />
            </span>
            <div class="min-w-0">
                <h3 class="text-base sm:text-lg font-black text-darkbrown-900 tracking-tight flex flex-wrap items-center gap-2">
                    <span>Cuaca Masa Sewa</span>
                    <span wire:loading wire:target="spot,syncDates"
                          class="inline-flex items-center gap-1.5 rounded-full bg-avocado-100/80 border border-avocado-300/60 px-2.5 py-0.5 text-[10px] font-bold text-avocado-800 animate-pulse">
                        <x-icon name="refresh-cw" class="w-3 h-3 animate-spin" />
                        Memperbarui…
                    </span>
                </h3>
                <div class="flex flex-wrap items-center gap-1.5 mt-1 text-xs text-darkbrown-600">
                    <span>Prakiraan:</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-bold text-darkbrown-900 bg-wheat-200/60 border border-wheat-300/50">
                        <x-icon name="calendar" class="w-3.5 h-3.5 text-darkbrown-500" />
                        {{ $rangeLabel }}
                    </span>
                    <span class="text-darkbrown-400">·</span>
                    <span class="inline-flex items-center gap-1 font-bold text-darkbrown-800">
                        <x-icon name="map-pin" class="w-3.5 h-3.5 text-avocado-600" />
                        {{ $locationLabel }}
                    </span>
                </div>
                @unless ($hasSelectedDates)
                    <p class="mt-1.5 inline-flex items-center gap-1.5 text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200/70 px-2.5 py-1 rounded-lg">
                        <x-icon name="calendar-plus" class="w-3.5 h-3.5 shrink-0 text-amber-600" />
                        <span>Pilih tanggal sewa untuk melihat prakiraan cuaca.</span>
                    </p>
                @endunless
            </div>
        </div>

        {{-- Dropdown Pemilih Spot Cuaca --}}
        <div class="w-full sm:w-64 shrink-0 rounded-xl bg-white/90 border border-wheat-200/90 p-2.5 shadow-2xs">
            <span class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-darkbrown-500 mb-1.5">
                <x-icon name="map-pin" class="w-3 h-3 text-avocado-600" />
                Lokasi Cuaca
            </span>
            <x-dropdown-select
                wire:model.live="spot"
                grouped
                size="sm"
                label="Lokasi cuaca"
                :options="$spotGroups"
            />
        </div>
    </div>

    @if ($forecast === null)
        {{-- Tanggal sewa di luar jangkauan prakiraan Open-Meteo --}}
        <div class="rounded-xl border border-wheat-200 bg-white/80 p-4 text-xs text-darkbrown-700 flex items-start gap-3 shadow-2xs">
            <div class="w-8 h-8 rounded-lg bg-wheat-100 flex items-center justify-center text-avocado-700 shrink-0">
                <x-icon name="calendar-clock" class="w-4 h-4" />
            </div>
            <div class="min-w-0">
                <p class="font-bold text-darkbrown-900">Prakiraan belum dalam jangkauan</p>
                <p class="mt-0.5 text-darkbrown-600">{{ $noForecastMessage }}</p>
            </div>
        </div>
    @elseif ($forecast['error'])
        {{-- Layanan cuaca gagal: booking tetap bisa dilanjutkan --}}
        <div class="rounded-xl border border-red-200 bg-red-50/70 p-4 flex items-start gap-3 shadow-2xs">
            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center text-red-600 shrink-0">
                <x-icon name="triangle-alert" class="w-4 h-4" />
            </div>
            <div class="text-xs text-red-900 min-w-0">
                <p class="font-bold text-red-900">Prakiraan cuaca belum tersedia</p>
                <p class="mt-0.5 text-red-800">{{ $forecast['error_message'] ?? 'Layanan cuaca sedang bermasalah.' }} Booking tetap bisa dilanjutkan.</p>
            </div>
        </div>
    @elseif (count($days) === 0)
        <div class="rounded-xl border border-wheat-200 bg-white/80 p-4 text-xs text-darkbrown-700 shadow-2xs">
            Belum ada data prakiraan untuk periode ini. Silakan coba pilih lokasi lain di atas.
        </div>
    @else
        {{-- Banner Peringatan Cuaca Ekstrem bila terdeteksi --}}
        @if (count($extremeDays) > 0)
            <div class="rounded-2xl border border-red-200/90 bg-red-50/80 p-4 flex items-center gap-3 shadow-2xs">
                <div class="w-9 h-9 rounded-xl bg-red-100 border border-red-200 flex items-center justify-center text-red-600 shrink-0">
                    <x-icon name="cloud-lightning" class="w-5 h-5 animate-pulse" />
                </div>
                <div class="text-xs text-red-900 min-w-0">
                    <p class="font-extrabold text-red-900 text-sm flex items-center gap-2">
                        Peringatan Cuaca Ekstrem
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-red-600 text-white tracking-wide">
                            {{ count($extremeDays) }} Hari
                        </span>
                    </p>
                    <p class="mt-0.5 text-red-700">Ada potensi cuaca ekstrem, siapkan perlengkapan hujan & flysheet tambahan.</p>
                </div>
            </div>
        @endif

        {{-- Legenda Tingkat Cuaca --}}
        <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-wheat-200/70">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-white/95 border border-avocado-200 text-avocado-900 shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-avocado-400 ring-2 ring-avocado-100"></span>
                Cerah
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-white/95 border border-sky-200 text-sky-900 shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-sky-400 ring-2 ring-sky-100"></span>
                Berawan
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-white/95 border border-amber-200 text-amber-900 shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 ring-2 ring-amber-100"></span>
                Hujan ringan
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-white/95 border border-orange-200 text-orange-900 shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-orange-500 ring-2 ring-orange-100 animate-pulse"></span>
                Hujan lebat
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-white/95 border border-red-200 text-red-900 shadow-2xs">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500 ring-2 ring-red-100 animate-pulse"></span>
                Ekstrem
            </span>
        </div>

        {{-- Kartu Prakiraan Per Hari dengan Efek Kedip / Warning Dinamis --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
            @foreach ($days as $day)
                @php
                    $tone = $tones[$day['severity']] ?? $tones['safe'];
                    $shouldBlink = in_array($day['severity'], ['alert', 'danger'], true);
                @endphp
                <article @class([
                    'relative rounded-2xl border p-4 flex flex-col justify-between transition-all duration-300 overflow-hidden group',
                    $tone['card'],
                    // Efek kedip HANYA untuk Hujan Lebat & Ekstrem
                    'ring-2 ring-orange-400/90 ring-offset-2 animate-pulse shadow-md shadow-orange-200/60' => $day['severity'] === 'alert',
                    'ring-2 ring-red-500 ring-offset-2 animate-pulse shadow-md shadow-red-200/60' => $day['severity'] === 'danger',
                    'ring-2 ring-avocado-500/80 ring-offset-1 shadow-sm' => $day['is_today'] && !$shouldBlink,
                    'shadow-2xs hover:shadow-md' => !$shouldBlink && !$day['is_today'],
                ])>
                    {{-- Accent bar atas --}}
                    <div class="absolute top-0 left-0 right-0 h-1.5 {{ $tone['accent'] }} {{ $shouldBlink ? 'animate-pulse' : '' }}"></div>

                    {{-- Bagian atas: Hari & Tanggal + Ikon Cuaca --}}
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="text-[11px] font-black uppercase tracking-wider {{ $tone['eyebrow'] }}">
                                        {{ $day['day_name'] }}
                                    </span>
                                    @if ($day['is_today'])
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-avocado-600 text-white tracking-wide shadow-2xs">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                                            Hari Ini
                                        </span>
                                    @endif
                                    @if ($shouldBlink)
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9px] font-black uppercase {{ $day['severity'] === 'danger' ? 'bg-red-600 text-white animate-bounce' : 'bg-orange-500 text-white animate-pulse' }} tracking-wide shadow-2xs">
                                            <x-icon name="triangle-alert" class="w-2.5 h-2.5 text-white" />
                                            Siaga
                                        </span>
                                    @endif
                                </div>
                                <p class="text-base sm:text-lg font-black text-darkbrown-900 tracking-tight mt-0.5">
                                    {{ $day['date_short'] }}
                                </p>
                            </div>

                            {{-- Ikon cuaca --}}
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl border flex items-center justify-center shrink-0 shadow-2xs transition-transform group-hover:scale-105 {{ $tone['icon_box'] }}">
                                <x-icon :name="$day['icon']" class="w-5 h-5" />
                            </div>
                        </div>

                        {{-- Label kondisi cuaca --}}
                        <div class="mt-2.5 flex items-center gap-1.5 flex-wrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold border {{ $tone['badge'] }} line-clamp-1">
                                {{ $day['label'] }}
                            </span>
                            @if ($shouldBlink)
                                <span class="inline-flex items-center gap-1 text-[10px] font-extrabold {{ $tone['eyebrow'] }}">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $tone['accent'] }} opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 {{ $tone['accent'] }}"></span>
                                    </span>
                                    Waspada
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Bagian bawah: Suhu, Peluang Hujan, Presipitasi & Angin --}}
                    <div class="mt-4 space-y-3">
                        <div class="flex items-end justify-between gap-2">
                            <p class="text-base sm:text-lg font-black text-darkbrown-900 leading-none">
                                {{ $day['temp_range'] }}
                            </p>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-black bg-white/90 text-sky-700 border border-sky-200/80 shadow-2xs">
                                <x-icon name="droplets" class="w-3.5 h-3.5 text-sky-500" />
                                {{ (int) $day['precipitation_probability'] }}%
                            </span>
                        </div>

                        {{-- Divider dan metrik presipitasi & angin --}}
                        <div class="pt-2.5 border-t {{ $tone['divider'] }} grid grid-cols-2 gap-2 text-[11px]">
                            <div class="flex items-center gap-1.5 text-darkbrown-600 font-medium">
                                <x-icon name="cloud-rain" class="w-3.5 h-3.5 text-darkbrown-400 shrink-0" />
                                <span class="text-darkbrown-800 font-bold">{{ number_format((float) $day['precipitation_mm'], 1) }} mm</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-darkbrown-600 font-medium justify-end">
                                <x-icon name="wind" class="w-3.5 h-3.5 text-darkbrown-400 shrink-0" />
                                <span class="text-darkbrown-800 font-bold">{{ number_format((float) $day['wind_kmh'], 0) }} km/j</span>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($partialMessage)
            <div class="rounded-xl border border-wheat-200 bg-white/70 p-3 text-xs text-darkbrown-600 flex items-start gap-2 shadow-2xs">
                <x-icon name="info" class="w-4 h-4 text-avocado-600 shrink-0 mt-0.5" />
                <span class="leading-relaxed">{{ $partialMessage }}</span>
            </div>
        @endif
    @endif

    {{-- Footer Info & Sumber Terpercaya --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pt-2 border-t border-wheat-200/60 text-[11px] text-darkbrown-500">
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 font-medium text-darkbrown-700">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-avocado-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-avocado-600"></span>
                </span>
                <span>Sumber: Open-Meteo</span>
            </span>
            @if ($generatedAt)
                <span class="text-darkbrown-300" aria-hidden="true">·</span>
                <span class="text-darkbrown-600">Diperbarui {{ $generatedAt }} WIB</span>
            @endif
            <span class="text-darkbrown-300" aria-hidden="true">·</span>
            <span class="text-darkbrown-400 italic">Prakiraan cuaca dapat berubah.</span>
        </div>
    </div>
</div>
