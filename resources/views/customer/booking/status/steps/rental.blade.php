{{-- Step "Sedang disewa". Semua data dari kolom nyata rentals / rental_details. --}}
@php
    $returnDay = \Carbon\Carbon::parse($rental->return_date)->startOfDay();
    $daysLeft  = (int) now()->startOfDay()->diffInDays($returnDay, false);   // negatif = lewat jatuh tempo
    $isRenting = $rental->status === 'picked_up';
    $isOverdue = $isRenting && $daysLeft < 0;
    $lateDays  = $isOverdue ? abs($daysLeft) : 0;

    // Rumus sama dengan RentalService::processReturn
    $lateFeePerDay = (float) $rental->details->sum('late_fee_per_day_snapshot');
    $estLateFee    = $lateFeePerDay * $lateDays;

    $ktpText = match ($rental->ktp_collateral_status) {
        'held'     => 'KTP ditahan di toko',
        'returned' => 'KTP sudah dikembalikan',
        default    => 'Belum dikonfirmasi',
    };

    // 1 baris rental_details = 1 unit fisik, jadi dikelompokkan per jenis alat
    $rentedGroups = $rental->details->groupBy('master_item_id');
@endphp

{{-- Peringatan jatuh tempo --}}
@if($isOverdue)
    <div class="rounded-xl bg-red-50 border border-red-200 p-3.5 text-sm text-red-800">
        <strong>Terlambat {{ $lateDays }} hari.</strong>
        @if($lateFeePerDay > 0)
            Estimasi denda saat ini <strong>{{ $rupiah($estLateFee) }}</strong>
            ({{ $rupiah($lateFeePerDay) }}/hari), terus bertambah sampai alat dikembalikan.
        @endif
    </div>
@elseif($isRenting && $daysLeft <= 1)
    <div class="rounded-xl bg-sunglow-50 border border-sunglow-300 p-3.5 text-sm text-darkbrown-800">
        <strong>{{ $daysLeft === 0 ? 'Hari ini batas pengembalian.' : 'Besok batas pengembalian.' }}</strong>
        @if($lateFeePerDay > 0) Keterlambatan dikenai {{ $rupiah($lateFeePerDay) }}/hari. @endif
    </div>
@endif

{{-- Ringkasan: 2 x 2 --}}
<dl class="grid grid-cols-2 gap-x-6 gap-y-5 text-sm">
    <div>
        <i data-lucide="calendar-check" class="h-4 w-4 text-darkbrown-700 mb-2"></i>
        <dt class="text-xs text-darkbrown-500">Mulai sewa</dt>
        <dd class="mt-1 font-semibold text-darkbrown-900">
            {{ $rental->picked_up_at ? $fmtDateTime($rental->picked_up_at) : 'Belum tercatat' }}
        </dd>
    </div>
    <div>
        <i data-lucide="calendar-clock" class="h-4 w-4 text-darkbrown-700 mb-2"></i>
        <dt class="text-xs text-darkbrown-500">Wajib kembali</dt>
        <dd class="mt-1 font-semibold {{ $isOverdue ? 'text-red-700' : 'text-darkbrown-900' }}">{{ $fmtDate($rental->return_date) }}</dd>
    </div>
    <div>
        <i data-lucide="clock" class="h-4 w-4 text-darkbrown-700 mb-2"></i>
        <dt class="text-xs text-darkbrown-500">Durasi sewa</dt>
        <dd class="mt-1 font-semibold text-darkbrown-900">{{ $rental->rental_days }} hari</dd>
    </div>
    <div>
        <i data-lucide="shield-check" class="h-4 w-4 text-darkbrown-700 mb-2"></i>
        <dt class="text-xs text-darkbrown-500">Jaminan</dt>
        <dd class="mt-1 font-semibold text-darkbrown-900">{{ $ktpText }}</dd>
    </div>
</dl>

{{-- Alat yang sedang disewa --}}
<div class="pt-4 border-t border-wheat-200">
    <h4 class="text-sm font-bold text-darkbrown-900 mb-3">Alat yang sedang disewa ({{ $rental->details->count() }})</h4>
    <ul class="space-y-3">
        @foreach($rentedGroups as $rows)
            @php
                $item = $rows->first()->masterItem;
                $img  = $item?->primaryImage?->image_url ?: $item?->image_url;
                $codes = $rows->map(fn ($r) => $r->itemUnit?->unit_code)->filter()->implode(', ');
            @endphp
            <li class="flex items-center gap-4">
                <div class="w-24 h-24 shrink-0 rounded-2xl border border-wheat-200 bg-white overflow-hidden flex items-center justify-center">
                    @if($img)
                        <img src="{{ $img }}" alt="{{ $item?->name }}" class="w-full h-full object-cover" loading="lazy">
                    @else
                        <i data-lucide="tent" class="h-8 w-8 text-darkbrown-400"></i>
                    @endif
                </div>
                <div class="min-w-0">
                    <div class="font-bold text-darkbrown-900">{{ $item?->name ?? 'Peralatan camping' }}</div>
                    <div class="text-sm text-darkbrown-500 mt-1">
                        {{ $rows->count() }} unit
                        @if($codes) · <span class="font-mono text-xs">{{ $codes }}</span> @endif
                    </div>
                    <div class="text-sm text-darkbrown-500 mt-1">Pastikan alat dikembalikan dalam kondisi baik.</div>
                </div>
            </li>
        @endforeach
    </ul>
</div>

{{-- Informasi pengembalian --}}
<div class="rounded-2xl bg-wheat-100 p-4 text-sm text-darkbrown-700">
    <div class="flex items-center gap-2 font-bold text-darkbrown-900 mb-1.5">
        <i data-lucide="info" class="h-4 w-4"></i>
        <span>Informasi pengembalian</span>
    </div>
    <p class="leading-relaxed">
        Kembalikan alat paling lambat sesuai tanggal dan jam yang disepakati. Hubungi vendor jika perlu mengubah jadwal.
        @if($rental->vendor?->name)
            <span class="block mt-1 text-darkbrown-600">Lokasi: <strong>{{ $rental->vendor->name }}</strong>@if($rental->vendor->address), {{ $rental->vendor->address }}@endif</span>
        @endif
    </p>
</div>

@if($canPayFine)
    <button type="button" @click="payNow()" :disabled="paying"
            class="w-full py-2.5 px-4 rounded-xl font-bold text-sm bg-red-600 hover:bg-red-700 text-white shadow transition disabled:opacity-70">
        Bayar denda ({{ $rupiah($fineAmount) }})
    </button>
@endif

{{-- Tombol Simulasi Kasir / Testing Saja (hanya tampilan, TIDAK menulis ke database) --}}
@if(app()->environment('local'))
<div class="mt-4 pt-3 border-t border-dashed border-wheat-300">
    <button type="button"
            @click="activeNode = 'return'; justDone = 'rental'"
            class="px-3 py-1.5 rounded-lg text-xs font-bold text-avocado-800 bg-avocado-100 hover:bg-avocado-200 border border-avocado-300 transition">
        Selesaikan Masa Sewa (test)
    </button>
</div>
@endif
