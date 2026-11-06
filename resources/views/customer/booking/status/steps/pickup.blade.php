<div class="space-y-3">
    {{-- Banner Status Pembayaran --}}
    <div x-show="isPaid || (status !== 'pending' && !['cancelled', 'expired'].includes(status))"
         class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl bg-avocado-50 border border-avocado-200">
        <div class="flex items-center gap-2.5 text-xs sm:text-sm font-semibold text-avocado-900">
            <i data-lucide="circle-check" class="h-4 w-4 text-avocado-600"></i>
            <span>Pembayaran lunas terverifikasi</span>
        </div>
    </div>

    {{-- Banner Status Validasi Alat oleh Kasir --}}
    @if($rental->status === 'pending')
        <div class="flex items-start gap-3 p-3.5 rounded-xl bg-amber-50/90 border border-amber-300 text-amber-900 shadow-sm">
            <div class="p-1 rounded-lg bg-amber-100 text-amber-800 shrink-0 mt-0.5">
                <i data-lucide="hourglass" class="h-4 w-4 text-amber-700 animate-pulse"></i>
            </div>
            <div class="space-y-0.5 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs sm:text-sm font-extrabold text-amber-950">Status Alat: Pending Validasi Kasir</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-200/70 text-amber-900 border border-amber-300">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                        Menunggu Pengecekan
                    </span>
                </div>
                <p class="text-xs text-amber-800 leading-relaxed">
                    Kasir toko sedang memeriksa dan memvalidasi unit fisik alat camping yang Anda sewa. Pesanan akan otomatis berubah status menjadi <strong>Tervalidasi (Siap Pickup)</strong> setelah kasir selesai melakukan pencocokan barcode alat.
                </p>
            </div>
        </div>
    @elseif(in_array($rental->status, ['confirmed', 'picked_up', 'returned', 'completed']))
        <div class="flex items-start gap-3 p-3.5 rounded-xl bg-emerald-50 border border-emerald-300 text-emerald-900 shadow-sm">
            <div class="p-1 rounded-lg bg-emerald-100 text-emerald-800 shrink-0 mt-0.5">
                <i data-lucide="check-check" class="h-4 w-4 text-emerald-700"></i>
            </div>
            <div class="space-y-0.5 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs sm:text-sm font-extrabold text-emerald-950">Status Alat: Tervalidasi Kasir</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-200/80 text-emerald-900 border border-emerald-300">
                        <i data-lucide="check" class="w-3 h-3 text-emerald-700"></i>
                        Siap Diambil
                    </span>
                </div>
                <p class="text-xs text-emerald-800 leading-relaxed">
                    Semua unit fisik alat telah selesai divalidasi dan dicocokkan barcode-nya oleh kasir toko. Alat siap diserahterimakan saat Anda tiba di outlet!
                </p>
            </div>
        </div>
    @endif

    {{-- Daftar Rincian Unit / Alat yang Divalidasi --}}
    @if($rental->details && $rental->details->isNotEmpty())
        <div class="rounded-xl border border-wheat-200 bg-white overflow-hidden">
            <div class="px-3.5 py-2.5 bg-wheat-50 border-b border-wheat-200 flex items-center justify-between">
                <span class="text-xs font-bold text-darkbrown-800 flex items-center gap-1.5">
                    <i data-lucide="boxes" class="w-3.5 h-3.5 text-avocado-600"></i>
                    Daftar Alat Pesanan ({{ $rental->details->count() }} Unit)
                </span>
                <span class="text-[11px] font-semibold text-darkbrown-600">
                    {{ $rental->status === 'pending' ? 'Menunggu Kasir' : 'Alat Siap' }}
                </span>
            </div>
            <ul class="divide-y divide-wheat-100 text-xs">
                @foreach($rental->details as $detail)
                    <li class="px-3.5 py-2.5 flex items-center justify-between gap-3">
                        <div class="min-w-0 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ $rental->status === 'pending' ? 'bg-amber-400' : 'bg-emerald-500' }}"></span>
                            <div>
                                <span class="font-bold text-darkbrown-900 block truncate">{{ $detail->masterItem?->name ?? 'Item Alat' }}</span>
                                @if($detail->itemUnit)
                                    <span class="text-[10px] text-darkbrown-500 font-mono">Kode Unit: {{ $detail->itemUnit->unit_code }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            @if($rental->status === 'pending')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    <i data-lucide="clock" class="w-2.5 h-2.5"></i>
                                    Pending
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    <i data-lucide="check" class="w-2.5 h-2.5 text-emerald-600"></i>
                                    Tervalidasi
                                </span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
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
