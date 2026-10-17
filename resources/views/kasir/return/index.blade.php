<x-layouts.vendor>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                    Inspeksi & Retur Barang
                </h1>
                <!-- <p class="text-xs text-darkbrown-500 mt-0.5">
                    Penerimaan pengembalian alat camping, inspeksi kondisi fisik, penghitungan denda, dan pelepasan KTP
                </p> -->
            </div>
        </div>
    </x-slot>

    <div class="py-0">
        <livewire:kasir.return-flow />
    </div>
</x-layouts.vendor>
