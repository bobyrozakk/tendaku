<x-layouts.admin :title="'Laporan Pendapatan - Tendaku'">
    <div class="space-y-6">
        <x-admin.page-header
            class="no-print"
            eyebrow="Super Admin"
            title="Laporan Pendapatan Platform"
            description="Komisi, escrow, dan laba platform."
        >
            <x-slot:meta>
                <span class="inline-flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5 text-sunglow-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export CSV mengikuti filter
                </span>
            </x-slot:meta>

            <x-slot:actions>
                <a href="{{ route('admin.settlements') }}" class="btn-tendaku-accent !px-3.5 !py-2 text-xs focus:ring-offset-darkbrown-900">
                    Settlement
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
                <a href="{{ route('dashboard') }}" class="btn-admin-hero-secondary !px-3.5 !py-2 text-xs">
                    Dashboard
                </a>
            </x-slot:actions>
        </x-admin.page-header>

        <livewire:admin.revenue-dashboard />
    </div>
</x-layouts.admin>
