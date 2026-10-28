<x-layouts.vendor>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                    Pickup / Serah Terima Barang
                </h1>
            </div>

            {{-- Realtime Date & Clock Badge (Mentok Kanan) --}}
            <div x-data="{
                    currentDate: '',
                    currentTime: '',
                    updateTime() {
                        const now = new Date();
                        this.currentDate = now.toLocaleDateString('id-ID', {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric'
                        });
                        this.currentTime = now.toLocaleTimeString('id-ID', {
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit'
                        }).replace(/\./g, ':');
                    }
                }"
                x-init="updateTime(); setInterval(() => updateTime(), 1000)"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-wheat-100/90 border border-wheat-200/90 text-darkbrown-800 shadow-xs w-fit sm:self-auto self-start">
                <div class="flex items-center gap-1.5 text-xs font-bold text-darkbrown-800">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-avocado-700"></i>
                    <span x-text="currentDate"></span>
                </div>
                <span class="text-wheat-300">|</span>
                <div class="flex items-center gap-1.5 text-xs font-mono font-bold text-avocado-800">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span x-text="currentTime"></span>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-0">
        <livewire:kasir.pickup />
    </div>
</x-layouts.vendor>
