{{-- Informasi masa sewa --}}
<dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
    <div>
        <dt class="text-xs text-darkbrown-500">Diserahkan</dt>
        <dd class="font-bold text-darkbrown-900">{{ $rental->picked_up_at ? $fmtDateTime($rental->picked_up_at) : '-' }}</dd>
    </div>
    <div>
        <dt class="text-xs text-darkbrown-500">Wajib kembali</dt>
        <dd class="font-bold text-darkbrown-900">{{ $fmtDate($rental->return_date) }}</dd>
    </div>
    <div>
        <dt class="text-xs text-darkbrown-500">Jaminan</dt>
        <dd class="font-bold text-darkbrown-900">{{ $ktpLabel }}</dd>
    </div>
</dl>

@if($canPayFine)
    <button type="button" @click="payNow()" :disabled="paying"
            class="w-full py-2.5 px-4 rounded-xl font-bold text-sm bg-red-600 hover:bg-red-700 text-white shadow transition disabled:opacity-70">
        Bayar denda ({{ $rupiah($fineAmount) }})
    </button>
@endif
