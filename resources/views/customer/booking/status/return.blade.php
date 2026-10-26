{{-- Informasi pengembalian --}}
<dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
    <div>
        <dt class="text-xs text-darkbrown-500">Dikembalikan</dt>
        <dd class="font-bold text-darkbrown-900">{{ $rental->returned_at ? $fmtDateTime($rental->returned_at) : '-' }}</dd>
    </div>
    <div>
        <dt class="text-xs text-darkbrown-500">Jaminan</dt>
        <dd class="font-bold text-darkbrown-900">{{ $ktpLabel }}</dd>
    </div>
</dl>

@if($hasDenda)
    <div class="rounded-xl bg-red-50 border border-red-200 p-3.5 text-sm text-red-800">
        Ada denda yang perlu diselesaikan sebesar <strong>{{ $rupiah($fineAmount) }}</strong>.
    </div>
@endif

@if($canPayFine)
    <button type="button" @click="payNow()" :disabled="paying"
            class="w-full py-2.5 px-4 rounded-xl font-bold text-sm bg-red-600 hover:bg-red-700 text-white shadow transition disabled:opacity-70">
        Bayar denda ({{ $rupiah($fineAmount) }})
    </button>
@endif
