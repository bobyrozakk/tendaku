<x-layouts.admin :title="'Laporan Pendapatan - Tendaku'">
    <div class="space-y-6">
        <div class="no-print flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <x-badge variant="dark" :dot="true">Super Administrator</x-badge>
                    <x-badge variant="goldenrod">Monetisasi Platform</x-badge>
                </div>

                <h1 class="text-2xl font-extrabold tracking-tight text-darkbrown-900 sm:text-3xl">
                    Laporan Pendapatan Platform
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-darkbrown-500">
                    Pantau komisi, MDR, escrow, laba platform, dan transaksi lintas vendor. Export CSV mengikuti filter yang aktif.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.settlements') }}" class="btn-tendaku-primary !px-3.5 !py-2 text-xs">
                    Kelola Settlement
                </a>
                <a href="{{ route('dashboard') }}" class="btn-tendaku-secondary !px-3.5 !py-2 text-xs">
                    Kembali ke Dashboard
                </a>
            </div>
        </div>

        <livewire:admin.revenue-dashboard />
    </div>
</x-layouts.admin>
