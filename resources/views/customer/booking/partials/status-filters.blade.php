
<div class="overflow-x-auto border-b border-wheat-200 pb-2">
    <nav class="flex min-w-max items-center gap-2"
         aria-label="Filter status transaksi">

        @foreach (config('booking_filters') as $key => $label)
            @php
                $filterStatus = $key === 'all' ? '' : $key;
                $isActive = ($status ?? '') === $filterStatus;
            @endphp

            <a
                href="{{ route(
                    'customer.booking.index',
                    $filterStatus ? ['status' => $filterStatus] : []
                ) }}"
                @if ($isActive) aria-current="page" @endif
                class="rounded-xl border px-3.5 py-2 text-xs
                       font-bold whitespace-nowrap transition
                       {{ $isActive
                            ? 'border-darkbrown-900 bg-darkbrown-900 text-wheat-200'
                            : 'border-wheat-200 bg-white text-darkbrown-700 hover:bg-wheat-100' }}"
            >
                {{ $label }}
            </a>
        @endforeach
    </nav>
</div>
