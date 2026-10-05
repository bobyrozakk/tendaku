<x-layouts.vendor>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 min-h-[40px]">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('Pickup / Serah Terima') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-0">
        <livewire:vendor.pickup />
    </div>
</x-layouts.vendor>