<x-layouts.vendor>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Alur Serah Terima & Pengembalian') }}
        </h2>
    </x-slot>

    <div class="py-6 space-y-6">
        <x-card title="Pickup & Return Flow">
            <livewire:vendor.pickup-return-flow />
        </x-card>
    </div>
</x-layouts.vendor>
