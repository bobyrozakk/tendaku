<x-layouts.vendor>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                    Pickup / Serah Terima Barang
                </h1>
                <!-- <p class="text-xs text-darkbrown-500 mt-0.5">
                    Verifikasi identitas penyewa, penahanan jaminan fisik KTP, foto dokumentasi, dan serah terima unit
                </p> -->
            </div>
        </div>
    </x-slot>

    <div class="py-0">
        <livewire:kasir.pickup />
    </div>
</x-layouts.vendor>
