<div
    class="relative shrink-0"
    @keydown.escape.window="filtersOpen = false"
    @click.outside="filtersOpen = false"
>
    <button
        type="button"
        @click="filtersOpen = !filtersOpen"
        :aria-expanded="filtersOpen ? 'true' : 'false'"
        aria-controls="catalog-advanced-filters"
        aria-label="Filter"
        class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border px-4 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2 sm:w-auto {{ $advancedFilterCount > 0 ? 'border-avocado-800 bg-avocado-700 text-white hover:bg-avocado-800' : 'border-wheat-300 bg-wheat-100 text-darkbrown-800 hover:bg-wheat-200' }}"
    >
        <i data-lucide="sliders-horizontal" class="h-4 w-4" aria-hidden="true"></i>
        @if ($advancedFilterCount > 0)
            <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-white px-1 text-xs font-bold text-avocado-800" aria-hidden="true">
                {{ $advancedFilterCount }}
            </span>
        @endif
        <i
            data-lucide="chevron-down"
            class="h-4 w-4 transition-transform duration-200 motion-reduce:transition-none"
            :class="filtersOpen ? 'rotate-180' : ''"
            aria-hidden="true"
        ></i>
    </button>

    <div
        id="catalog-advanced-filters"
        role="dialog"
        aria-label="Filter katalog"
        x-cloak
        x-show="filtersOpen"
        x-transition.origin.top.right.duration.150ms
        class="absolute right-0 top-full z-40 mt-2 w-full space-y-5 rounded-2xl border border-wheat-200 bg-white p-4 shadow-tendaku-lg sm:w-[34rem] sm:p-5 sm:max-w-[calc(100vw-4rem)]"
    >
        @include('customer.catalog.partials.category-filter')

        @include('customer.catalog.partials.filter-fields')
    </div>
</div>
