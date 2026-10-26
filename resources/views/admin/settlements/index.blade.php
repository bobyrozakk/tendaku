<x-layouts.admin :title="'Settlement Vendor - Tendaku'">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <x-badge variant="dark" :dot="true">Super Administrator</x-badge>
                    <x-badge variant="avocado">Monetisasi Platform</x-badge>
                </div>

                <h1 class="text-2xl font-extrabold tracking-tight text-darkbrown-900 sm:text-3xl">
                    Settlement &amp; Pencairan Vendor
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-darkbrown-500">
                    Tinjau draft batch mingguan, setujui, catat bukti transfer, dan terbitkan invoice settlement vendor.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.pendapatan') }}" class="btn-tendaku-secondary !px-3.5 !py-2 text-xs">
                    Laporan Pendapatan
                </a>
                <a href="{{ route('dashboard') }}" class="btn-tendaku-secondary !px-3.5 !py-2 text-xs">
                    Kembali ke Dashboard
                </a>
            </div>
        </div>

        <livewire:admin.settlement-manager />
    </div>
</x-layouts.admin>
