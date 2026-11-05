{{-- Step "Transaksi selesai". Semua data dari rentals / rental_details / payments. --}}
@php
    $isCompleted = $rental->status === 'completed';
    $isReturned  = $rental->returned_at !== null || in_array($rental->status, ['returned', 'completed'], true);

    $details      = $rental->details;
    $inspected    = $details->filter(fn ($d) => ! empty($d->condition_after));
    $allInspected = $details->isNotEmpty() && $inspected->count() === $details->count();
    $damageTotal  = (float) $rental->total_damage_fee;

    $finePaid = $rental->payments->where('payment_type', 'fine')->where('status', 'paid')->isNotEmpty();
    $fineDue  = $hasDenda && ! $finePaid;

    // Waktu selesai: saat kasir menutup pengembalian (fallback updated_at untuk data lama)
    $tz        = config('app.timezone', 'Asia/Jakarta');
    $doneAt    = ($rental->returned_at ?? $rental->updated_at)?->copy()->timezone($tz);
    $tzLabel   = match ($tz) {
        'Asia/Jakarta'  => 'WIB',
        'Asia/Makassar' => 'WITA',
        'Asia/Jayapura' => 'WIT',
        default         => $doneAt?->format('T'),
    };

    $badgeOk   = 'bg-avocado-100 text-avocado-800 border-avocado-300';
    $badgeWait = 'bg-sunglow-100 text-goldenrod-900 border-sunglow-300';
    $badgeBad  = 'bg-red-50 text-red-800 border-red-200';

    // Hero
    if ($isCompleted) {
        $heroTitle = 'Transaksi Selesai';
        $heroSub   = 'Penyewaan telah selesai dan seluruh kewajiban telah dipenuhi.';
    } elseif ($isReturned && $fineDue) {
        $heroTitle = 'Menunggu Pelunasan Denda';
        $heroSub   = 'Alat sudah dikembalikan. Transaksi selesai setelah denda dilunasi.';
    } elseif ($isReturned) {
        $heroTitle = 'Menyelesaikan Transaksi';
        $heroSub   = 'Alat sudah dikembalikan. Vendor sedang menyelesaikan pemeriksaan dan jaminan.';
    } else {
        $heroTitle = 'Belum Selesai';
        $heroSub   = 'Transaksi selesai setelah alat dikembalikan dan seluruh kewajiban dipenuhi.';
    }

    // Baris pengembalian alat
    if ($isReturned && $allInspected) {
        $retBadge = ['Selesai', $badgeOk];
        $retDesc  = $damageTotal > 0
            ? 'Alat telah dikembalikan. Ada catatan kerusakan dengan denda ' . $rupiah($damageTotal) . '.'
            : 'Alat telah dikembalikan dan pemeriksaan selesai.';
    } elseif ($isReturned) {
        $retBadge = ['Diperiksa', $badgeWait];
        $retDesc  = "Alat sudah diterima. {$inspected->count()} dari {$details->count()} unit selesai diperiksa.";
    } else {
        $retBadge = ['Belum', $badgeWait];
        $retDesc  = 'Menunggu alat dikembalikan ke vendor.';
    }

    // Baris jaminan
    $ktpStatus = $rental->ktp_collateral_status;
    if ($ktpStatus === 'returned') {
        $ktpBadge2 = ['Selesai', $badgeOk];
        $ktpDesc   = 'KTP sudah dikembalikan' . ($rental->ktp_returned_at ? ' pada ' . $fmtDateTime($rental->ktp_returned_at->copy()->timezone($tz)) . '.' : '.');
    } elseif ($ktpStatus === 'held') {
        $ktpBadge2 = ['Ditahan', $badgeWait];
        $ktpDesc   = 'KTP masih ditahan di toko sampai pemeriksaan selesai.';
    } else {
        $ktpBadge2 = ['Belum', $badgeWait];
        $ktpDesc   = 'Jaminan belum diproses.';
    }
@endphp

{{-- Hero --}}
<div class="flex flex-col items-center text-center gap-2 pt-2">
    <i data-lucide="{{ $isCompleted ? 'circle-check' : 'clock' }}"
       class="h-9 w-9 {{ $isCompleted ? 'text-avocado-600' : 'text-goldenrod-600' }}"></i>
    <h4 class="text-lg font-bold text-darkbrown-900 mt-1">{{ $heroTitle }}</h4>
    <p class="text-sm text-darkbrown-600 max-w-xs">{{ $heroSub }}</p>
    <span class="mt-1 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $isCompleted ? $badgeOk : $badgeWait }}">
        {{ $isCompleted ? 'Selesai' : 'Belum selesai' }}
    </span>
</div>

{{-- Total & waktu selesai --}}
<div class="flex items-end justify-between gap-4 pt-4 border-t border-wheat-200">
    <div>
        <div class="text-sm text-darkbrown-500">Total pembayaran</div>
        <div class="mt-1 text-3xl font-extrabold tracking-tight text-avocado-700">{{ $rupiah($totalFinal) }}</div>
    </div>
    <div class="text-right">
        <div class="text-sm text-darkbrown-500">Selesai pada</div>
        @if($isCompleted && $doneAt)
            <div class="mt-1 font-bold text-darkbrown-900">{{ $fmtDate($doneAt) }}</div>
            <div class="text-xs text-darkbrown-500 mt-0.5">{{ $doneAt->format('H:i') }} {{ $tzLabel }}</div>
        @else
            <div class="mt-1 font-bold text-darkbrown-500">Belum selesai</div>
        @endif
    </div>
</div>

{{-- Ringkasan kewajiban --}}
<ul class="pt-4 border-t border-wheat-200 space-y-4 text-sm">
    <li class="flex items-start gap-3">
        <i data-lucide="package-check" class="h-5 w-5 mt-0.5 shrink-0 text-darkbrown-700"></i>
        <div class="flex-1 min-w-0">
            <div class="font-semibold text-darkbrown-900">Pengembalian alat</div>
            <p class="text-darkbrown-500 mt-0.5">{{ $retDesc }}</p>
        </div>
        <span class="shrink-0 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $retBadge[1] }}">{{ $retBadge[0] }}</span>
    </li>

    <li class="flex items-start gap-3">
        <i data-lucide="shield-check" class="h-5 w-5 mt-0.5 shrink-0 text-darkbrown-700"></i>
        <div class="flex-1 min-w-0">
            <div class="font-semibold text-darkbrown-900">Jaminan / deposit</div>
            <p class="text-darkbrown-500 mt-0.5">{{ $ktpDesc }}</p>
        </div>
        <span class="shrink-0 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $ktpBadge2[1] }}">{{ $ktpBadge2[0] }}</span>
    </li>

    @if($hasDenda)
        <li class="flex items-start gap-3">
            <i data-lucide="receipt" class="h-5 w-5 mt-0.5 shrink-0 text-darkbrown-700"></i>
            <div class="flex-1 min-w-0">
                <div class="font-semibold text-darkbrown-900">Denda</div>
                <p class="text-darkbrown-500 mt-0.5">
                    {{ $rupiah($fineAmount) }}
                    @if($rental->total_late_fee > 0 && $rental->total_damage_fee > 0)
                        (keterlambatan {{ $rupiah($rental->total_late_fee) }}, kerusakan {{ $rupiah($rental->total_damage_fee) }})
                    @elseif($rental->total_late_fee > 0)
                        (keterlambatan)
                    @else
                        (kerusakan unit)
                    @endif
                </p>
            </div>
            <span class="shrink-0 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $finePaid ? $badgeOk : $badgeBad }}">{{ $finePaid ? 'Lunas' : 'Belum dibayar' }}</span>
        </li>
    @endif

    <li>
        <a href="{{ route('booking.receipt', $rental->booking_code) }}" class="flex items-start gap-3 group">
            <i data-lucide="receipt-text" class="h-5 w-5 mt-0.5 shrink-0 text-darkbrown-700"></i>
            <div class="flex-1 min-w-0">
                <div class="font-semibold text-darkbrown-900 group-hover:text-avocado-700 transition">Bukti transaksi</div>
                <p class="text-darkbrown-500 mt-0.5">Lihat rincian pembayaran dan informasi penyewaan.</p>
            </div>
            <i data-lucide="chevron-right" class="h-4 w-4 mt-1 shrink-0 text-darkbrown-500"></i>
        </a>
    </li>
</ul>
