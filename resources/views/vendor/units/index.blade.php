<x-layouts.vendor>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                    Unit Owner
                </h1>
                <p class="text-xs text-darkbrown-500 mt-0.5">
                    Manajemen unit fisik individual, pencatatan barcode/serial, status operasional, dan kondisi alat
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-0">
        <livewire:vendor.item-unit-manager />
    </div>
</x-layouts.vendor>