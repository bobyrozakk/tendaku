<x-layouts.customer>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detail Produk') }}
        </h2>
    </x-slot>

    <div class="py-6 space-y-6">
        <x-card title="Informasi Peralatan">
            <x-weather-badge condition="Cerah Berawan" temp="22°C" />
            <div class="mt-4">
                <livewire:customer.product-booking />
            </div>
        </x-card>
    </div>
</x-layouts.customer>
