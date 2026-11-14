<x-layouts.admin :title="'Monitoring Transaksi - Tendaku'">
    <div class="space-y-6">
        <x-admin.page-header
            eyebrow="Super Administrator"
            title="Monitoring Transaksi"
            description="Pantau alur transaksi booking, pembayaran escrow, dan settlement vendor secara realtime."
        >
            <x-slot:actions>
                <a href="{{ route('admin.pendapatan') }}" class="btn-admin-hero-secondary !px-4 !py-2.5 text-xs">
                    Laporan Pendapatan
                </a>
                <a href="{{ route('admin.settlements') }}" class="btn-tendaku-accent !px-4 !py-2.5 text-xs focus:ring-offset-darkbrown-900">
                    Kelola Settlement
                </a>
            </x-slot:actions>
        </x-admin.page-header>

        <livewire:admin.transaction-monitor />
    </div>
</x-layouts.admin>
