<x-layouts.vendor>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                    Manajemen Kasir
                </h1>
                <p class="text-xs text-darkbrown-500 mt-0.5">
                    Kelola akun staf kasir toko, otorisasi akses transaksi POS, dan kelola status aktif/nonaktif akun
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-0">
        <livewire:vendor.cashier-manager />
    </div>
</x-layouts.vendor>
