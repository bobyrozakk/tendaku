<form wire:submit.prevent role="search" class="relative min-w-0 flex-1">
    <label for="catalog-search" class="sr-only">Cari alat camping</label>
    <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-darkbrown-400" aria-hidden="true"></i>
    <input
        type="search"
        id="catalog-search"
        name="search"
        wire:model.live.debounce.300ms="search"
        placeholder="Cari tenda, kompor, carrier..."
        autocomplete="off"
        class="input-tendaku h-11 w-full pl-11 pr-16 text-sm font-medium [&::-webkit-search-cancel-button]:appearance-none"
    />
    <div class="absolute inset-y-0 right-0 flex items-center gap-1 pr-2">
        <span
            wire:loading
            wire:target="search"
            class="mr-1 inline-block h-4 w-4 animate-spin rounded-full border-2 border-avocado-600 border-t-transparent"
            aria-hidden="true"
        ></span>
        @if ($hasSearch)
            <button
                type="button"
                wire:click="$set('search', '')"
                class="inline-flex h-11 w-11 sm:h-8 sm:w-8 items-center justify-center rounded-full text-darkbrown-400 transition hover:bg-wheat-100 hover:text-darkbrown-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500"
                title="Hapus Pencarian"
                aria-label="Hapus pencarian"
            >
                <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
            </button>
        @endif
    </div>
</form>
