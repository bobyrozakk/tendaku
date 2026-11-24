<x-layouts.customer>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between lg:gap-10">
            <div class="min-w-0 max-w-2xl">
                <h1 class="font-heading text-2xl font-extrabold tracking-tight text-darkbrown-900 sm:text-3xl">
                    Katalog Outdoor
                </h1>

                <p class="mt-1 text-base font-semibold text-avocado-800 sm:text-lg">
                    Perlengkapan camping, siap untuk petualanganmu.
                </p>

                <p class="mt-3 max-w-xl text-sm leading-relaxed text-darkbrown-600">
                    Temukan tenda, sleeping bag, kompor, dan alat outdoor terawat. Semua unit dicek sebelum diserahkan, dengan jaminan KTP tanpa deposit uang.
                </p>
            </div>
        </div>
    </x-slot>

    <livewire:customer.catalog-index />
</x-layouts.customer>
