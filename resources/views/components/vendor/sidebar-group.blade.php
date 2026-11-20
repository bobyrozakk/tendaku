@props([
    'id',
    'label',
    'icon',
    'active' => false,
    'items' => [],
])

@php
    $groupButtonClasses = 'group relative w-full flex items-center min-h-[44px] py-2.5 rounded-lg text-[13.5px] leading-5 font-medium transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sunglow-300 focus-visible:ring-offset-1 focus-visible:ring-offset-[#1E1A10]';

    $activeSummaryClasses = $active
        ? 'text-wheat-100 hover:bg-darkbrown-700'
        : 'text-wheat-300 hover:bg-darkbrown-700 hover:text-wheat-100';

    $iconColorClasses = $active
        ? 'text-goldenrod-500'
        : 'text-wheat-300/80 group-hover:text-wheat-100';
@endphp

<div class="relative group/group">
    <button
        type="button"
        @click="toggleGroup('{{ $id }}')"
        :aria-expanded="isExpanded && openGroups['{{ $id }}'] ? 'true' : 'false'"
        aria-controls="owner-submenu-{{ $id }}"
        title="{{ $label }}"
        class="{{ $groupButtonClasses }} {{ $activeSummaryClasses }}"
        :class="{
            'px-3 justify-between gap-3': isExpanded,
            'px-0 justify-center': !isExpanded,
            'bg-tendaku-moss/25 border border-tendaku-moss': !isExpanded && {{ $active ? 'true' : 'false' }},
        }"
    >
        <span class="flex items-center min-w-0" :class="isExpanded ? 'gap-3' : 'justify-center'">
            <x-icon :name="$icon" stroke-width="1.75" class="w-[18px] h-[18px] {{ $iconColorClasses }}" />
            <span
                x-show="isExpanded"
                class="truncate whitespace-nowrap"
            >
                {{ $label }}
            </span>
        </span>

        <span
            x-show="isExpanded"
            class="shrink-0 transition-transform duration-150"
            :class="openGroups['{{ $id }}'] ? 'rotate-180' : 'rotate-0'"
        >
            <x-icon
                name="chevron-down"
                stroke-width="1.75"
                class="w-4 h-4 text-darkbrown-300 group-hover:text-wheat-200"
            />
        </span>
    </button>

    <div
        x-show="!isExpanded"
        x-cloak
        role="tooltip"
        class="hidden lg:group-hover/group:block lg:group-focus-within/group:block absolute left-full top-1/2 -translate-y-1/2 ml-2.5 px-2.5 py-1.5 rounded-md bg-darkbrown-900 text-wheat-100 text-xs font-medium whitespace-nowrap border border-darkbrown-700 z-50 pointer-events-none"
    >
        {{ $label }}
    </div>

    <div
        id="owner-submenu-{{ $id }}"
        x-show="isExpanded && openGroups['{{ $id }}']"
        x-cloak
        class="mt-1 ml-5 pl-3 border-l border-darkbrown-700/80 space-y-0.5"
    >
        @foreach ($items as $item)
            <x-vendor.sidebar-item
                :label="$item['label']"
                :icon="$item['icon']"
                :href="$item['href']"
                :active="$item['active'] ?? false"
                :submenu="true"
            />
        @endforeach
    </div>
</div>
