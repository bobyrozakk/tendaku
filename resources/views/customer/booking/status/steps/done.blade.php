{{-- Ringkasan setelah sewa selesai --}}
<dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
    <div>
        <dt class="text-xs text-darkbrown-500">Total akhir</dt>
        <dd class="font-extrabold text-darkbrown-900">{{ $rupiah($totalFinal) }}</dd>
    </div>
    <div>
        <dt class="text-xs text-darkbrown-500">Selesai pada</dt>
        <dd class="font-bold text-darkbrown-900">{{ $fmtDateTime($rental->updated_at) }}</dd>
    </div>
</dl>
