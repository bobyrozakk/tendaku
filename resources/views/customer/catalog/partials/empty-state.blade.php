<div class="rounded-2xl border border-dashed border-wheat-300 px-5 py-10 text-center sm:px-8">
    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-wheat-100 text-darkbrown-600">
        <i data-lucide="search-x" class="h-8 w-8" aria-hidden="true"></i>
    </div>
    <h3 class="mt-4 font-heading text-lg font-bold text-darkbrown-900">Belum ada alat yang cocok</h3>
    <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-darkbrown-600">
        @if ($hasSearch)
            Tidak ada hasil untuk “{{ $search }}”. Coba kata kunci lain atau hapus beberapa filter.
        @elseif ($hasDates && (! $hasStartDate || ! $hasEndDate))
            Lengkapi tanggal mulai dan selesai untuk memeriksa ketersediaan alat.
        @elseif ($onlyAvailable)
            Tidak ada alat yang tersedia pada tanggal yang dipilih. Coba geser tanggal atau nonaktifkan filter ketersediaan.
        @else
            Tidak ada alat camping yang cocok dengan filter yang dipilih saat ini.
        @endif
    </p>

    <div class="mt-6 flex flex-wrap justify-center gap-2">
        <button type="button" wire:click="resetFilters" class="btn-tendaku-primary min-h-[44px] px-5 text-sm">
            <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
            Lihat Semua Peralatan
        </button>
        @if ($hasSearch)
            <button type="button" wire:click="$set('search', '')" class="btn-tendaku-secondary min-h-[44px] px-4 text-sm">
                Hapus pencarian
            </button>
        @endif
    </div>

    @if ($categories->isNotEmpty())
        <div class="mt-7 border-t border-wheat-200 pt-5">
            <p class="text-xs font-bold uppercase tracking-[.18em] text-darkbrown-500">Coba kategori lain</p>
            <div class="mt-3 flex flex-wrap justify-center gap-2">
                @foreach ($categories->take(4) as $cat)
                    <button
                        type="button"
                        wire:click="selectCategory({{ $cat->id }})"
                        class="inline-flex min-h-[44px] sm:min-h-[2.25rem] items-center gap-1.5 rounded-xl border border-wheat-300 bg-white px-3 py-1.5 text-xs font-semibold text-darkbrown-700 transition hover:border-avocado-400 hover:bg-wheat-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2"
                    >
                        <i data-lucide="tag" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        {{ $cat->name }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>
