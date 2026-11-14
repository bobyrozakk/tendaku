<x-layouts.admin :title="'Dashboard Super Admin - Tendaku'">
    <div class="space-y-5">
        <x-admin.page-header
            title="Dashboard Super Admin"
            description="Ringkasan operasional platform hari ini."
        >
            <x-slot:meta>
                <span class="inline-flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5 text-sunglow-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5 text-sunglow-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Sinkron 15 detik
                </span>
            </x-slot:meta>

            <x-slot:actions>
                <a href="{{ route('admin.pendapatan') }}" class="btn-admin-hero-secondary !px-3.5 !py-2 text-xs">
                    Pendapatan
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </a>
                <a href="#kelola-vendor" class="btn-tendaku-accent !px-3.5 !py-2 text-xs focus:ring-offset-darkbrown-900">
                    Kelola Vendor
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                </a>
            </x-slot:actions>
        </x-admin.page-header>

        <livewire:admin.gmv-dashboard />
        <livewire:admin.vendor-manager />
    </div>
</x-layouts.admin>
