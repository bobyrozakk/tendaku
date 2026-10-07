<x-layouts.customer>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="font-extrabold text-2xl md:text-3xl text-darkbrown-900 tracking-tight font-heading">
                    ⛺ Katalog Peralatan Camping & Outdoor
                </h1>
                <p class="text-sm text-darkbrown-600 mt-1">
                    Temukan tenda, sleeping bag, kompor, & alat outdoor terawat dengan <span class="font-bold text-avocado-700">Jaminan KTP (Tanpa Deposit Uang)</span>.
                </p>
            </div>
            <div class="flex items-center gap-2 bg-avocado-50 border border-avocado-200 px-3.5 py-2 rounded-xl text-xs font-semibold text-avocado-900 shadow-sm self-start md:self-auto">
                <span class="w-2 h-2 rounded-full bg-avocado-500 animate-pulse"></span>
                <span>Unit Terverifikasi & Higienis</span>
            </div>
        </div>
    </x-slot>

    <livewire:customer.catalog-index />
</x-layouts.customer>
