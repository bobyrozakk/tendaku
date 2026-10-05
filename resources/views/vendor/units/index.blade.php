<x-layouts.vendor>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 min-h-[40px]">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Manajemen Unit Barang') }}
            </h2>
            <div class="flex items-center">
                <x-primary-button variant="avocado" wire:click="$dispatch('open-add-unit-modal')">
                    + Tambah Unit (Scan/Input Barcode)
                </x-primary-button>
            </div>
        </div>
    </x-slot>

    <div class="py-0">
        <livewire:vendor.item-unit-manager />
    </div>
</x-layouts.vendor>