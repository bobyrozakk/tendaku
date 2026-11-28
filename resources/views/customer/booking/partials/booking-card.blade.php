
@php
    $isLate = $rental->isLate();
    $displayStatus = in_array($rental->status, ['returned', 'completed'], true) ? 'completed' : $rental->status;
    $statusLabel = in_array($rental->status, ['returned', 'completed'], true) ? 'Selesai' : $rental->displayStatusLabel();

    $itemCount = $rental->details->count();
    $firstItem = $rental->details->first()?->masterItem;

    $total = $rental->final_total ?? $rental->grand_total;
@endphp

<article class="space-y-4 rounded-2xl p-5 transition duration-200 sm:p-6 {{ $isLate ? 'border-2 border-red-300 bg-red-50 hover:border-red-400 hover:shadow-tendaku shadow-tendaku-sm' : 'card-tendaku border border-wheat-200 bg-white hover:border-wheat-400 shadow-tendaku-sm' }}">

    <div class="flex flex-col justify-between gap-3
                border-b {{ $isLate ? 'border-red-200' : 'border-wheat-100' }} pb-3
                sm:flex-row sm:items-center">
        <div class="flex flex-wrap items-center gap-3">
            <span class="font-mono text-sm font-extrabold
                         text-darkbrown-900">
                #{{ $rental->booking_code }}
            </span>

            <x-rental-status-badge
                :status="$displayStatus"
                :label="$statusLabel"
            />

            @if ($isLate)
                <span class="inline-flex items-center gap-1 rounded-full border border-red-300 bg-red-100 px-2.5 py-0.5 text-[11px] font-bold text-red-800">
                    <i data-lucide="triangle-alert" class="h-3 w-3" aria-hidden="true"></i>
                    Terlambat
                </span>
            @endif
        </div>

        <time datetime="{{ $rental->created_at->toIso8601String() }}"
              class="text-xs font-medium text-darkbrown-500">
            Dipesan
            {{ $rental->created_at->translatedFormat('d M Y, H:i') }}
        </time>
    </div>

    <div class="grid grid-cols-1 items-center gap-5
                md:grid-cols-12">

        <div class="space-y-3 md:col-span-8">
            <div class="flex items-start gap-2 text-sm
                        font-bold text-darkbrown-900">
                <i data-lucide="store"
                   class="mt-0.5 h-4 w-4 shrink-0 {{ $isLate ? 'text-red-700' : 'text-sunglow-700' }}"
                   aria-hidden="true"></i>

                <div>
                    <p>{{ $rental->vendor?->name ?? 'Vendor tidak tersedia' }}</p>

                    @if ($rental->vendor?->city)
                        <p class="mt-0.5 text-xs font-medium
                                  text-darkbrown-500">
                            {{ $rental->vendor->city }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="space-y-2 text-sm text-darkbrown-700">
                <p class="flex items-start gap-2">
                    <i data-lucide="calendar"
                       class="mt-0.5 h-4 w-4 shrink-0 {{ $isLate ? 'text-red-600' : 'text-avocado-600' }}"
                       aria-hidden="true"></i>

                    <span>
                        <span class="font-semibold">Periode sewa</span><br>

                        {{ $rental->pickup_date?->translatedFormat('d M Y') }}
                        –
                        {{ $rental->return_date?->translatedFormat('d M Y') }}

                        <span class="{{ $isLate ? 'font-semibold text-red-700' : 'text-darkbrown-500' }}">
                            ({{ $rental->rental_days }} hari{{ $isLate ? ' · Terlambat' : '' }})
                        </span>
                    </span>
                </p>

                <p class="flex items-start gap-2">
                    <i data-lucide="package"
                       class="mt-0.5 h-4 w-4 shrink-0 text-darkbrown-400"
                       aria-hidden="true"></i>

                    <span>
                        {{ $itemCount }} item sewa

                        @if ($firstItem)
                            · {{ $firstItem->name }}

                            @if ($itemCount > 1)
                                dan item lainnya
                            @endif
                        @endif
                    </span>
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-3 border-t {{ $isLate ? 'border-red-200' : 'border-wheat-100' }}
                    pt-4 md:col-span-4 md:items-end
                    md:border-t-0 md:pt-0">

            <div>
                <p class="text-xs text-darkbrown-500">
                    Total transaksi
                </p>

                <p class="font-heading text-xl font-extrabold
                          text-darkbrown-900">
                    Rp {{ number_format($total, 0, ',', '.') }}
                </p>
            </div>

            <a href="{{ route('booking.status', $rental->booking_code) }}"
               class="btn-tendaku-primary inline-flex min-h-[44px] sm:min-h-0 items-center
                      justify-center gap-2 rounded-xl px-4 py-2.5
                      text-sm font-bold">
                Lihat Detail
                <i data-lucide="arrow-right"
                   class="h-4 w-4"
                   aria-hidden="true"></i>
            </a>
        </div>
    </div>
</article>
