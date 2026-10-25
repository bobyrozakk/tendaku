<x-layouts.admin :title="'Dashboard Super Admin - Tendaku'">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <x-badge variant="dark" :dot="true">Super Administrator</x-badge>
                    <span class="text-xs font-medium text-darkbrown-400">Akses penuh seluruh tenant Tendaku</span>
                </div>

                <h1 class="text-2xl font-extrabold tracking-tight text-darkbrown-900 sm:text-3xl">
                    Dashboard Super Admin
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-darkbrown-500">
                    Pantau performa GMV, tren transaksi, dan kelola persetujuan vendor dari satu halaman.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.pendapatan') }}" class="btn-tendaku-secondary !px-3.5 !py-2 text-xs">
                    Laporan Pendapatan
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </a>
                <a href="#kelola-vendor" class="btn-tendaku-primary !px-3.5 !py-2 text-xs">
                    Kelola Vendor
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                </a>
            </div>
        </div>

        <livewire:admin.gmv-dashboard />
        <livewire:admin.vendor-manager />
    </div>
</x-layouts.admin>
