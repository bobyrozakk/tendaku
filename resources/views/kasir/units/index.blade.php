<x-layouts.vendor>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                    Status Unit Alat
                </h1>
                <!-- <p class="text-xs text-darkbrown-500 mt-0.5">
                    Monitoring ketersediaan unit fisik alat camping (tersedia di toko, sedang disewa, atau dalam perbaikan)
                </p> -->
            </div>
        </div>
    </x-slot>

    <div class="py-0">
        <livewire:kasir.item-unit-list />
    </div>
</x-layouts.vendor>
