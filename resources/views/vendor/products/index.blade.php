<x-layouts.vendor>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Manajemen Produk & Unit') }}
        </h2>
    </x-slot>

    <div class="py-6 space-y-6">
        <x-card title="Daftar Unit Barang">
            <livewire:vendor.item-unit-manager />
        </x-card>
    </div>
</x-layouts.vendor>
