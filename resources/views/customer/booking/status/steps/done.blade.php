@php
    $isCompleted = $rental->status === 'completed';
    $isReturned = $rental->returned_at !== null || in_array($rental->status, ['returned', 'completed'], true);

    $details = $rental->details;
    $fineTotal = (float) $rental->total_late_fee + (float) $rental->total_damage_fee;
    $finePaid = $rental->payments
        ->where('payment_type', 'fine')
        ->where('status', 'paid')
        ->isNotEmpty();
    $fineDue = $hasDenda && ! $finePaid;

    $timezone = config('app.timezone', 'Asia/Jakarta');
    $doneAt = ($rental->returned_at ?? $rental->updated_at)?->copy()->timezone($timezone);
    $timezoneLabel = match ($timezone) {
        'Asia/Jakarta' => 'WIB',
        'Asia/Makassar' => 'WITA',
        'Asia/Jayapura' => 'WIT',
        default => $doneAt?->format('T'),
    };

    [$title, $description] = match (true) {
        $isCompleted => [
            'Pengembalian Selesai',
            'Pengembalian alat telah diperiksa dan transaksi telah diselesaikan.',
        ],
        $fineDue => [
            'Menunggu Pelunasan Denda',
            'Lunasi denda untuk menyelesaikan transaksi.',
        ],
        $isReturned => [
            'Pemeriksaan Pengembalian',
            'Alat sudah diterima dan sedang diperiksa vendor.',
        ],
        default => [
            'Menunggu Pengembalian',
            'Kembalikan alat ke vendor untuk menyelesaikan transaksi.',
        ],
    };
@endphp

<div class="rounded-2xl border-2 border-dashed border-wheat-300 bg-gradient-to-b from-white to-[#fbf8f0] p-4 shadow-sm sm:p-5">
    <header class="flex flex-col gap-3 border-b border-dashed border-wheat-300 pb-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <i data-lucide="{{ $isCompleted ? 'circle-check' : 'clock' }}"
               class="h-6 w-6 shrink-0 {{ $isCompleted ? 'text-avocado-700' : 'text-goldenrod-600' }}"></i>
            <div>
                <h2 class="font-bold text-darkbrown-900">{{ $title }}</h2>
                <p class="text-sm text-darkbrown-600">{{ $description }}</p>
            </div>
        </div>
        @if($isCompleted && $doneAt)
            <div class="flex shrink-0 items-center gap-1.5 text-xs font-medium text-darkbrown-500 sm:pl-4">
                <i data-lucide="calendar-clock" class="h-4 w-4"></i>
                <span>{{ $doneAt->translatedFormat('d M Y, H:i') }} {{ $timezoneLabel }}</span>
            </div>
        @endif
    </header>

    <section class="flex flex-col gap-2 border-b border-dashed border-wheat-300 py-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-darkbrown-600">Total Pembayaran</p>
            <p class="mt-0.5 text-3xl font-extrabold tracking-tight text-avocado-800">{{ $rupiah($totalFinal) }}</p>
        </div>
        <p class="text-sm text-darkbrown-600">
            Termasuk denda <span class="font-bold text-darkbrown-900">{{ $rupiah($fineTotal) }}</span>
        </p>
    </section>

    <div class="grid grid-cols-1 gap-4 py-4 sm:grid-cols-2">
        <section>
            <div class="mb-2 flex items-center justify-between gap-3">
                <h3 class="font-bold text-darkbrown-900">Hasil Pengembalian</h3>
                <span class="text-xs text-darkbrown-500">{{ $details->count() }} Item Diperiksa</span>
            </div>

            <div class="divide-y divide-dashed divide-wheat-300">
                @forelse($details as $detail)
                    @php
                        $condition = $detail->condition_after;
                        $conditionLabel = match ($condition) {
                            'lost' => 'Hilang',
                            'damaged' => 'Rusak',
                            'good' => 'Baik',
                            default => 'Belum diperiksa',
                        };
                        $conditionColor = match ($condition) {
                            'lost', 'damaged' => 'text-red-700',
                            'good' => 'text-avocado-700',
                            default => 'text-darkbrown-400',
                        };
                        $damageFee = (float) ($detail->damage_fee ?? 0);
                    @endphp
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-darkbrown-900">
                                {{ $detail->masterItem?->name ?? 'Peralatan Camping' }}
                            </p>
                            <p class="truncate text-xs text-darkbrown-500">
                                {{ $detail->itemUnit?->unit_code ?? '-' }}
                                <span class="px-1 text-wheat-500">·</span>
                                {{ $detail->itemUnit?->barcode ?? 'Tanpa barcode' }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right text-xs">
                            <p class="font-semibold {{ $conditionColor }}">{{ $conditionLabel }}</p>
                            <p class="mt-0.5 text-darkbrown-500">
                                {{ $damageFee > 0 ? 'Denda '.$rupiah($damageFee) : 'Tanpa denda' }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="py-2 text-sm text-darkbrown-500">Belum ada barang untuk diperiksa.</p>
                @endforelse
            </div>
        </section>

        <div class="space-y-4 border-t border-dashed border-wheat-300 pt-4 sm:border-l sm:border-t-0 sm:pl-4 sm:pt-0">
            <section>
                <h3 class="mb-2 font-bold text-darkbrown-900">Rincian Pembayaran</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3 text-darkbrown-600">
                        <span>Biaya Sewa</span>
                        <span class="font-medium text-darkbrown-900">{{ $rupiah($rental->grand_total) }}</span>
                    </div>
                    <div class="flex justify-between gap-3 text-darkbrown-600">
                        <span>Total Denda</span>
                        <span class="font-medium text-darkbrown-900">{{ $rupiah($fineTotal) }}</span>
                    </div>
                    <div class="flex justify-between gap-3 border-t border-dashed border-wheat-300 pt-2 font-bold text-darkbrown-900">
                        <span>Total Pembayaran Akhir</span>
                        <span class="text-avocado-800">{{ $rupiah($totalFinal) }}</span>
                    </div>
                </div>

                @if($canPayFine && $fineDue)
                    <button type="button" @click="payNow()" :disabled="paying"
                            class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-red-700 disabled:opacity-70">
                        <i data-lucide="credit-card" class="h-4 w-4"></i>
                        Bayar denda {{ $rupiah($fineAmount) }}
                    </button>
                @endif
            </section>

            <section class="flex items-start gap-3 border-t border-dashed border-wheat-300 pt-3">
                <i data-lucide="id-card" class="mt-0.5 h-5 w-5 shrink-0 text-darkbrown-600"></i>
                <div class="min-w-0 text-sm">
                    <h3 class="font-bold text-darkbrown-900">Jaminan KTP</h3>
                    <p class="text-darkbrown-600">
                        @if($rental->ktp_collateral_status === 'returned')
                            Sudah dikembalikan kepada penyewa.
                        @elseif($rental->ktp_collateral_status === 'held')
                            Masih ditahan di toko.
                        @else
                            Belum diproses.
                        @endif
                    </p>
                    @if($rental->ktp_collateral_status === 'returned' && $rental->ktp_returned_at)
                        <p class="mt-0.5 text-xs text-darkbrown-400">
                            {{ $rental->ktp_returned_at->copy()->timezone($timezone)->translatedFormat('d M Y, H:i') }} {{ $timezoneLabel }}
                        </p>
                    @endif
                </div>
            </section>
        </div>
    </div>

    @if($isReturned || $isCompleted)
        <section class="border-t border-dashed border-wheat-300 pt-4">
            <div class="flex items-center gap-2">
                <i data-lucide="receipt-text" class="h-5 w-5 shrink-0 text-darkbrown-600"></i>
                <div class="min-w-0">
                    <h3 class="font-bold text-darkbrown-900">Bukti Transaksi</h3>
                    <p class="truncate text-sm font-semibold text-darkbrown-800">{{ $rental->vendor?->name ?? 'Tendaku Outdoor' }}</p>
                    <p class="truncate text-xs text-darkbrown-500">{{ $rental->vendor?->address ?? '-' }}</p>
                </div>
            </div>

            <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 border-t border-dashed border-wheat-300 pt-3 text-xs sm:grid-cols-4">
                <div class="min-w-0">
                    <dt class="text-darkbrown-400">Kode Booking</dt>
                    <dd class="truncate font-mono font-semibold text-darkbrown-800">{{ $rental->booking_code }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-darkbrown-400">Penyewa</dt>
                    <dd class="truncate font-semibold text-darkbrown-800">{{ $renterName }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-darkbrown-400">Kasir Pemeriksa</dt>
                    <dd class="truncate font-semibold text-darkbrown-800">{{ $rental->returnedBy?->name ?? 'Kasir Tendaku' }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-darkbrown-400">Waktu Pengembalian</dt>
                    <dd class="truncate font-semibold text-darkbrown-800">{{ $doneAt?->translatedFormat('d M Y, H:i') ?? '-' }}</dd>
                </div>
            </dl>

            <a href="{{ route('booking.receipt', $rental->booking_code) }}"
               target="_blank"
               class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-wheat-400 bg-wheat-100 px-4 py-2.5 text-sm font-bold text-darkbrown-900 transition hover:bg-wheat-200">
                <i data-lucide="printer" class="h-4 w-4"></i>
                Lihat & Cetak Struk
            </a>
        </section>
    @endif
</div>
