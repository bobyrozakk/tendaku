@props(['stock' => null])

<x-badge :variant="$stock === null ? 'wheat' : ($stock > 0 ? 'avocado' : 'danger')" :dot="true" {{ $attributes }}>
    @if($stock === null)
        Ketersediaan belum dapat diperiksa
    @elseif($stock > 0)
        Stok: {{ $stock }} Unit
    @else
        Tidak tersedia pada periode ini
    @endif
</x-badge>
