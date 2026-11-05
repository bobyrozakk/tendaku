<x-layouts.admin :title="'Monitoring Transaksi - Tendaku'">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <x-badge variant="dark" :dot="true">Super Administrator</x-badge>
                    <x-badge variant="goldenrod">Monitoring Monetisasi</x-badge>
                </div>

                <h1 class="text-2xl font-extrabold tracking-tight text-darkbrown-900 sm:text-3xl">
                    Monitoring Transaksi
                </h1>
                <p class="mt-1 max-w-3xl text-sm text-darkbrown-500">
                    Pantau alur normal dari booking customer, pembayaran, komisi platform, rental selesai,
                    invoice vendor, sampai pencairan dana. Komisi <span class="font-semibold text-goldenrod-700">Accrued</span>
                    berarti sudah tercatat tetapi belum masuk batch; invoice terbit saat batch disetujui.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.pendapatan') }}" class="btn-tendaku-secondary !px-3.5 !py-2 text-xs">
                    Laporan Pendapatan
                </a>
                <a href="{{ route('admin.settlements') }}" class="btn-tendaku-primary !px-3.5 !py-2 text-xs">
                    Kelola Settlement
                </a>
            </div>
        </div>

        <livewire:admin.transaction-monitor />
    </div>
</x-layouts.admin>
