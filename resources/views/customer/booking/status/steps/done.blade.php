
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

{{-- STRUK PENGEMBALIAN & INSPEKSI ALAT (Tampil jika sudah selesai atau alat sudah dikembalikan) --}}
@if($isCompleted || $isReturned)
    <div class="pt-5 border-t border-wheat-200" x-data="{ openReceiptModal: false }">
        <div class="rounded-2xl border-2 border-dashed border-wheat-300 bg-gradient-to-b from-white to-[#fbf8f0] p-5 shadow-sm space-y-4">
            {{-- Header Struk --}}
            <div class="text-center pb-3 border-b border-dashed border-wheat-300">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-avocado-100 text-avocado-800 border border-avocado-300 mb-1.5">
                    <i data-lucide="check-circle" class="w-3 h-3"></i> Struk Pengembalian & Inspeksi
                </span>
                <h4 class="font-black text-base text-darkbrown-900 tracking-tight">{{ $rental->vendor?->name ?? 'Tendaku Outdoor' }}</h4>
                <p class="text-[11px] text-darkbrown-500">{{ $rental->vendor?->address ?? 'Toko Rental Tendaku' }}</p>
                <p class="text-[10px] font-mono text-darkbrown-400 mt-0.5">BUKTI RESMI PENGEMBALIAN UNIT & SERAH TERIMA JAMINAN</p>
            </div>

            {{-- Ringkasan Info Transaksi --}}
            <div class="grid grid-cols-2 gap-2 text-xs text-darkbrown-700 py-1">
                <div>
                    <span class="text-darkbrown-400 block text-[10px] uppercase font-bold">Kode Booking</span>
                    <span class="font-mono font-black text-darkbrown-900">{{ $rental->booking_code }}</span>
                </div>
                <div class="text-right">
                    <span class="text-darkbrown-400 block text-[10px] uppercase font-bold">Waktu Pengembalian</span>
                    <span class="font-semibold text-darkbrown-900">{{ $doneAt ? $doneAt->format('d M Y, H:i') : '-' }}</span>
                </div>
                <div>
                    <span class="text-darkbrown-400 block text-[10px] uppercase font-bold">Penyewa</span>
                    <span class="font-semibold text-darkbrown-900">{{ $renterName }}</span>
                </div>
                <div class="text-right">
                    <span class="text-darkbrown-400 block text-[10px] uppercase font-bold">Kasir Pemeriksa</span>
                    <span class="font-semibold text-darkbrown-900">{{ $rental->returnedBy?->name ?? 'Kasir Tendaku' }}</span>
                </div>
            </div>

            {{-- Rincian Hasil Inspeksi Fisik Alat --}}
            <div class="pt-2 border-t border-dashed border-wheat-300 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="font-black text-[11px] uppercase tracking-wider text-darkbrown-600">Hasil Inspeksi Alat:</span>
                    <span class="text-[10px] text-darkbrown-400 font-semibold">{{ $details->count() }} Item</span>
                </div>

                <div class="divide-y divide-wheat-200/70 bg-white/80 rounded-xl p-2.5 border border-wheat-200 space-y-2">
                    @foreach($details as $d)
                        @php
                            $cond = $d->condition_after ?? 'good';
                            $damageFee = (float)($d->damage_fee ?? 0);
                            $condBadge = match($cond) {
                                'lost' => ['bg-rose-100 text-rose-800 border-rose-300', 'Hilang'],
                                'damaged' => ['bg-amber-100 text-amber-900 border-amber-300', 'Rusak'],
                                default => ['bg-emerald-100 text-emerald-800 border-emerald-300', 'Baik'],
                            };
                        @endphp
                        <div class="flex items-start justify-between gap-2 pt-2 first:pt-0 text-xs">
                            <div class="min-w-0 flex-1">
                                <p class="font-bold text-darkbrown-900 truncate">{{ $d->masterItem?->name ?? 'Item Rental' }}</p>
                                <div class="flex items-center gap-2 mt-0.5 text-[10px] font-mono text-darkbrown-500">
                                    <span>Unit: {{ $d->itemUnit?->unit_code ?? '-' }}</span>
                                    <span>•</span>
                                    <span>{{ $d->itemUnit?->barcode ?? 'Tanpa barcode' }}</span>
                                </div>
                                @if($d->checklist_notes)
                                    <p class="text-[10px] text-darkbrown-400 italic mt-0.5">"{{ $d->checklist_notes }}"</p>
                                @endif
                            </div>
                            <div class="text-right shrink-0">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $condBadge[0] }}">
                                    {{ $condBadge[1] }}
                                </span>
                                @if($damageFee > 0)
                                    <p class="text-[10px] font-bold text-rose-600 mt-1">Denda: {{ $rupiah($damageFee) }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Ringkasan Biaya dan Denda --}}
            <div class="pt-2 border-t border-dashed border-wheat-300 space-y-1.5 text-xs">
                <div class="flex justify-between text-darkbrown-600">
                    <span>Biaya Sewa Awal:</span>
                    <span class="font-semibold text-darkbrown-900">{{ $rupiah($rental->grand_total) }}</span>
                </div>
                @if($rental->total_late_fee > 0)
                    <div class="flex justify-between text-rose-700 font-semibold">
                        <span>Denda Terlambat:</span>
                        <span>{{ $rupiah($rental->total_late_fee) }}</span>
                    </div>
                @endif
                @if($rental->total_damage_fee > 0)
                    <div class="flex justify-between text-rose-700 font-semibold">
                        <span>Denda Kerusakan / Hilang:</span>
                        <span>{{ $rupiah($rental->total_damage_fee) }}</span>
                    </div>
                @endif
                <div class="flex justify-between font-black text-sm text-darkbrown-900 pt-2 border-t border-wheat-200">
                    <span>Total Pembayaran Akhir:</span>
                    <span class="text-avocado-800 font-extrabold">{{ $rupiah($totalFinal) }}</span>
                </div>
            </div>

            {{-- Status Jaminan KTP --}}
            <div class="p-2.5 rounded-xl bg-avocado-50 border border-avocado-200 flex items-center justify-center gap-2 text-center text-xs font-bold text-avocado-900">
                <span>🪪</span>
                <span>Jaminan KTP: {{ $rental->ktp_collateral_status === 'returned' ? 'Sudah Dikembalikan ke Penyewa' : ($rental->ktp_collateral_status === 'held' ? 'Ditahan di Toko' : 'Tidak Ada / Selesai') }}</span>
            </div>

            {{-- Tombol Aksi Struk --}}
            <div class="pt-2 flex items-center gap-2">
                <button type="button"
                        @click="openReceiptModal = true"
                        class="flex-1 inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-bold bg-darkbrown-900 text-white hover:bg-darkbrown-800 transition shadow-sm cursor-pointer active:scale-95">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span>Buka & Cetak Struk</span>
                </button>
                <a href="{{ route('booking.receipt', ['code' => $rental->booking_code, 'format' => 'html']) }}"
                   target="_blank"
                   class="inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl text-xs font-bold bg-wheat-100 hover:bg-wheat-200 text-darkbrown-800 border border-wheat-300 transition cursor-pointer">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                    <span class="hidden sm:inline">Halaman Penuh</span>
                </a>
            </div>
        </div>

        {{-- MODAL PRINT POPUP (Format Struk Termal Kasir) --}}
        <template x-teleport="body">
            <div x-show="openReceiptModal"
                 x-cloak
                 class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-darkbrown-950/70 backdrop-blur-sm animate-fade-in"
                 @keydown.escape.window="openReceiptModal = false">
                <div class="bg-white w-full max-w-sm rounded-3xl border border-wheat-300 shadow-2xl p-6 space-y-4 max-h-[90vh] overflow-y-auto"
                     @click.away="openReceiptModal = false">

                    <div id="customer-printable-receipt" class="space-y-4">
                        <div class="text-center pb-3 border-b border-dashed border-wheat-300">
                            <h3 class="font-black text-lg text-darkbrown-900 tracking-tight">{{ $rental->vendor?->name ?? 'Tendaku Outdoor' }}</h3>
                            <p class="text-[10px] text-darkbrown-500">{{ $rental->vendor?->address ?? 'Toko Rental Tendaku' }}</p>
                            <p class="text-[10px] font-mono text-darkbrown-400 mt-1">STRUK PENGEMBALIAN & SERAH KTP</p>
                        </div>

                        <div class="text-xs space-y-1.5 text-darkbrown-700">
                            <div class="flex justify-between">
                                <span>Kode Booking:</span>
                                <span class="font-mono font-black text-darkbrown-900">{{ $rental->booking_code }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Pelanggan:</span>
                                <span class="font-bold text-darkbrown-900">{{ $renterName }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Waktu Retur:</span>
                                <span>{{ $doneAt ? $doneAt->format('d M Y, H:i') : '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Kasir:</span>
                                <span>{{ $rental->returnedBy?->name ?? 'Kasir Tendaku' }}</span>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-dashed border-wheat-300 space-y-2 text-xs">
                            <span class="font-bold text-[10px] uppercase text-darkbrown-500 block">Daftar Barang Kembali:</span>
                            @foreach($details as $it)
                                @php
                                    $itCond = $it->condition_after ?? 'good';
                                    $itFee = (float)($it->damage_fee ?? 0);
                                @endphp
                                <div class="flex justify-between text-[11px]">
                                    <div>
                                        <p class="font-bold text-darkbrown-900">{{ $it->masterItem?->name ?? 'Item' }}</p>
                                        <p class="text-[10px] text-darkbrown-500 font-mono">{{ $it->itemUnit?->barcode ?? $it->itemUnit?->unit_code ?? '-' }} ({{ $itCond }})</p>
                                    </div>
                                    <span class="font-semibold text-darkbrown-800">
                                        {{ $itFee > 0 ? 'Denda: ' . $rupiah($itFee) : '✓ Baik' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        <div class="pt-3 border-t border-dashed border-wheat-300 space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <span>Biaya Sewa Lunas:</span>
                                <span>{{ $rupiah($rental->grand_total) }}</span>
                            </div>
                            @if($rental->total_late_fee > 0)
                                <div class="flex justify-between text-red-700 font-semibold">
                                    <span>Denda Terlambat:</span>
                                    <span>{{ $rupiah($rental->total_late_fee) }}</span>
                                </div>
                            @endif
                            @if($rental->total_damage_fee > 0)
                                <div class="flex justify-between text-red-700 font-semibold">
                                    <span>Denda Kerusakan / Hilang:</span>
                                    <span>{{ $rupiah($rental->total_damage_fee) }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between font-black text-sm text-darkbrown-900 pt-2 border-t border-wheat-200">
                                <span>Total Transaksi Akhir:</span>
                                <span class="text-avocado-800">{{ $rupiah($totalFinal) }}</span>
                            </div>
                        </div>

                        <div class="p-2.5 rounded-xl bg-avocado-50 border border-avocado-200 text-center text-[11px] font-bold text-avocado-800">
                            🪪 Status KTP: {{ $rental->ktp_collateral_status === 'returned' ? 'Sudah Dikembalikan ke Penyewa' : 'Selesai' }}
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <button type="button"
                                onclick="window.print()"
                                class="w-1/2 py-2.5 rounded-xl font-bold text-xs bg-darkbrown-900 text-white hover:bg-darkbrown-800 transition cursor-pointer flex items-center justify-center gap-1.5">
                            <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                            <span>Cetak Struk</span>
                        </button>
                        <button type="button"
                                @click="openReceiptModal = false"
                                class="w-1/2 py-2.5 rounded-xl font-bold text-xs bg-wheat-100 hover:bg-wheat-200 text-darkbrown-800 transition cursor-pointer">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endif

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
