<div class="border-t border-wheat-100 pt-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="catalog-vendor" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-darkbrown-500">
                Vendor / Toko
            </label>
            <select
                id="catalog-vendor"
                wire:model.live="vendorId"
                class="input-tendaku h-11 w-full px-3 text-sm font-medium"
            >
                <option value="">Semua Vendor</option>
                @foreach ($vendors as $ven)
                    <option value="{{ $ven->id }}">{{ $ven->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <span class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-darkbrown-500">
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
                        class="input-tendaku h-11 w-full pl-9 pr-2 text-sm font-medium"
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
                        class="input-tendaku h-11 w-full pl-9 pr-2 text-sm font-medium"
                    />
                </div>
            </div>
        </div>

        <div class="sm:col-span-2">
            <span class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-darkbrown-500">
                Tanggal Sewa
            </span>
            <div class="grid grid-cols-2 gap-2">
                <label class="block min-w-0">
                    <span class="mb-1 block text-[11px] font-semibold text-darkbrown-600">Mulai</span>
                    <input
                        type="date"
                        wire:model.live="startDate"
                        min="{{ \Carbon\Carbon::today()->format('Y-m-d') }}"
                        class="input-tendaku h-11 w-full px-3 text-xs font-medium"
                    />
                </label>
                <label class="block min-w-0">
                    <span class="mb-1 block text-[11px] font-semibold text-darkbrown-600">Selesai</span>
                    <input
                        type="date"
                        wire:model.live="endDate"
                        min="{{ $startDate ?: \Carbon\Carbon::today()->format('Y-m-d') }}"
                        class="input-tendaku h-11 w-full px-3 text-xs font-medium"
                    />
                </label>
            </div>
        </div>

        <label class="flex cursor-pointer items-start justify-between gap-3 rounded-2xl border border-wheat-200 bg-wheat-50/60 p-3.5 transition hover:border-avocado-300 sm:col-span-2">
            <span class="flex min-w-0 items-start gap-2.5">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-avocado-700 shadow-sm">
                    <i data-lucide="calendar-check" class="h-4 w-4" aria-hidden="true"></i>
                </span>
                <span class="min-w-0">
                    <span class="block text-xs font-bold text-darkbrown-900">Hanya tampilkan yang tersedia</span>
                    <span class="mt-0.5 block text-[11px] leading-relaxed text-darkbrown-500">
                        Sembunyikan alat yang sudah penuh pada tanggal sewa yang dipilih.
                    </span>
                </span>
            </span>
            <input
                type="checkbox"
                wire:model.live="onlyAvailable"
                role="switch"
                class="peer sr-only"
            />
            <span
                class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-wheat-300 transition after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition after:content-[''] peer-checked:bg-avocado-600 peer-checked:after:translate-x-5 peer-focus-visible:ring-2 peer-focus-visible:ring-avocado-500 peer-focus-visible:ring-offset-2"
                aria-hidden="true"
            ></span>
        </label>
    </div>
</div>
