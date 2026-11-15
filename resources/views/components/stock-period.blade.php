@props(['dateRange' => null])

<p {{ $attributes->merge(['class' => 'text-xs text-darkbrown-600']) }}>
    @if($dateRange)
        Ketersediaan untuk {{ \Carbon\Carbon::parse($dateRange['start_date'])->format('d M Y') }}
        sampai {{ \Carbon\Carbon::parse($dateRange['end_date'])->format('d M Y') }} (inklusif).
    @else
        Pilih rentang tanggal yang valid untuk memeriksa ketersediaan.
    @endif
</p>
