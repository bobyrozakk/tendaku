<div class="rounded-2xl border border-wheat-200/90 bg-gradient-to-b from-white via-wheat-50/40 to-wheat-50/70 p-4 sm:p-5 md:p-6 shadow-sm shadow-darkbrown-900/5 space-y-4" aria-busy="true">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div class="flex items-start gap-3.5 min-w-0">
            <span class="w-11 h-11 rounded-2xl bg-gradient-to-br from-avocado-100 to-avocado-200/60 border border-avocado-300/50 text-avocado-800 flex items-center justify-center shadow-2xs shrink-0">
                <x-icon name="cloud-sun" class="w-6 h-6" />
            </span>
            <div>
                <h3 class="text-base sm:text-lg font-black text-darkbrown-900 tracking-tight">Cuaca Masa Sewa</h3>
                <p class="text-xs text-darkbrown-500 mt-1">
                    Menyiapkan prakiraan cuaca untuk periode sewa…
                </p>
            </div>
        </div>
        <div class="w-full sm:w-64 h-14 rounded-xl bg-wheat-100/60 animate-pulse"></div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
        @foreach (range(1, 4) as $skeleton)
            <div class="h-44 rounded-2xl border border-wheat-200/80 bg-white/70 p-4 animate-pulse flex flex-col justify-between">
                <div class="flex items-start justify-between gap-2">
                    <div class="space-y-2">
                        <div class="w-16 h-3 bg-wheat-200 rounded"></div>
                        <div class="w-20 h-4 bg-wheat-200 rounded"></div>
                    </div>
                    <div class="w-9 h-9 rounded-xl bg-wheat-200"></div>
                </div>
                <div class="w-24 h-4 bg-wheat-200 rounded"></div>
                <div class="space-y-2 pt-2 border-t border-wheat-100">
                    <div class="w-28 h-5 bg-wheat-200 rounded"></div>
                    <div class="w-full h-3 bg-wheat-100 rounded"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>
