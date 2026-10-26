<x-layouts.customer>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="flex items-center gap-2.5 font-extrabold text-2xl md:text-3xl text-darkbrown-900 tracking-tight font-heading">
                    <x-icon name="cloud-sun" class="w-7 h-7 md:w-8 md:h-8 text-goldenrod-500" />
                    Prakiraan Cuaca Spot Camping
                </h1>
                <p class="text-sm text-darkbrown-600 mt-1">
                    Pantau cuaca <span class="font-bold text-avocado-700">{{ count(config('weather.spots', [])) }} lokasi</span> di seluruh Jawa Timur —
                    mulai dari Gunung Bromo, Ranu Kumbolo, Kawah Ijen, pantai, hingga kota besar —
                    supaya perjalanan Anda tidak kejutan cuaca.
                </p>
            </div>
            <div class="flex items-center gap-2 bg-avocado-50 border border-avocado-200 px-3.5 py-2 rounded-xl text-xs font-semibold text-avocado-900 shadow-sm self-start md:self-auto">
                <x-icon name="radio" class="w-3.5 h-3.5 text-avocado-600" />
                <span>Data Real-time Open-Meteo</span>
            </div>
        </div>
    </x-slot>

    <livewire:customer.weather-calendar />
</x-layouts.customer>
