<div class="col-span-full md:col-span-12 grid grid-cols-1 md:grid-cols-12 gap-3 md:gap-x-4 md:gap-y-4"
     aria-busy="true"
     x-data="{ stalled: false }"
     x-init="setTimeout(() => stalled = true, 15000)">
    <div class="md:col-span-6 h-12 rounded-xl bg-wheat-100 animate-pulse"></div>
    <div class="col-span-full md:col-span-12 space-y-2">
        <div class="flex gap-2 overflow-hidden animate-pulse">
            @foreach (range(1, 4) as $skeleton)
                <div class="grow shrink-0 basis-[calc((100%-0.5rem)/2)] md:basis-[calc((100%-1.5rem)/4)] min-w-0 h-32 rounded-xl bg-wheat-100"></div>
            @endforeach
        </div>
        <p x-show="!stalled" class="text-xs text-darkbrown-500">Menyiapkan prakiraan cuaca untuk periode sewa…</p>
        <p x-show="stalled" x-cloak class="text-xs text-darkbrown-600">
            Prakiraan cuaca belum bisa dimuat.
            <button type="button" onclick="window.location.reload()" class="inline-flex min-h-[44px] items-center px-1 font-bold text-avocado-700 underline underline-offset-2">Muat ulang</button>
            <span class="text-darkbrown-500">Booking tetap bisa dilanjutkan.</span>
        </p>
    </div>
</div>
