@props([
    'options' => [],
])

@php
    $activeOption = collect($options)->firstWhere('active', true)
        ?? ($options[0] ?? ['key' => '30_days', 'label' => '30 Hari', 'active' => true]);
@endphp

<div
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
    class="relative inline-block text-left"
>
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open ? 'true' : 'false'"
        aria-haspopup="menu"
        aria-label="Pilih periode analitik"
        class="inline-flex min-h-[44px] sm:min-h-[36px] min-w-[156px] items-center justify-between gap-2.5 rounded-lg border border-wheat-300 bg-[#FDFBF7] px-3 py-1.5 text-xs font-semibold text-darkbrown-800 hover:bg-wheat-100/80 hover:border-wheat-400 transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-600 focus-visible:ring-offset-1 cursor-pointer"
    >
        <span class="inline-flex items-center gap-2 min-w-0">
            <x-icon name="calendar" stroke-width="1.75" class="w-3.5 h-3.5 text-avocado-700 shrink-0" />
            <span class="truncate">{{ $activeOption['label'] }}</span>
        </span>

        <span
            class="shrink-0 transition-transform duration-150 text-darkbrown-500"
            :class="open ? 'rotate-180' : 'rotate-0'"
        >
            <x-icon name="chevron-down" stroke-width="1.75" class="w-3.5 h-3.5" />
        </span>
    </button>

    <nav
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-120"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        role="menu"
        aria-label="Filter periode analitik"
        class="absolute left-0 lg:left-auto lg:right-0 z-30 mt-1.5 w-44 rounded-lg border border-wheat-300/90 bg-white p-1 shadow-tendaku-sm"
    >
        @foreach ($options as $option)
            <a
                href="{{ route('vendor.owner.dashboard', ['period' => $option['key']]) }}"
                wire:navigate
                role="menuitem"
                @if ($option['active']) aria-current="page" @endif
                @click="open = false"
                class="flex min-h-[40px] sm:min-h-[34px] items-center justify-between gap-2 rounded-md px-2.5 py-1.5 text-xs transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-600 {{ $option['active'] ? 'bg-avocado-50 text-avocado-700 font-semibold' : 'text-darkbrown-700 font-medium hover:bg-[#FDFBF7] hover:text-darkbrown-900' }}"
            >
                <span>{{ $option['label'] }}</span>
                @if ($option['active'])
                    <x-icon name="check" stroke-width="2" class="w-3.5 h-3.5 text-avocado-700 shrink-0" />
                @endif
            </a>
        @endforeach
    </nav>
</div>
