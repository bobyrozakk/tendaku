<div x-show="isPaid || (status !== 'pending' && !['cancelled', 'expired'].includes(status))"
     class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl bg-avocado-50 border border-avocado-200">
    <div class="flex items-center gap-2.5 text-xs sm:text-sm font-semibold text-avocado-900">
        <i data-lucide="circle-check" class="h-4 w-4"></i>
        <span>Pembayaran lunas terverifikasi</span>
    </div>
</div>

<dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
    <div>
        <dt class="text-xs text-darkbrown-500">Tanggal ambil</dt>
        <dd class="font-bold text-darkbrown-900">{{ $fmtDate($rental->pickup_date) }}</dd>
    </div>
    <div>
        <dt class="text-xs text-darkbrown-500">Tanggal kembali</dt>
        <dd class="font-bold text-darkbrown-900">{{ $fmtDate($rental->return_date) }}</dd>
    </div>
    <div>
        <dt class="text-xs text-darkbrown-500">Durasi sewa</dt>
        <dd class="font-bold text-darkbrown-900">{{ $rental->rental_days }} hari</dd>
    </div>
    <div>
        <dt class="text-xs text-darkbrown-500">Cara menerima</dt>
        <dd class="font-bold text-darkbrown-900">{{ $rental->pickup_method === 'delivery' ? 'Diantar kurir' : 'Ambil di toko' }}</dd>
    </div>
</dl>

@if($rental->pickup_method === 'delivery' && $rental->delivery_address)
    <div class="rounded-xl bg-wheat-50 border border-wheat-200 p-3.5 text-sm">
        <div class="text-xs text-darkbrown-500">Alamat pengantaran</div>
        <div class="font-semibold text-darkbrown-900">{{ $rental->delivery_address }}</div>
    </div>
@elseif($rental->vendor)
    <div class="rounded-xl bg-wheat-50 border border-wheat-200 p-3.5 text-sm">
        <div class="text-xs text-darkbrown-500">Lokasi pengambilan</div>
        <div class="font-semibold text-darkbrown-900">{{ $rental->vendor->name }}</div>
        <div class="text-darkbrown-600">{{ $rental->vendor->address }}</div>
    </div>
@endif

<p class="text-sm text-darkbrown-600 leading-relaxed">
    Bawa <strong class="text-darkbrown-800">KTP asli</strong> dan tunjukkan kode booking <span class="font-mono font-bold text-darkbrown-900">#{{ $rental->booking_code }}</span> ke kasir vendor.
</p>
<div class="pt-2">
    <a href="{{ route('booking.receipt', $rental->booking_code) }}"
       class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold text-darkbrown-800 bg-wheat-100 hover:bg-wheat-200 border border-wheat-300 transition shadow-sm">
        <i data-lucide="receipt-text" class="h-4 w-4"></i>
        <span>Lihat receipt order</span>
    </a>
</div>

{{-- Batalkan pesanan: hanya sebelum barang diserahterimakan --}}
<div x-show="status === 'confirmed' || (status === 'pending' && isPaid)" x-cloak>
    <div x-data="{ reason: @js(old('cancel_reason', '')) }"
         x-init="@if ($errors->hasAny(['cancel_reason', 'cancel_note', 'confirm_cancel'])) $nextTick(() => $refs.cancelDialog.showModal()) @endif"
         class="pt-1">
        <button type="button" @click="$refs.cancelDialog.showModal()"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 transition shadow-sm">
            <i data-lucide="x-circle" class="h-4 w-4"></i>
            <span>Batalkan pesanan</span>
        </button>

        <dialog x-ref="cancelDialog"
                class="w-[92vw] max-w-md rounded-2xl p-0 border border-wheat-200 shadow-xl backdrop:bg-black/50"
                @click.self="$refs.cancelDialog.close()">
            <form method="POST" action="{{ route('booking.cancel', $rental->booking_code) }}" class="p-5 sm:p-6 space-y-4">
                @csrf

                <div>
                    <h3 class="font-heading text-lg font-extrabold text-darkbrown-900">Batalkan pesanan</h3>
                    <p class="mt-1 text-sm text-darkbrown-600">
                        Pesanan <span class="font-mono font-bold">#{{ $rental->booking_code }}</span> akan dibatalkan
                        dan alat yang dipesan dilepas kembali ke stok. Tindakan ini tidak dapat diurungkan.
                    </p>
                </div>

                <div>
                    <label for="cancel_reason" class="block text-sm font-semibold text-darkbrown-800 mb-1.5">Alasan pembatalan</label>
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
                    <label for="cancel_note" class="block text-sm font-semibold text-darkbrown-800 mb-1.5">
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
                            class="px-4 py-2 rounded-xl text-sm font-bold text-darkbrown-800 bg-wheat-100 hover:bg-wheat-200 border border-wheat-300 transition">
                        Kembali
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-red-600 hover:bg-red-700 transition">
                        Ya, batalkan pesanan
                    </button>
                </div>
            </form>
        </dialog>
    </div>
</div>

{{-- Tombol Simulasi Kasir / Testing Saja (hanya tampilan, TIDAK menulis ke database) --}}
@if(app()->environment('local'))
<div class="mt-4 pt-3 border-t border-dashed border-wheat-300">
    <button type="button"
            @click="activeNode = 'rental'; justDone = 'pickup'"
            class="px-3 py-1.5 rounded-lg text-xs font-bold text-avocado-800 bg-avocado-100 hover:bg-avocado-200 border border-avocado-300 transition">
        Selesaikan Serah Terima (test)
    </button>
</div>
@endif
