<div class="space-y-3 animate-pulse" aria-busy="true">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-wheat-200"></div>
        <div class="space-y-2">
            <div class="w-28 h-5 bg-wheat-200 rounded"></div>
            <div class="w-36 h-3 bg-wheat-100 rounded"></div>
        </div>
    </div>
    <div class="rounded-2xl bg-white border border-wheat-200 p-4 space-y-3">
        <div class="w-28 h-4 bg-wheat-200 rounded"></div>
        <div class="grid grid-cols-3 gap-3">
            @foreach (range(1, 3) as $skeleton)
                <div class="space-y-2 flex flex-col items-center">
                    <div class="w-14 h-3 bg-wheat-100 rounded"></div>
                    <div class="w-7 h-7 rounded-full bg-wheat-200"></div>
                    <div class="w-16 h-4 bg-wheat-200 rounded"></div>
                    <div class="w-10 h-3 bg-wheat-100 rounded"></div>
                </div>
            @endforeach
        </div>
    </div>
    <p class="text-xs text-darkbrown-500">Menyiapkan prakiraan cuaca untuk periode sewa…</p>
</div>
