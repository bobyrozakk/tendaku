{{-- Informasi serah terima / pickup --}}
<dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
    <div>
        <dt class="text-xs text-darkbrown-500">Tanggal ambil</dt>
        <dd class="font-bold text-darkbrown-900">{{ $fmtDate($rental->pickup_date) }}</dd>
    </div>
    <div>
        <dt class="text-xs text-darkbrown-500">Tanggal kembali</dt>
        <dd class="font-bold text-darkbrown-900">{{ $fmtDate($rental->return_date) }}</dd>
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
