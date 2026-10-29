{{-- Informasi serah terima / pickup --}}
@if($status !== 'pending' && !in_array($status, ['cancelled', 'expired']))
    <div x-show="isPaid || (status !== 'pending' && !['cancelled', 'expired'].includes(status))"
         class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 rounded-xl bg-avocado-50 border border-avocado-200">
        <div class="flex items-center gap-2.5 text-xs sm:text-sm font-semibold text-avocado-900">
            <i data-lucide="circle-check" class="h-4 w-4"></i>
            <span>Pembayaran lunas terverifikasi</span>
        </div>
    </div>
@endif

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
