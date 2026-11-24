@php
    /**
     * Kalender ketersediaan (hanya untuk dilihat, tidak bisa diklik).
     *
     * Warna: putih = masih tersedia, biru = sebagian dibooking, merah = semua dibooking.
     *
     * Variabel:
     *  - $calendar   ['total' => int, 'days' => ['Y-m-d' => sisa]]
     *  - $embedded   true = tanpa kartu/judul sendiri (dipakai di dalam panel "Ketersediaan")
     *  - $unitLabel  'unit' (alat satuan) atau 'paket' (paket sewa)
     */
    $embedded = $embedded ?? false;
    $unitLabel = $unitLabel ?? 'unit';
    $calendarDays = $calendar['days'] ?? [];
    $calendarTotal = (int) ($calendar['total'] ?? 0);

    $monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $weekdayNames = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

    $calendarMonths = [];
    if ($calendarDays !== []) {
        $firstDay = \Carbon\Carbon::parse(array_key_first($calendarDays))->startOfDay();
        $lastDay = \Carbon\Carbon::parse(array_key_last($calendarDays))->startOfDay();

        for ($cursor = $firstDay->copy()->startOfMonth(); $cursor->lte($lastDay); $cursor->addMonth()) {
            $cells = [];
            for ($blank = 1; $blank < $cursor->dayOfWeekIso; $blank++) {
                $cells[] = null;
            }

            $stateOf = fn (?int $available) => match (true) {
                $available === null => 'out',
                $available <= 0 => 'full',
                $available < $calendarTotal => 'partial',
                default => 'free',
            };

            for ($day = 1; $day <= $cursor->daysInMonth; $day++) {
                $date = $cursor->copy()->day($day);
                $available = $calendarDays[$date->toDateString()] ?? null;
                $state = $stateOf($available);
                $booked = in_array($state, ['partial', 'full'], true);

                $cells[] = [
                    'day' => $day,
                    'available' => $available,
                    'label' => $date->format('d').' '.$monthNames[$date->month - 1].' '.$date->year,
                    'state' => $state,
                    // Apakah hari sebelum/sesudahnya punya status booking yang sama (satu rentang)
                    'prev_same' => $booked && $stateOf($calendarDays[$date->copy()->subDay()->toDateString()] ?? null) === $state,
                    'next_same' => $booked && $stateOf($calendarDays[$date->copy()->addDay()->toDateString()] ?? null) === $state,
                ];
            }

            $calendarMonths[] = [
                'title' => $monthNames[$cursor->month - 1].' '.$cursor->year,
                'cells' => $cells,
            ];
        }
    }

    $bandClasses = ['partial' => 'bg-sky-100', 'full' => 'bg-red-100'];
    $textClasses = [
        'free' => 'text-darkbrown-800',
        'partial' => 'font-bold text-sky-700',
        'full' => 'font-bold text-red-700',
        'out' => 'text-gray-300',
    ];

    $navButtonClass = 'flex h-7 w-7 shrink-0 cursor-pointer items-center justify-center rounded-full border border-gray-200 bg-white text-darkbrown-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40';
@endphp

@if ($calendarMonths !== [])
    <section class="{{ $embedded ? '' : 'rounded-3xl border border-wheat-200/90 bg-white p-6 shadow-tendaku-sm' }}"
             @if (! $embedded) aria-labelledby="kalender-ketersediaan" @else aria-label="Kalender ketersediaan" @endif
             x-data="{ month: 0, last: {{ count($calendarMonths) - 1 }} }">
        @if (! $embedded)
            <h2 id="kalender-ketersediaan" class="mb-3 text-lg font-extrabold tracking-tight text-darkbrown-900">Kalender Ketersediaan</h2>
        @endif

        {{-- Kalender ber-outline: header putih, isi abu muda --}}
        <div class="overflow-hidden rounded-2xl border border-gray-200">
            <div class="flex items-center justify-between gap-2 border-b border-gray-200 bg-white px-3 py-2">
                <button type="button" @click="month = Math.max(0, month - 1)" :disabled="month === 0"
                        aria-label="Bulan sebelumnya" class="{{ $navButtonClass }}">
                    <i data-lucide="chevron-left" class="h-3.5 w-3.5"></i>
                </button>

                <div class="relative h-5 min-w-0 flex-1 text-center">
                    @foreach ($calendarMonths as $index => $calendarMonth)
                        <p x-show="month === {{ $index }}" @if ($index > 0) x-cloak @endif
                           class="absolute inset-0 text-sm font-bold leading-5 text-darkbrown-900" aria-live="polite">{{ $calendarMonth['title'] }}</p>
                    @endforeach
                </div>

                <button type="button" @click="month = Math.min(last, month + 1)" :disabled="month === last"
                        aria-label="Bulan berikutnya" class="{{ $navButtonClass }}">
                    <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
                </button>
            </div>

            <div class="bg-gray-50 px-2 pb-2 pt-2.5">
                @foreach ($calendarMonths as $index => $calendarMonth)
                    <div x-show="month === {{ $index }}" @if ($index > 0) x-cloak @endif>
                        <div class="grid grid-cols-7 gap-y-1 text-center">
                            @foreach ($weekdayNames as $weekday)
                                <span class="pb-1 text-[10px] font-semibold uppercase tracking-wide text-gray-400">{{ $weekday }}</span>
                            @endforeach

                            @foreach ($calendarMonth['cells'] as $cellIndex => $cell)
                                @if ($cell === null)
                                    <span aria-hidden="true"></span>
                                @else
                                    @php
                                        $cellLabel = match ($cell['state']) {
                                            'full' => $cell['label'].': semua '.$unitLabel.' sudah dibooking',
                                            'partial' => $cell['label'].': tersisa '.$cell['available'].' dari '.$calendarTotal.' '.$unitLabel,
                                            'free' => $cell['label'].': tersedia',
                                            default => $cell['label'],
                                        };

                                        $column = $cellIndex % 7;
                                        $booked = in_array($cell['state'], ['partial', 'full'], true);
                                        $rangeStart = $booked && ! $cell['prev_same'];
                                        $rangeEnd = $booked && ! $cell['next_same'];

                                        // Pita rentang: membulat di ujung rentang dan di tepi minggu
                                        $bandClass = $booked ? $bandClasses[$cell['state']] : '';
                                        if ($booked && ($rangeStart || $column === 0)) {
                                            $bandClass .= ' rounded-l-full';
                                        }
                                        if ($booked && ($rangeEnd || $column === 6)) {
                                            $bandClass .= ' rounded-r-full';
                                        }
                                        $circleClass = $textClasses[$cell['state']];
                                    @endphp
                                    <span class="flex h-8 items-center justify-center {{ $bandClass }}">
                                        <span title="{{ $cellLabel }}"
                                              aria-label="{{ $cellLabel }}"
                                              class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold {{ $circleClass }}">
                                            {{ $cell['day'] }}
                                        </span>
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-1.5 text-[11px] text-darkbrown-600">
            <li class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded border border-gray-300 bg-gray-50"></span>Tersedia</li>
            <li class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-full bg-sky-200"></span>Sebagian dibooking</li>
            <li class="inline-flex items-center gap-1.5"><span class="h-3 w-3 rounded-full bg-red-200"></span>Semua {{ $unitLabel }} dibooking</li>
        </ul>
    </section>
@endif
