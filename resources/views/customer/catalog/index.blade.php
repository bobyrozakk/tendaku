<x-layouts.customer>
    <x-slot name="header">
        <div class="relative overflow-hidden rounded-3xl border border-wheat-200 bg-gradient-to-br from-wheat-100 via-[#FDFBF7] to-sunglow-100/80 px-5 py-5 shadow-tendaku-sm sm:px-6 sm:py-6">
            <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-sunglow-300/30 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-24 left-1/4 h-56 w-56 rounded-full bg-avocado-200/30 blur-3xl" aria-hidden="true"></div>

            <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0 max-w-2xl">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-avocado-300/70 bg-white/70 px-3 py-1 text-[11px] font-bold uppercase tracking-[.18em] text-avocado-800 backdrop-blur-sm">
                        <i data-lucide="compass" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        Katalog Outdoor
                    </span>

                    <h1 class="mt-2 font-heading text-2xl font-extrabold tracking-tight text-darkbrown-900 sm:text-3xl">
                        Perlengkapan camping, siap untuk petualanganmu.
                    </h1>

                    <p class="mt-1.5 max-w-xl text-sm leading-relaxed text-darkbrown-600">
                        Temukan tenda, sleeping bag, kompor, dan alat outdoor terawat. Semua unit dicek sebelum diserahkan, dengan jaminan KTP tanpa deposit uang.
                    </p>

                    <div class="-mx-5 mt-3 flex gap-2 overflow-x-auto px-5 pb-1 scrollbar-none sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0 sm:pb-0">
                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-wheat-300 bg-white/80 px-3 py-1.5 text-[11px] font-semibold text-darkbrown-800 shadow-sm">
                            <i data-lucide="shield-check" class="h-3.5 w-3.5 text-avocado-600" aria-hidden="true"></i>
                            Jaminan KTP, tanpa deposit
                        </span>
                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-wheat-300 bg-white/80 px-3 py-1.5 text-[11px] font-semibold text-darkbrown-800 shadow-sm">
                            <i data-lucide="sparkles" class="h-3.5 w-3.5 text-avocado-600" aria-hidden="true"></i>
                            Unit higienis &amp; terawat
                        </span>
                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-wheat-300 bg-white/80 px-3 py-1.5 text-[11px] font-semibold text-darkbrown-800 shadow-sm">
                            <i data-lucide="calendar-check" class="h-3.5 w-3.5 text-avocado-600" aria-hidden="true"></i>
                            Cek ketersediaan real-time
                        </span>
                    </div>
                </div>

                <div class="hidden shrink-0 items-center gap-3 rounded-2xl border border-white/70 bg-white/60 px-4 py-3 shadow-sm backdrop-blur-sm lg:flex">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-avocado-600 text-white shadow-sm">
                        <i data-lucide="tent-tree" class="h-5 w-5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[.16em] text-darkbrown-500">Siap dipakai</p>
                        <p class="text-sm font-extrabold text-darkbrown-900">Alat terawat, trip tenang.</p>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <livewire:customer.catalog-index />
</x-layouts.customer>
