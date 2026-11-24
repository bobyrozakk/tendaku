<div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <h2 id="catalog-results-heading" class="font-heading text-xl font-extrabold tracking-tight text-darkbrown-900">
                Daftar Peralatan Tersedia
            </h2>
            <span wire:loading class="animate-pulse text-[11px] font-semibold text-avocado-700">
                Memuat data...
            </span>
        </div>
        <p class="mt-1 text-xs text-darkbrown-600" role="status" aria-live="polite">
            @if ($items->total() > 0)
                Menampilkan {{ $items->firstItem() }}–{{ $items->lastItem() }} dari {{ $items->total() }} unit siap sewa untuk camping kamu.
            @else
                Tidak ada peralatan yang ditemukan.
            @endif
        </p>
        <div class="mt-1 flex items-start gap-1.5 text-xs text-darkbrown-600">
            <i data-lucide="calendar" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-avocado-600" aria-hidden="true"></i>
            <x-stock-period :date-range="$dateRange" />
        </div>
    </div>

    <div class="flex w-full shrink-0 items-center gap-2 sm:w-auto">
        <span class="text-[11px] font-bold uppercase tracking-wider text-darkbrown-500">Urutkan</span>
        <div class="relative flex-1 sm:flex-none">
            <i data-lucide="arrow-up-down" class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-darkbrown-400" aria-hidden="true"></i>
            <select
                id="catalog-sort"
                wire:model.live="sort"
                aria-label="Urutkan peralatan"
                class="input-tendaku h-10 w-full pl-9 pr-8 text-xs font-semibold sm:min-w-[10.5rem]"
            >
                <option value="latest">Terbaru</option>
                <option value="price_asc">Harga: Termurah</option>
                <option value="price_desc">Harga: Termahal</option>
                <option value="name_asc">Nama (A-Z)</option>
            </select>
        </div>
    </div>
</div>
