<x-layouts.customer>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="font-extrabold text-2xl md:text-3xl text-darkbrown-900 tracking-tight font-heading">
                    ⛺ Prakiraan Cuaca Spot Camping
                </h1>
                <p class="text-sm text-darkbrown-600 mt-1">
                    Cek cuaca di <span class="font-bold text-avocado-700">Gunung Bromo</span>,
                    <span class="font-bold text-avocado-700">Ranu Kumbolo</span>, dan
                    <span class="font-bold text-avocado-700">Kota Batu</span> sebelum berangkat —
                    supaya Planner tidak kejutan cuaca di tengah perjalanan.
                </p>
            </div>
            <div class="flex items-center gap-2 bg-avocado-50 border border-avocado-200 px-3.5 py-2 rounded-xl text-xs font-semibold text-avocado-900 shadow-sm self-start md:self-auto">
                <span class="w-2 h-2 rounded-full bg-avocado-500 animate-pulse"></span>
                <span>Data Real-time Open-Meteo</span>
            </div>
        </div>
    </x-slot>

    <livewire:customer.weather-calendar />
</x-layouts.customer>
