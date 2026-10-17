<div class="space-y-5"
     x-data="{ confirmOpen: false, confirmUnitId: null, confirmUnitCode: '' }"
     @open-confirm-maintenance.window="confirmOpen = true; confirmUnitId = $event.detail.id; confirmUnitCode = $event.detail.code">

    {{-- ═══════════════════════════════════════════════════ --}}
    {{-- 1. KPI CARDS — Modern Gradient Style               --}}
    {{-- ═══════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Total Unit --}}
        <div class="relative overflow-hidden rounded-2xl p-5 flex flex-col justify-between min-h-[110px]"
             style="background: linear-gradient(135deg, #2b2202 0%, #3d3000 60%, #4a3c00 100%); box-shadow: 0 4px 20px rgba(43,34,2,0.25);">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest mb-1" style="color: rgba(245,233,200,0.55);">Total Unit Fisik</p>
                    <p class="text-4xl font-black tracking-tight leading-none" style="color: #f5e9c8;">{{ $totalAll }}</p>
                </div>
                <div class="p-2 rounded-xl" style="background: rgba(245,233,200,0.1);">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: rgba(245,233,200,0.6);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
            <p class="text-[10px] mt-3" style="color: rgba(245,233,200,0.45);">Semua inventaris terdaftar</p>
            {{-- decorative orb --}}
            <div class="absolute -right-4 -bottom-4 w-20 h-20 rounded-full opacity-10" style="background: #f5e9c8;"></div>
        </div>

        {{-- Tersedia --}}
        <button wire:click="setTab('available')"
                class="relative overflow-hidden rounded-2xl p-5 flex flex-col justify-between min-h-[110px] text-left transition-all duration-200 {{ $filterStatus === 'available' ? 'ring-2 ring-avocado-500 ring-offset-1' : '' }}"
                style="background: linear-gradient(135deg, #4e6506 0%, #5c7608 60%, #6c8b08 100%); box-shadow: 0 4px 20px rgba(78,101,6,0.3);">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-200">Tersedia di Toko</p>
                    </div>
                    <p class="text-4xl font-black tracking-tight leading-none text-white">{{ $totalAvailable }}</p>
                </div>
                <div class="p-2 rounded-xl bg-white/10">
                    <svg class="w-5 h-5 text-emerald-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-[10px] mt-3 text-emerald-300">Siap disewakan sekarang</p>
            <div class="absolute -right-4 -bottom-4 w-20 h-20 rounded-full opacity-10 bg-white"></div>
        </button>

        {{-- Sedang Disewa --}}
        <button wire:click="setTab('rented')"
                class="relative overflow-hidden rounded-2xl p-5 flex flex-col justify-between min-h-[110px] text-left transition-all duration-200 {{ $filterStatus === 'rented' ? 'ring-2 ring-amber-400 ring-offset-1' : '' }}"
                style="background: linear-gradient(135deg, #92610a 0%, #a87012 60%, #bf8018 100%); box-shadow: 0 4px 20px rgba(146,97,10,0.3);">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-300 animate-pulse"></span>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-amber-200">Sedang Disewa</p>
                    </div>
                    <p class="text-4xl font-black tracking-tight leading-none text-white">{{ $totalRented }}</p>
                </div>
                <div class="p-2 rounded-xl bg-white/10">
                    <svg class="w-5 h-5 text-amber-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-[10px] mt-3 text-amber-300">Unit dibawa pelanggan</p>
            <div class="absolute -right-4 -bottom-4 w-20 h-20 rounded-full opacity-10 bg-white"></div>
        </button>

        {{-- Perbaikan --}}
        <button wire:click="setTab('maintenance')"
                class="relative overflow-hidden rounded-2xl p-5 flex flex-col justify-between min-h-[110px] text-left transition-all duration-200 {{ $filterStatus === 'maintenance' ? 'ring-2 ring-red-400 ring-offset-1' : '' }}"
                style="background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 60%, #b91c1c 100%); box-shadow: 0 4px 20px rgba(127,29,29,0.3);">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-300 animate-pulse"></span>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-red-200">Perbaikan / Servis</p>
                    </div>
                    <p class="text-4xl font-black tracking-tight leading-none text-white">{{ $totalMaintenance }}</p>
                </div>
                <div class="p-2 rounded-xl bg-white/10">
                    <svg class="w-5 h-5 text-red-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-[10px] mt-3 text-red-300">Tidak dapat disewakan</p>
            <div class="absolute -right-4 -bottom-4 w-20 h-20 rounded-full opacity-10 bg-white"></div>
        </button>
    </div>

    {{-- ═══════════════════════════════════════════════════ --}}
    {{-- Alert Notifikasi Feedback Action                   --}}
    {{-- ═══════════════════════════════════════════════════ --}}
    @if (session()->has('success'))
        <div class="flex items-center gap-3 p-4 rounded-2xl bg-avocado-50 border border-avocado-200 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-avocado-600 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <p class="text-xs font-semibold text-avocado-800">{{ session('success') }}</p>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="flex items-center gap-3 p-4 rounded-2xl bg-red-50 border border-red-200 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-red-600 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <p class="text-xs font-semibold text-red-800">{{ session('error') }}</p>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════ --}}
    {{-- 2. FILTER BAR                                      --}}
    {{-- ═══════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl border border-wheat-200 shadow-tendaku-sm overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 border-b border-wheat-100">
            {{-- Tab pills --}}
            <div class="flex items-center gap-1.5 flex-wrap">
                <button wire:click="setTab('all')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all duration-150 {{ $filterStatus === 'all' ? 'bg-darkbrown-900 text-white shadow-sm' : 'text-darkbrown-600 hover:bg-wheat-100' }}">
                    Semua ({{ $totalAll }})
                </button>
                <button wire:click="setTab('available')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-1.5 {{ $filterStatus === 'available' ? 'bg-avocado-600 text-white shadow-sm' : 'text-avocado-700 hover:bg-avocado-50' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $filterStatus === 'available' ? 'bg-emerald-300 animate-pulse' : 'bg-avocado-400' }}"></span>
                    Tersedia ({{ $totalAvailable }})
                </button>
                <button wire:click="setTab('rented')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-1.5 {{ $filterStatus === 'rented' ? 'bg-amber-600 text-white shadow-sm' : 'text-amber-700 hover:bg-amber-50' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $filterStatus === 'rented' ? 'bg-amber-300 animate-pulse' : 'bg-amber-400' }}"></span>
                    Disewa ({{ $totalRented }})
                </button>
                <button wire:click="setTab('maintenance')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all duration-150 flex items-center gap-1.5 {{ $filterStatus === 'maintenance' ? 'bg-red-600 text-white shadow-sm' : 'text-red-700 hover:bg-red-50' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $filterStatus === 'maintenance' ? 'bg-red-300 animate-pulse' : 'bg-red-400' }}"></span>
                    Perbaikan ({{ $totalMaintenance }})
                </button>
            </div>

            {{-- Search --}}
            <div class="relative w-full sm:w-64">
                <svg class="w-3.5 h-3.5 text-darkbrown-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input wire:model.live.debounce.300ms="search"
                       type="text"
                       placeholder="Cari kode, barcode, nama barang..."
                       class="w-full pl-8 pr-4 py-2 text-xs rounded-xl border border-wheat-300 focus:border-avocado-500 focus:ring-1 focus:ring-avocado-500 bg-wheat-50/50 text-darkbrown-900 placeholder-darkbrown-400 transition">
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════ --}}
        {{-- 3. UNIT LIST (Card-based rows)                     --}}
        {{-- ═══════════════════════════════════════════════════ --}}
        <div class="divide-y divide-wheat-100">
            @forelse($units as $unit)
                <div class="group flex items-center gap-4 px-5 py-4 hover:bg-wheat-50/60 transition-colors duration-150">

                    {{-- Status Indicator strip --}}
                    <div class="shrink-0 w-1 self-stretch rounded-full
                        @if($unit->status === 'available') bg-avocado-400
                        @elseif($unit->status === 'rented') bg-amber-400
                        @elseif($unit->status === 'maintenance') bg-red-400
                        @else bg-gray-300
                        @endif">
                    </div>

                    {{-- Unit Code + Barcode --}}
                    <div class="w-36 shrink-0">
                        <p class="font-black text-sm text-darkbrown-900 leading-none">{{ $unit->unit_code }}</p>
                        @if($unit->barcode)
                            <p class="font-mono text-[10px] text-darkbrown-400 mt-1">{{ $unit->barcode }}</p>
                        @else
                            <p class="text-[10px] text-darkbrown-300 italic mt-1">–</p>
                        @endif
                    </div>

                    {{-- Nama Barang --}}
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-sm text-darkbrown-900 truncate">{{ $unit->masterItem->name ?? '—' }}</p>
                        <p class="text-[11px] text-darkbrown-400 mt-0.5">
                            Rp {{ number_format($unit->masterItem->daily_rate ?? 0, 0, ',', '.') }} <span class="text-darkbrown-300">/hari</span>
                        </p>
                    </div>

                    {{-- Status Badge --}}
                    <div class="shrink-0">
                        @if($unit->status === 'available')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-avocado-500 animate-pulse"></span>
                                Tersedia
                            </span>
                        @elseif($unit->status === 'rented')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                Disewa
                            </span>
                        @elseif($unit->status === 'maintenance')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-red-100 text-red-800 border border-red-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                Perbaikan
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-600 border border-gray-200">
                                {{ ucfirst($unit->status) }}
                            </span>
                        @endif
                    </div>

                    {{-- Catatan — ml-auto mendorongnya ke kanan --}}
                    <div class="hidden md:block ml-auto w-52 shrink-0 text-right">
                        <p class="text-[11px] text-darkbrown-500 truncate">{{ $unit->notes ?: '—' }}</p>
                    </div>

                    {{-- Tombol Aksi (hanya render jika ada aksi) --}}
                    @if($filterStatus === 'maintenance')
                        <div class="shrink-0 flex justify-end">
                            <button wire:click="moveToAvailable({{ $unit->id }})"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-white transition-all duration-150 shadow-sm
                                           bg-avocado-600 hover:bg-avocado-700 active:scale-95">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                Selesai Perbaikan
                            </button>
                        </div>
                    @elseif($filterStatus === 'available')
                        <div class="shrink-0 flex justify-end">
                            <button @click="$dispatch('open-confirm-maintenance', { id: {{ $unit->id }}, code: '{{ $unit->unit_code }}' })"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 shadow-sm
                                           text-amber-800 bg-amber-50 border border-amber-200 hover:bg-amber-100 active:scale-95">
                                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Kirim Perbaikan
                            </button>
                        </div>
                    @endif

                </div>
            @empty
                <div class="py-16 text-center">
                    <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center"
                         style="background: linear-gradient(135deg, #f0e8d4 0%, #e6dcc5 100%);">
                        <svg class="w-8 h-8 text-darkbrown-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <p class="font-bold text-sm text-darkbrown-800">Tidak ada unit ditemukan</p>
                    <p class="text-xs text-darkbrown-400 mt-1">Coba ubah kata kunci pencarian atau pilih tab lain.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($units->hasPages())
            <div class="px-5 py-4 border-t border-wheat-100">
                {{ $units->links() }}
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════ --}}
    {{-- Custom Confirmation Modal — Kirim ke Perbaikan     --}}
    {{-- ═══════════════════════════════════════════════════ --}}
    <div x-show="confirmOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display: none;">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"
             @click="confirmOpen = false"></div>

        {{-- Modal Panel --}}
        <div x-show="confirmOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden"
             style="box-shadow: 0 25px 60px rgba(0,0,0,0.2);">

            {{-- Header strip --}}
            <div class="h-1.5 w-full" style="background: linear-gradient(90deg, #92610a, #bf8018);"></div>

            {{-- Body --}}
            <div class="p-6">
                {{-- Icon --}}
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4"
                     style="background: linear-gradient(135deg, #fef3c7, #fde68a);">
                    <svg class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>

                <h3 class="text-base font-extrabold text-darkbrown-900 mb-1">Kirim ke Perbaikan?</h3>
                <p class="text-sm text-darkbrown-500 mb-1">
                    Unit <span class="font-bold text-darkbrown-800 font-mono" x-text="confirmUnitCode"></span> akan dipindahkan ke status <span class="font-bold text-amber-700">Perbaikan / Servis</span>.
                </p>
                <p class="text-xs text-darkbrown-400">Unit tidak dapat disewakan selama dalam proses perbaikan.</p>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 px-6 pb-6">
                <button @click="confirmOpen = false"
                        class="flex-1 py-2.5 rounded-xl text-sm font-bold text-darkbrown-700 bg-wheat-100 hover:bg-wheat-200 border border-wheat-300 transition-all duration-150 active:scale-95">
                    Batal
                </button>
                <button @click="$wire.moveToMaintenance(confirmUnitId); confirmOpen = false"
                        class="flex-1 py-2.5 rounded-xl text-sm font-bold text-white transition-all duration-150 active:scale-95 shadow-sm"
                        style="background: linear-gradient(135deg, #92610a, #bf8018);">
                    Ya, Kirim Perbaikan
                </button>
            </div>
        </div>
    </div>

</div>
