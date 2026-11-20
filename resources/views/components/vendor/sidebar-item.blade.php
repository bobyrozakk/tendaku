@props([
    'label',
    'icon',
    'href',
    'active' => false,
    'submenu' => false,
])

@php
    $iconClass = $submenu ? 'w-4 h-4' : 'w-[18px] h-[18px]';
    $heightClass = $submenu ? 'min-h-[40px] py-2' : 'min-h-[44px] py-2.5';

    $baseClasses = 'group relative flex items-center rounded-lg text-[13.5px] leading-5 transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sunglow-300 focus-visible:ring-offset-1 focus-visible:ring-offset-[#1E1A10]';

    $stateClasses = $active
        ? 'bg-tendaku-moss text-white font-semibold'
        : 'text-wheat-300 font-medium hover:bg-darkbrown-700 hover:text-wheat-100';

    $iconColorClass = $active
        ? 'text-white'
        : 'text-wheat-300/80 group-hover:text-wheat-100';
@endphp

<div class="relative group/item">
    <a
        href="{{ $href }}"
        wire:navigate
        @if ($active) aria-current="page" @endif
        @click="if (window.innerWidth < 1024) mobileOpen = false"
        title="{{ $label }}"
        class="{{ $baseClasses }} {{ $heightClass }} {{ $stateClasses }}"
        :class="isExpanded ? 'px-3 gap-3 justify-start' : 'px-0 justify-center'"
    >
        <x-icon :name="$icon" stroke-width="1.75" class="{{ $iconClass }} {{ $iconColorClass }}" />

        <span
            x-show="isExpanded"
            class="truncate whitespace-nowrap flex-1"
        >
            {{ $label }}
        </span>
    </a>

    @unless ($submenu)
        <div
            x-show="!isExpanded"
            x-cloak
            role="tooltip"
            class="hidden lg:group-hover/item:block lg:group-focus-within/item:block absolute left-full top-1/2 -translate-y-1/2 ml-2.5 px-2.5 py-1.5 rounded-md bg-darkbrown-900 text-wheat-100 text-xs font-medium whitespace-nowrap border border-darkbrown-700 z-50 pointer-events-none"
        >
            {{ $label }}
        </div>
    @endunless
</div>
