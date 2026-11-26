<div class="col-span-full md:col-span-12 grid grid-cols-1 md:grid-cols-12 gap-3 md:gap-x-4 md:gap-y-4" aria-busy="true">
    <div class="md:col-span-6 h-12 rounded-xl bg-wheat-100 animate-pulse"></div>
    <div class="col-span-full md:col-span-12 space-y-2 animate-pulse">
        <div class="flex gap-2 overflow-hidden">
            @foreach (range(1, 4) as $skeleton)
                <div class="grow shrink-0 basis-[calc((100%-1.5rem)/4)] min-w-[6.5rem] h-32 rounded-xl bg-wheat-100"></div>
            @endforeach
        </div>
        <p class="text-xs text-darkbrown-500">Menyiapkan prakiraan cuaca untuk periode sewa…</p>
    </div>
</div>
