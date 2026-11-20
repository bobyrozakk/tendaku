@php
    $isWaitingValidation = $rental->status === 'pending' && $isPaid;

    $pickupStatus = match (true) {
        $rental->status === 'pending' && $isPaid => 'Menunggu Validasi',
        $rental->status === 'pending' => 'Menunggu Pembayaran',
        $rental->status === 'confirmed' => 'Siap Diambil',
        $rental->status === 'picked_up' => 'Sudah Diambil',
        $rental->status === 'returned' => 'Pengembalian Diproses',
        $rental->status === 'completed' => 'Transaksi Selesai',
        $rental->status === 'cancelled' => 'Pesanan Dibatalkan',
        $rental->status === 'expired' => 'Kedaluwarsa',
        default => 'Status Pesanan',
    };

    $pickupDescription = match (true) {
        $rental->status === 'pending' && $isPaid => 'Pembayaran berhasil diverifikasi. Kasir sedang mencocokkan barcode dan kondisi fisik alat sebelum pesanan siap diambil.',
        $rental->status === 'pending' => 'Pembayaran belum terverifikasi. Kasir akan memvalidasi alat setelah pembayaran berhasil.',
        $rental->status === 'confirmed' => 'Kasir telah mencocokkan barcode dan kondisi fisik alat. Pesanan siap diambil.',
        $rental->status === 'picked_up' => 'Alat telah diserahterimakan dan masa sewa sedang berjalan.',
        $rental->status === 'returned' => 'Alat sudah dikembalikan dan sedang diperiksa.',
        $rental->status === 'completed' => 'Serah terima, pengembalian, dan pemeriksaan telah selesai.',
        $rental->status === 'cancelled' => 'Pesanan telah dibatalkan.',
        $rental->status === 'expired' => 'Pesanan telah kedaluwarsa.',
        default => '',
    };

    $statusTone = match (true) {
        $isWaitingValidation || $rental->status === 'pending' => 'border-amber-300 bg-amber-50/90 text-amber-950',
        in_array($rental->status, ['cancelled', 'expired'], true) => 'border-red-200 bg-red-50 text-red-900',
        default => 'border-avocado-200 bg-avocado-50 text-avocado-900',
    };

    $unitStatus = $pickupStatus;
@endphp

<div class="space-y-4">
    <section class="flex flex-col gap-3 rounded-2xl border p-4 sm:flex-row sm:items-start sm:justify-between {{ $statusTone }}">
        <div class="flex items-start gap-3">
            <i data-lucide="{{ $isWaitingValidation ? 'hourglass' : (in_array($rental->status, ['cancelled', 'expired'], true) ? 'circle-alert' : 'package-check') }}"
               class="mt-0.5 h-5 w-5 shrink-0 {{ $isWaitingValidation ? 'animate-pulse' : '' }}"></i>
            <div class="min-w-0">
                <h3 class="font-bold">{{ $pickupStatus }}</h3>
                <p class="mt-1 text-sm leading-relaxed opacity-90">{{ $pickupDescription }}</p>
            </div>
        </div>
    </section>

    @if($rental->details->isNotEmpty())
        <section class="overflow-hidden rounded-2xl border border-wheat-200 bg-white">
            <div class="flex items-center justify-between gap-3 border-b border-wheat-200 bg-wheat-50 px-4 py-3">
                <h3 class="font-bold text-darkbrown-900">Daftar Alat</h3>
                <span class="text-xs text-darkbrown-500">
                    {{ $rental->details->count() }} Unit Disewa
                </span>
            </div>
            <ul class="divide-y divide-wheat-100">
                @foreach($rental->details as $detail)
                    <li class="flex items-center justify-between gap-3 px-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-darkbrown-900">
                                {{ $detail->masterItem?->name ?? 'Peralatan Camping' }}
                            </p>
                            @if($detail->itemUnit)
                                <p class="mt-0.5 text-xs font-mono text-darkbrown-500">{{ $detail->itemUnit->unit_code }}</p>
                            @endif
                        </div>
                        <span class="shrink-0 text-right text-xs font-semibold text-darkbrown-700">
                            Status: {{ $unitStatus }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <section class="rounded-2xl border border-wheat-200 bg-white p-4">
            <h3 class="mb-3 flex items-center gap-2 font-bold text-darkbrown-900">
                <i data-lucide="calendar-days" class="h-4 w-4 text-darkbrown-500"></i>
                Jadwal Penyewaan
            </h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-darkbrown-500">Tanggal pengambilan</dt>
                    <dd class="text-right font-semibold text-darkbrown-900">{{ $fmtDate($rental->pickup_date) }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-darkbrown-500">Tanggal pengembalian</dt>
                    <dd class="text-right font-semibold text-darkbrown-900">{{ $fmtDate($rental->return_date) }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-darkbrown-500">Durasi sewa</dt>
                    <dd class="text-right font-semibold text-darkbrown-900">{{ $rental->rental_days }} hari</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-darkbrown-500">Metode penerimaan</dt>
                    <dd class="text-right font-semibold text-darkbrown-900">
                        {{ $rental->pickup_method === 'delivery' ? 'Diantar kurir' : 'Ambil di toko' }}
                    </dd>
                </div>
            </dl>
        </section>

        @if($rental->pickup_method === 'delivery' && $rental->delivery_address)
            <section class="rounded-2xl border border-wheat-200 bg-white p-4">
                <h3 class="mb-3 flex items-center gap-2 font-bold text-darkbrown-900">
                    <i data-lucide="map-pin" class="h-4 w-4 text-darkbrown-500"></i>
                    Lokasi Pengantaran
                </h3>
                <p class="text-sm leading-relaxed text-darkbrown-700">{{ $rental->delivery_address }}</p>
            </section>
        @elseif($rental->vendor)
            <section class="rounded-2xl border border-wheat-200 bg-white p-4">
                <h3 class="mb-3 flex items-center gap-2 font-bold text-darkbrown-900">
                    <i data-lucide="map-pin" class="h-4 w-4 text-darkbrown-500"></i>
                    Lokasi Pengambilan
                </h3>
                <p class="text-sm font-semibold text-darkbrown-900">{{ $rental->vendor->name }}</p>
                <p class="mt-1 text-sm leading-relaxed text-darkbrown-600">{{ $rental->vendor->address }}</p>
            </section>
        @endif
    </div>

    <section class="rounded-2xl border border-wheat-200 bg-wheat-50/70 p-4">
        <h3 class="mb-2 flex items-center gap-2 font-bold text-darkbrown-900">
            <i data-lucide="clipboard-list" class="h-4 w-4 text-darkbrown-500"></i>
            Instruksi Pengambilan
        </h3>
        <p class="text-sm leading-relaxed text-darkbrown-700">
            @if($rental->pickup_method === 'delivery')
                Siapkan KTP asli dan tunjukkan kode booking saat alat diserahkan.
            @else
                Bawa KTP asli dan tunjukkan kode booking berikut kepada kasir.
            @endif
        </p>
        <p class="mt-2 inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 font-mono text-sm font-bold text-darkbrown-900">
            <i data-lucide="hash" class="h-4 w-4 text-darkbrown-500"></i>
            {{ $rental->booking_code }}
        </p>

        <div class="mt-4 flex flex-col gap-2 border-t border-wheat-200 pt-3 sm:flex-row sm:items-center">
            <a href="{{ route('booking.receipt', $rental->booking_code) }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl border border-wheat-300 bg-white px-4 py-2.5 text-sm font-semibold text-darkbrown-800 transition hover:bg-wheat-100">
                <i data-lucide="receipt-text" class="h-4 w-4"></i>
                Lihat Receipt Pesanan
            </a>

            <div x-show="status === 'confirmed' || (status === 'pending' && isPaid)" x-cloak>
                <div x-data="{ reason: @js(old('cancel_reason', '')) }"
                     x-init="@if ($errors->hasAny(['cancel_reason', 'cancel_note', 'confirm_cancel'])) $nextTick(() => $refs.cancelDialog.showModal()) @endif">
                    <button type="button" @click="$refs.cancelDialog.showModal()"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50 sm:w-auto">
                        <i data-lucide="x-circle" class="h-4 w-4"></i>
                        Batalkan Pesanan
                    </button>

                    <dialog x-ref="cancelDialog"
                            class="w-[92vw] max-w-md rounded-2xl border border-wheat-200 p-0 shadow-xl backdrop:bg-black/50"
                            @click.self="$refs.cancelDialog.close()">
                        <form method="POST" action="{{ route('booking.cancel', $rental->booking_code) }}" class="space-y-4 p-5 sm:p-6">
                            @csrf

                            <div>
                                <h3 class="font-heading text-lg font-extrabold text-darkbrown-900">Batalkan Pesanan</h3>
                                <p class="mt-1 text-sm text-darkbrown-600">
                                    Pesanan <span class="font-mono font-bold">#{{ $rental->booking_code }}</span> akan dibatalkan dan alat dilepas kembali ke stok.
                                </p>
                            </div>

                            <div>
                                <label for="cancel_reason" class="mb-1.5 block text-sm font-semibold text-darkbrown-800">Alasan pembatalan</label>
                                <select id="cancel_reason" name="cancel_reason" x-model="reason" required
                                        class="w-full rounded-xl border border-wheat-300 bg-white px-3 py-2 text-sm text-darkbrown-900 focus:border-avocado-600 focus:ring-2 focus:ring-avocado-500/30">
                                    <option value="">Pilih alasan…</option>
                                    @foreach (config('booking.cancel_reasons', []) as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('cancel_reason') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="cancel_note" class="mb-1.5 block text-sm font-semibold text-darkbrown-800">
                                    Catatan <span x-show="reason !== 'other'" class="font-normal text-darkbrown-500">(opsional)</span>
                                </label>
                                <textarea id="cancel_note" name="cancel_note" rows="3" maxlength="500"
                                          :required="reason === 'other'"
                                          class="w-full rounded-xl border border-wheat-300 bg-white px-3 py-2 text-sm text-darkbrown-900 focus:border-avocado-600 focus:ring-2 focus:ring-avocado-500/30"
                                          placeholder="Ceritakan singkat alasan Anda">{{ old('cancel_note') }}</textarea>
                                @error('cancel_note') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                            </div>

                            <label class="flex items-start gap-2 text-sm text-darkbrown-700">
                                <input type="checkbox" name="confirm_cancel" value="1" required
                                       class="mt-0.5 rounded border-wheat-400 text-red-600 focus:ring-red-500">
                                <span>Saya mengerti pesanan ini akan dibatalkan.</span>
                            </label>
                            @error('confirm_cancel') <p class="text-sm text-red-700">{{ $message }}</p> @enderror

                            <div class="flex items-center justify-end gap-2 pt-1">
                                <button type="button" @click="$refs.cancelDialog.close()"
                                        class="rounded-xl border border-wheat-300 bg-wheat-100 px-4 py-2 text-sm font-bold text-darkbrown-800 transition hover:bg-wheat-200">
                                    Kembali
                                </button>
                                <button type="submit"
                                        class="rounded-xl border border-red-300 bg-white px-4 py-2 text-sm font-bold text-red-700 transition hover:bg-red-50">
                                    Ya, Batalkan Pesanan
                                </button>
                            </div>
                        </form>
                    </dialog>
                </div>
            </div>
        </div>
    </section>
</div>
