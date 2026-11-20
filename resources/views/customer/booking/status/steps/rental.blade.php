{{-- Step "Sedang disewa". Semua data berasal dari rental dan detailnya. --}}
@php
    $returnDate = \Carbon\Carbon::parse($rental->return_date);
    $returnDay = $returnDate->copy()->startOfDay();
    $daysLeft = (int) now()->startOfDay()->diffInDays($returnDay, false);
    $isRenting = $rental->status === 'picked_up';
    $isOverdue = $isRenting && $daysLeft < 0;
    $lateDays = $isOverdue ? abs($daysLeft) : 0;
    $lateFeePerDay = (float) $rental->details->sum('late_fee_per_day_snapshot');
    $estimatedLateFee = $lateFeePerDay * $lateDays;
    $rentalStart = $rental->picked_up_at
        ? $fmtDateTime($rental->picked_up_at)
        : $fmtDate($rental->pickup_date);
    $returnTime = $rental->return_time
        ? \Carbon\Carbon::parse($rental->return_time)->format('H:i')
        : null;
    $rentedGroups = $rental->details->groupBy('master_item_id');
    $ktpText = match ($rental->ktp_collateral_status) {
        'held' => 'KTP ditahan di toko',
        'returned' => 'KTP sudah dikembalikan',
        default => 'Belum dikonfirmasi',
    };
    $ktpDescription = match ($rental->ktp_collateral_status) {
        'held' => 'Jaminan akan dikembalikan setelah proses pengembalian dan pemeriksaan alat selesai.',
        'returned' => 'Pengembalian jaminan telah dikonfirmasi.',
        default => 'Hubungi vendor untuk memastikan status jaminan.',
    };
    $deadlineTone = $isOverdue
        ? 'border-red-200 bg-red-50 text-red-900'
        : ($daysLeft <= 1 ? 'border-amber-300 bg-amber-50 text-darkbrown-900' : 'border-sunglow-300 bg-sunglow-50 text-darkbrown-900');
@endphp

<div class="space-y-4">
    <p class="text-sm leading-relaxed text-darkbrown-600">
        Alat telah diserahkan dan saat ini berada dalam masa penyewaan.
    </p>

    <section class="rounded-2xl border p-4 sm:p-5 {{ $deadlineTone }}">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wide opacity-75">
                    {{ $isOverdue ? 'Pengembalian Terlambat' : 'Wajib Dikembalikan' }}
                </p>
                <p class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">
                    {{ $returnDate->translatedFormat('d F Y') }}
                </p>
                <p class="mt-1 text-sm font-semibold">
                    @if($isOverdue)
                        Lewat jadwal {{ $lateDays }} hari
                    @elseif($daysLeft === 0)
                        Belum terlambat · batas pengembalian hari ini
                    @elseif($daysLeft === 1)
                        Belum terlambat · sisa 1 hari
                    @else
                        Belum terlambat · sisa {{ $daysLeft }} hari
                    @endif
                    @if($returnTime)
                        · sebelum pukul {{ $returnTime }}
                    @endif
                </p>
            </div>
            <i data-lucide="{{ $isOverdue ? 'triangle-alert' : 'calendar-clock' }}"
               class="mt-1 h-6 w-6 shrink-0"></i>
        </div>
        @if($isOverdue && $lateFeePerDay > 0)
            <p class="mt-3 border-t border-current/20 pt-3 text-sm">
                Estimasi denda saat ini <strong>{{ $rupiah($estimatedLateFee) }}</strong>
                <span class="opacity-80">({{ $rupiah($lateFeePerDay) }}/hari)</span>
            </p>
        @endif
    </section>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <section class="rounded-2xl border border-wheat-200 bg-white p-4">
            <h4 class="mb-3 flex items-center gap-2 text-sm font-bold text-darkbrown-900">
                <i data-lucide="calendar-days" class="h-4 w-4 text-darkbrown-500"></i>
                Jadwal Penyewaan
            </h4>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-darkbrown-500">Mulai sewa</dt>
                    <dd class="text-right font-semibold text-darkbrown-900">{{ $rentalStart }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-darkbrown-500">Tanggal kembali</dt>
                    <dd class="text-right font-semibold text-darkbrown-900">
                        {{ $fmtDate($rental->return_date) }}
                        @if($returnTime)
                            <span class="block text-xs font-normal text-darkbrown-500">Pukul {{ $returnTime }}</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-darkbrown-500">Durasi sewa</dt>
                    <dd class="text-right font-semibold text-darkbrown-900">{{ $rental->rental_days }} hari</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-wheat-200 bg-white p-4">
            <h4 class="mb-3 flex items-center gap-2 text-sm font-bold text-darkbrown-900">
                <i data-lucide="id-card" class="h-4 w-4 text-darkbrown-500"></i>
                Informasi Jaminan
            </h4>
            <p class="text-sm font-bold text-darkbrown-900">{{ $ktpText }}</p>
            <p class="mt-1 text-sm leading-relaxed text-darkbrown-600">{{ $ktpDescription }}</p>
        </section>
    </div>

    <section class="rounded-2xl border border-wheat-200 bg-white p-4">
        <h4 class="mb-3 text-sm font-bold text-darkbrown-900">
            Alat yang Disewa ({{ $rental->details->count() }} Unit)
        </h4>
        <ul class="space-y-3">
            @foreach($rentedGroups as $rows)
                @php
                    $item = $rows->first()->masterItem;
                    $imageUrl = $item?->primaryImage?->image_url ?: $item?->image_url;
                    $unitCodes = $rows->map(fn ($row) => $row->itemUnit?->unit_code)->filter()->implode(', ');
                @endphp
                <li class="flex items-center gap-3">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-wheat-200 bg-wheat-50">
                        @if($imageUrl)
                            <img src="{{ $imageUrl }}" alt="{{ $item?->name ?? 'Foto alat' }}" class="h-full w-full object-cover" loading="lazy">
                        @else
                            <i data-lucide="tent" class="h-5 w-5 text-darkbrown-400"></i>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-darkbrown-900">{{ $item?->name ?? 'Peralatan Camping' }}</p>
                        <p class="mt-0.5 text-xs text-darkbrown-500">
                            {{ $rows->count() }} unit
                            @if($unitCodes)
                                · <span class="font-mono">{{ $unitCodes }}</span>
                            @endif
                        </p>
                        <p class="mt-1 text-xs leading-relaxed text-darkbrown-500">
                            Pastikan alat dikembalikan dalam kondisi baik dan sesuai kelengkapan awal.
                        </p>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="rounded-2xl border border-wheat-200 bg-wheat-50 p-4">
        <h4 class="mb-2 flex items-center gap-2 text-sm font-bold text-darkbrown-900">
            <i data-lucide="info" class="h-4 w-4 text-darkbrown-500"></i>
            Informasi Pengembalian
        </h4>
        <p class="text-sm leading-relaxed text-darkbrown-700">
            Kembalikan seluruh alat sesuai jadwal yang telah ditentukan.
        </p>
        @if($rental->vendor)
            <div class="mt-3 border-t border-wheat-200 pt-3">
                <p class="text-xs font-bold text-darkbrown-500">Lokasi Pengembalian</p>
                <p class="mt-1 text-sm font-semibold text-darkbrown-900">{{ $rental->vendor->name }}</p>
                @if($rental->vendor->address)
                    <p class="mt-0.5 text-sm text-darkbrown-600">{{ $rental->vendor->address }}</p>
                @endif
            </div>
        @endif
    </section>

    @if($canPayFine)
        <button type="button" @click="payNow()" :disabled="paying"
                class="w-full rounded-xl bg-red-600 px-4 py-2.5 text-sm font-bold text-white shadow transition hover:bg-red-700 disabled:opacity-70">
            Bayar denda ({{ $rupiah($fineAmount) }})
        </button>
    @endif
</div>
