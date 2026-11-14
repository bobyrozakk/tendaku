<x-layouts.admin :title="'Settlement Vendor - Tendaku'">
    <div class="space-y-6">
        <x-admin.page-header
            eyebrow="Super Administrator"
            title="Settlement Vendor"
            description="Tinjau batch pencairan mingguan, invoice komisi, dan persetujuan transfer vendor."
        >
            <x-slot:actions>
                <a href="{{ route('admin.pendapatan') }}" class="btn-admin-hero-secondary !px-4 !py-2.5 text-xs">
                    Laporan Pendapatan
                </a>
                <a href="{{ route('dashboard') }}" class="btn-tendaku-accent !px-4 !py-2.5 text-xs focus:ring-offset-darkbrown-900">
                    Dashboard
                </a>
            </x-slot:actions>
        </x-admin.page-header>

        <livewire:admin.settlement-manager />
    </div>
</x-layouts.admin>
