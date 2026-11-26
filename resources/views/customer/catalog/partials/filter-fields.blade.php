<div class="grid grid-cols-1 gap-x-3 gap-y-4 sm:grid-cols-2">
    <div>
        <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-darkbrown-500">
            Vendor / Toko
        </span>
        <x-dropdown-select
            wire:model.live="vendorId"
            size="md"
            label="Vendor / Toko"
            :options="collect($vendors)->pluck('name', 'id')->prepend('Semua Vendor', '')->all()"
        />
    </div>

    <div>
        <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-darkbrown-500">
            Rentang Harga / Hari
        </span>
        <div class="flex items-center gap-2">
            <div class="relative min-w-0 flex-1">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-darkbrown-400" aria-hidden="true">Rp</span>
                <input
                    type="number"
                    inputmode="numeric"
                    min="0"
                    step="1000"
                    wire:model.live.debounce.400ms="minPrice"
                    placeholder="Min"
                    aria-label="Harga minimum per hari"
                    class="input-tendaku h-10 w-full pl-9 pr-2 text-sm font-medium"
                />
            </div>
            <span class="text-xs text-darkbrown-400" aria-hidden="true">–</span>
            <div class="relative min-w-0 flex-1">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-darkbrown-400" aria-hidden="true">Rp</span>
                <input
                    type="number"
                    inputmode="numeric"
                    min="0"
                    step="1000"
                    wire:model.live.debounce.400ms="maxPrice"
                    placeholder="Maks"
                    aria-label="Harga maksimum per hari"
                    class="input-tendaku h-10 w-full pl-9 pr-2 text-sm font-medium"
                />
            </div>
        </div>
    </div>

    <div class="sm:col-span-2">
        <span class="mb-1.5 block text-[11px] font-bold uppercase tracking-wider text-darkbrown-500">
            Tanggal Sewa
        </span>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <label class="flex min-w-0 items-center gap-2">
                <span class="w-14 shrink-0 text-[11px] font-semibold text-darkbrown-600">Mulai</span>
                <input
                    type="date"
                    wire:model.live="startDate"
                    min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                    class="input-tendaku h-10 min-w-0 flex-1 px-3 text-xs font-medium"
                />
            </label>
            <label class="flex min-w-0 items-center gap-2">
                <span class="w-14 shrink-0 text-[11px] font-semibold text-darkbrown-600">Selesai</span>
                <input
                    type="date"
                    wire:model.live="endDate"
                    min="{{ $startDate ?: \Carbon\Carbon::today()->format('Y-m-d') }}"
                    class="input-tendaku h-10 min-w-0 flex-1 px-3 text-xs font-medium"
                />
            </label>
        </div>
    </div>

    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl bg-wheat-50 px-3 py-2.5 transition hover:bg-wheat-100/70 sm:col-span-2">
        <span class="min-w-0">
            <span class="block text-xs font-bold text-darkbrown-900">Hanya tampilkan yang tersedia</span>
            <span class="mt-0.5 block text-[11px] leading-snug text-darkbrown-500">
                Sembunyikan alat yang sudah penuh pada tanggal sewa yang dipilih.
            </span>
        </span>
        <input
            type="checkbox"
            wire:model.live="onlyAvailable"
            role="switch"
            class="peer sr-only"
        />
        <span
            class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-wheat-300 transition after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow-sm after:transition after:content-[''] peer-checked:bg-avocado-600 peer-checked:after:translate-x-4 peer-focus-visible:ring-2 peer-focus-visible:ring-avocado-500 peer-focus-visible:ring-offset-2"
            aria-hidden="true"
        ></span>
    </label>
</div>
