<x-layouts.admin :title="'Settlement Vendor - Tendaku'">
    <div class="space-y-6">
        <x-admin.page-header
            eyebrow="Super Admin"
            title="Settlement & Pencairan Vendor"
            description="Tinjau batch, invoice, dan bukti transfer vendor."
        >
            <x-slot:meta>
                <span class="inline-flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5 text-sunglow-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Batch mingguan Senin–Minggu
                </span>
            </x-slot:meta>

            <x-slot:actions>
                <a href="{{ route('admin.pendapatan') }}" class="btn-tendaku-accent !px-3.5 !py-2 text-xs focus:ring-offset-darkbrown-900">
                    Pendapatan
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
                <a href="{{ route('dashboard') }}" class="btn-admin-hero-secondary !px-3.5 !py-2 text-xs">
                    Dashboard
                </a>
            </x-slot:actions>
        </x-admin.page-header>

        <livewire:admin.settlement-manager />
    </div>
</x-layouts.admin>
