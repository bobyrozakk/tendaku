<x-layouts.admin :title="'Laporan Pendapatan - Tendaku'">
    <div class="space-y-6">
        <x-admin.page-header
            class="no-print"
            eyebrow="Super Administrator"
            title="Laporan Pendapatan Platform"
            description="Ringkasan komisi platform, dana escrow, dan laba bersih dari seluruh vendor."
        >
            <x-slot:actions>
                <a href="{{ route('admin.settlements') }}" class="btn-admin-hero-secondary !px-4 !py-2.5 text-xs">
                    Kelola Settlement
                </a>
                <a href="{{ route('dashboard') }}" class="btn-tendaku-accent !px-4 !py-2.5 text-xs focus:ring-offset-darkbrown-900">
                    Dashboard
                </a>
            </x-slot:actions>
        </x-admin.page-header>

        <livewire:admin.revenue-dashboard />
    </div>
</x-layouts.admin>
