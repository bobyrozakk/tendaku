@props([
    'title',
    'value',
    'description' => null,
    'icon' => null,
    'emphasis' => false,
])

@php
    $cardClasses = $emphasis
        ? 'bg-[#FDFBF7] border-avocado-500/75'
        : 'bg-white border-wheat-300/80';

    $titleClasses = $emphasis
        ? 'text-avocado-700'
        : 'text-darkbrown-700';

    $iconWrapClasses = $emphasis
        ? 'bg-tendaku-moss text-white'
        : 'bg-wheat-100/90 text-darkbrown-700';
@endphp

<article {{ $attributes->merge(['class' => "h-full rounded-xl border px-4 py-3.5 flex flex-col justify-between gap-2 {$cardClasses}"]) }}>
    <div class="flex items-center justify-between gap-2.5">
        <h2 class="text-xs font-semibold leading-tight {{ $titleClasses }}">
            {{ $title }}
        </h2>

        @if ($icon)
            <span class="w-7 h-7 rounded-md flex items-center justify-center shrink-0 {{ $iconWrapClasses }}">
                <x-icon :name="$icon" stroke-width="1.75" class="w-3.5 h-3.5" />
            </span>
        @endif
    </div>

    <div>
        <p class="font-admin-number text-xl sm:text-[22px] font-extrabold tracking-tight text-darkbrown-900 leading-tight break-words">
            {{ $value }}
        </p>

        @if ($description)
            <p class="mt-1 text-[11px] font-medium text-darkbrown-500 leading-snug">
                {{ $description }}
            </p>
        @endif
    </div>
</article>
