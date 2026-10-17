<x-layouts.vendor>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 min-h-[40px]">
            <div>
                <h1 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                    Dashboard & POS Kasir
                </h1>
                <!-- <p class="text-xs text-darkbrown-500 mt-0.5">
                    Kelola antrean validasi pesanan, pemindaian barcode alat, dan transaksi langsung toko
                </p> -->
            </div>
        </div>
    </x-slot>

    <div class="py-0">
        <livewire:kasir.pos />
    </div>
</x-layouts.vendor>
