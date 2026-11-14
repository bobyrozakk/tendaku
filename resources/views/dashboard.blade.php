<x-layouts.admin :title="'Dashboard Super Admin - Tendaku'">
    <div class="space-y-5">
        <x-admin.page-header
            eyebrow="Super Administrator"
            title="Dashboard Operasional"
            description="Ringkasan performa GMV dan tata kelola vendor platform hari ini."
        >
            <x-slot:meta>
                <span class="inline-flex items-center gap-1.5 text-xs text-wheat-200/80">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                    {{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
                </span>
            </x-slot:meta>

            <x-slot:actions>
                <a href="{{ route('admin.pendapatan') }}" class="btn-admin-hero-secondary !px-4 !py-2.5 text-xs">
                    Pendapatan
                </a>
                <a href="#kelola-vendor" class="btn-tendaku-accent !px-4 !py-2.5 text-xs focus:ring-offset-darkbrown-900">
                    Kelola Vendor
                </a>
            </x-slot:actions>
        </x-admin.page-header>

        <livewire:admin.gmv-dashboard />
        <livewire:admin.vendor-manager />
    </div>
</x-layouts.admin>
