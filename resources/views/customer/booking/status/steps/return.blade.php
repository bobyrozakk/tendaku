{{-- Step "Pengembalian". Semua data dari rentals / rental_details / payments, tidak ada teks status yang statis. --}}
@php
    $retDay      = \Carbon\Carbon::parse($rental->return_date)->startOfDay();
    $daysLeft    = (int) now()->startOfDay()->diffInDays($retDay, false);
    $isReturned  = $rental->returned_at !== null || in_array($rental->status, ['returned', 'completed'], true);
    $isOverdue   = ! $isReturned && $daysLeft < 0;
    $lateDays    = $isOverdue ? abs($daysLeft) : 0;

    $lateFeePerDay = (float) $rental->details->sum('late_fee_per_day_snapshot');
    $estLateFee    = $lateFeePerDay * $lateDays;

    // Jam batas hanya ditampilkan kalau memang tersimpan (bukan 00:00)
    $retTime = $rental->return_time ? \Carbon\Carbon::parse($rental->return_time)->format('H:i') : null;
    $retTime = ($retTime && $retTime !== '00:00') ? $retTime : null;

    // Keterlambatan aktual (kalau sudah dikembalikan)
    $actualLate = 0;
    if ($rental->returned_at) {
        $actualLate = max(0, (int) $retDay->diffInDays($rental->returned_at->copy()->startOfDay(), false));
    }

    // Pemeriksaan vendor, dari condition_after per unit
    $details       = $rental->details;
    $inspected     = $details->filter(fn ($d) => ! empty($d->condition_after));
    $allInspected  = $details->isNotEmpty() && $inspected->count() === $details->count();
    $notGood       = $inspected->filter(fn ($d) => $d->condition_after !== 'good');
    $damageTotal   = (float) $rental->total_damage_fee;

    // Jaminan
    $ktpStatus = $rental->ktp_collateral_status;

    // Denda
    $finePaid = $rental->payments->where('payment_type', 'fine')->where('status', 'paid')->isNotEmpty();
    $fineDue  = $hasDenda && ! $finePaid;
@endphp

<dl class="space-y-5 text-sm">
    {{-- Batas pengembalian --}}
    <div class="flex gap-3">
        <i data-lucide="calendar-clock" class="h-5 w-5 mt-0.5 shrink-0 text-darkbrown-700"></i>
        <div>
            <dt class="text-darkbrown-500">Batas pengembalian</dt>
            <dd class="mt-1 text-lg font-bold {{ $isOverdue ? 'text-red-700' : 'text-darkbrown-900' }}">
                Maks. {{ $fmtDate($rental->return_date) }}@if($retTime), {{ $retTime }}@endif
            </dd>
            <dd class="mt-1 text-xs {{ $isOverdue ? 'text-red-700 font-semibold' : 'text-darkbrown-500' }}">
                @if($isOverdue)
                    Terlambat {{ $lateDays }} hari
                    @if($lateFeePerDay > 0) · estimasi denda {{ $rupiah($estLateFee) }} @endif
                @elseif(! $isReturned && $daysLeft === 0)
                    Batas pengembalian hari ini.
                @elseif(! $isReturned)
                    {{ $daysLeft }} hari lagi · {{ $retTime ? 'tanggal dan jam' : 'tanggal' }} sesuai kesepakatan sewa.
                @else
                    {{ $retTime ? 'Tanggal dan jam' : 'Tanggal' }} sesuai kesepakatan sewa.
                @endif
            </dd>
        </div>
    </div>

    {{-- Dikembalikan pada --}}
    <div class="flex gap-3">
        <i data-lucide="package" class="h-5 w-5 mt-0.5 shrink-0 text-darkbrown-700"></i>
        <div>
            <dt class="text-darkbrown-500">Dikembalikan pada</dt>
            <dd class="mt-1 text-lg font-bold text-darkbrown-900">
                {{ $rental->returned_at ? $fmtDateTime($rental->returned_at) : 'Belum dikembalikan' }}
            </dd>
            <dd class="mt-1 text-xs text-darkbrown-500">
                @if($rental->returned_at)
                    {{ $actualLate > 0 ? "Terlambat {$actualLate} hari" : 'Tepat waktu' }}
                    @if($rental->returnedBy) · dikonfirmasi kasir {{ $rental->returnedBy->name }} @endif
                @else
                    Waktu aktual terisi setelah pengembalian dikonfirmasi.
                @endif
            </dd>
        </div>
    </div>

    {{-- Pemeriksaan vendor --}}
    <div class="flex gap-3">
        <i data-lucide="clipboard-check" class="h-5 w-5 mt-0.5 shrink-0 text-darkbrown-700"></i>
        <div class="min-w-0 flex-1">
            <dt class="text-darkbrown-500">Pemeriksaan vendor</dt>
            <dd class="mt-1 text-lg font-bold text-darkbrown-900">
                @if(! $isReturned) Menunggu pengembalian alat
                @elseif(! $allInspected) Sedang diperiksa
                @elseif($notGood->isNotEmpty() || $damageTotal > 0) Selesai, ada catatan kondisi
                @else Selesai, semua alat dalam kondisi baik
                @endif
            </dd>
            <dd class="mt-1 text-xs text-darkbrown-500">
                @if(! $isReturned)
                    Pemeriksaan dilakukan setelah vendor menerima alat.
                @elseif(! $allInspected)
                    {{ $inspected->count() }} dari {{ $details->count() }} unit sudah diperiksa.
                @else
                    {{ $details->count() }} unit diperiksa.
                @endif
            </dd>

            @if($allInspected)
                <ul class="mt-3 space-y-1.5 rounded-xl bg-wheat-50 border border-wheat-200 p-3">
                    @foreach($details as $d)
                        <li class="flex items-center justify-between gap-3 text-xs">
                            <span class="text-darkbrown-800 font-semibold truncate">
                                {{ $d->masterItem?->name ?? 'Peralatan camping' }}
                                @if($d->itemUnit) <span class="font-mono font-normal text-darkbrown-500">{{ $d->itemUnit->unit_code }}</span> @endif
                            </span>
                            <span class="shrink-0 {{ $d->condition_after === 'good' ? 'text-avocado-700' : 'text-red-700' }} font-bold">
                                {{ $d->condition_after }}@if((float) $d->damage_fee > 0) · {{ $rupiah($d->damage_fee) }} @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Jaminan --}}
    <div class="flex gap-3">
        <i data-lucide="shield-check" class="h-5 w-5 mt-0.5 shrink-0 text-darkbrown-700"></i>
        <div>
            <dt class="text-darkbrown-500">Jaminan / deposit</dt>
            <dd class="mt-1 text-lg font-bold text-darkbrown-900">
                @if($ktpStatus === 'returned') KTP sudah dikembalikan
                @elseif($ktpStatus === 'held') KTP masih ditahan di toko
                @else Belum diproses
                @endif
            </dd>
            <dd class="mt-1 text-xs text-darkbrown-500">
                @if($ktpStatus === 'returned' && $rental->ktp_returned_at)
                    Dikembalikan {{ $fmtDateTime($rental->ktp_returned_at) }}.
                @else
                    Penyelesaian mengikuti hasil pemeriksaan dan ketentuan jaminan.
                @endif
            </dd>
        </div>
    </div>
</dl>

{{-- Denda --}}
@if($hasDenda)
    <div class="rounded-xl border p-3.5 text-sm {{ $finePaid ? 'bg-avocado-50 border-avocado-200 text-avocado-900' : 'bg-red-50 border-red-200 text-red-800' }}">
        <div class="font-bold mb-1">{{ $finePaid ? 'Denda sudah dibayar' : 'Ada denda yang perlu diselesaikan' }}</div>
        <div class="space-y-0.5">
            @if($rental->total_late_fee > 0)
                <div class="flex justify-between"><span>Denda keterlambatan</span><span class="font-bold">{{ $rupiah($rental->total_late_fee) }}</span></div>
            @endif
            @if($rental->total_damage_fee > 0)
                <div class="flex justify-between"><span>Denda kerusakan unit</span><span class="font-bold">{{ $rupiah($rental->total_damage_fee) }}</span></div>
            @endif
        </div>
    </div>
@endif

{{-- Yang perlu dilakukan --}}
<div class="rounded-2xl bg-wheat-100 p-4 text-sm text-darkbrown-700 border-t border-wheat-200">
    <div class="flex items-center gap-2 font-bold text-darkbrown-900 mb-1.5">
        <i data-lucide="info" class="h-4 w-4"></i>
        <span>Yang perlu dilakukan</span>
    </div>
    <p class="leading-relaxed">
        @if($isOverdue)
            Segera kembalikan alat ke {{ $rental->vendor?->name ?? 'toko' }}. Denda keterlambatan bertambah setiap hari sampai alat dikembalikan.
        @elseif(! $isReturned)
            Kembalikan alat sesuai jadwal dan ikuti instruksi vendor. Simpan bukti pengembalian jika tersedia.
        @elseif($fineDue)
            Selesaikan pembayaran denda sebesar {{ $rupiah($fineAmount) }} agar transaksi dapat ditutup.
        @elseif(! $allInspected)
            Alat sudah diterima. Tunggu vendor menyelesaikan pemeriksaan.
        @else
            Tidak ada tindakan lagi yang diperlukan. Simpan bukti pengembalian jika tersedia.
        @endif
    </p>
</div>

@if($canPayFine && $fineDue)
    <button type="button" @click="payNow()" :disabled="paying"
            class="w-full py-2.5 px-4 rounded-xl font-bold text-sm bg-red-600 hover:bg-red-700 text-white shadow transition disabled:opacity-70">
        Bayar denda ({{ $rupiah($fineAmount) }})
    </button>
@endif

{{-- Tombol Simulasi Kasir / Testing Saja (hanya tampilan, TIDAK menulis ke database) --}}
@if(app()->environment('local'))
<div class="mt-4 pt-3 border-t border-dashed border-wheat-300">
    <button type="button"
            @click="activeNode = 'done'; justDone = 'return'"
            class="px-3 py-1.5 rounded-lg text-xs font-bold text-avocado-800 bg-avocado-100 hover:bg-avocado-200 border border-avocado-300 transition">
        Selesaikan Pengembalian (test)
    </button>
</div>
@endif
