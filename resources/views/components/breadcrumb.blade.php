@props([
    // [['label' => 'Beranda', 'url' => route('home')], ['label' => 'Halaman ini']]
    // Item terakhir (tanpa url) dianggap halaman aktif.
    'items' => [],
])

<nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'mb-1.5']) }}>
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-darkbrown-500">
        @foreach ($items as $item)
            @php
                $isCurrent = $loop->last || empty($item['url'] ?? null);
            @endphp

            <li class="flex min-w-0 items-center gap-2">
                @unless ($loop->first)
                    <span class="text-wheat-400" aria-hidden="true">/</span>
                @endunless

                @if ($isCurrent)
                    <span aria-current="page" class="inline-flex min-w-0 items-center font-semibold text-darkbrown-800">
                        <span class="truncate">{{ $item['label'] }}</span>
                    </span>
                @else
                    <a href="{{ $item['url'] }}" class="inline-flex min-w-0 items-center transition hover:text-avocado-600">
                        <span class="truncate">{{ $item['label'] }}</span>
                    </a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
