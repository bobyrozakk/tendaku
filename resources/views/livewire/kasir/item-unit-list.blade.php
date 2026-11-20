<div class="space-y-6"
     x-data="{ confirmOpen: false, confirmUnitId: null, confirmUnitCode: '' }"
     @open-confirm-maintenance.window="confirmOpen = true; confirmUnitId = $event.detail.id; confirmUnitCode = $event.detail.code">

    {{-- Flash Toast Notification --}}
    @if (session()->has('success'))
        <div class="flex items-center justify-between gap-3 p-4 rounded-xl bg-avocado-50 border border-avocado-300 text-darkbrown-900 shadow-sm animate-fade-in" role="alert">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-avocado-600 text-white flex items-center justify-center shrink-0">
                    <i data-lucide="check" class="w-4 h-4"></i>
                </div>
                <div>
                    <p class="text-xs font-bold leading-tight">{{ session('success') }}</p>
                    <p class="text-[11px] text-darkbrown-600 mt-0.5">Inventaris berhasil diperbarui.</p>
                </div>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-darkbrown-400 hover:text-darkbrown-700 p-1">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="flex items-center justify-between gap-3 p-4 rounded-xl bg-rose-50 border border-rose-300 text-rose-900 shadow-sm animate-fade-in" role="alert">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center shrink-0">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                </div>
                <div>
                    <p class="text-xs font-bold leading-tight">{{ session('error') }}</p>
                    <p class="text-[11px] text-rose-700 mt-0.5">Tindakan tidak dapat diproses.</p>
                </div>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-400 hover:text-rose-700 p-1">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════ --}}
    {{-- 1. KPI SUMMARY METRICS (Frosted Glassmorphism POS)  --}}
    {{-- ═══════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Unit Fisik — Obsidian Frosted Glass --}}
        <div class="group relative overflow-hidden rounded-2xl p-5 backdrop-blur-xl backdrop-saturate-150 bg-gradient-to-br from-darkbrown-900/90 via-darkbrown-900/80 to-darkbrown-950/95 border border-white/15 border-t-white/30 shadow-[0_8px_30px_rgb(0,0,0,0.12)] flex flex-col justify-between gap-4 transition-all duration-200 hover:-translate-y-1 hover:shadow-[0_12px_36px_rgb(0,0,0,0.22)]">
            {{-- Glass reflection light sheen & ambient orb --}}
            <div class="absolute inset-0 bg-gradient-to-b from-white/15 via-transparent to-black/10 pointer-events-none"></div>
            <div class="absolute -right-6 -top-6 w-28 h-28 rounded-full bg-white/10 blur-xl pointer-events-none group-hover:scale-125 transition-transform duration-500"></div>
            
            <div class="flex items-start justify-between gap-3 relative z-10">
                <span class="text-[11px] font-bold text-wheat-200/90 uppercase tracking-wider">Total unit fisik</span>
                <div class="w-10 h-10 rounded-xl bg-white/15 backdrop-blur-md text-white flex items-center justify-center shrink-0 border border-white/20 shadow-xs">
                    <i data-lucide="package" class="w-5 h-5"></i>
                </div>
            </div>
            <p class="text-4xl font-black tracking-tight text-white font-admin-number tabular-nums relative z-10 leading-none">{{ $totalAll }}</p>
        </div>

        {{-- Tersedia — Emerald Avocado Frosted Glass --}}
        <button wire:click="setTab('available')"
                class="group relative overflow-hidden rounded-2xl p-5 text-left cursor-pointer backdrop-blur-xl backdrop-saturate-150 bg-gradient-to-br from-avocado-600/90 via-avocado-700/85 to-avocado-800/95 border border-white/25 border-t-white/45 shadow-[0_8px_30px_rgba(46,125,50,0.18)] flex flex-col justify-between gap-4 transition-all duration-200 hover:-translate-y-1 hover:shadow-[0_12px_36px_rgba(46,125,50,0.28)] active:scale-[0.98] {{ $filterStatus === 'available' ? 'ring-2 ring-white/90 ring-offset-2 ring-offset-wheat-100' : '' }}">
            {{-- Glass reflection light sheen & ambient orb --}}
            <div class="absolute inset-0 bg-gradient-to-b from-white/20 via-transparent to-black/10 pointer-events-none"></div>
            <div class="absolute -right-6 -top-6 w-28 h-28 rounded-full bg-white/15 blur-xl pointer-events-none group-hover:scale-125 transition-transform duration-500"></div>

            <div class="flex items-start justify-between gap-3 relative z-10">
                <span class="text-[11px] font-bold text-white/95 uppercase tracking-wider">Tersedia</span>
                <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md text-white flex items-center justify-center shrink-0 border border-white/30 shadow-xs">
                    <i data-lucide="circle-check" class="w-5 h-5"></i>
                </div>
            </div>
            <p class="text-4xl font-black tracking-tight text-white font-admin-number tabular-nums relative z-10 leading-none">{{ $totalAvailable }}</p>
        </button>

        {{-- Sedang Disewa — Amber Sun Frosted Glass --}}
        <button wire:click="setTab('rented')"
                class="group relative overflow-hidden rounded-2xl p-5 text-left cursor-pointer backdrop-blur-xl backdrop-saturate-150 bg-gradient-to-br from-amber-500/90 via-amber-600/85 to-amber-700/95 border border-white/30 border-t-white/50 shadow-[0_8px_30px_rgba(217,119,6,0.18)] flex flex-col justify-between gap-4 transition-all duration-200 hover:-translate-y-1 hover:shadow-[0_12px_36px_rgba(217,119,6,0.28)] active:scale-[0.98] {{ $filterStatus === 'rented' ? 'ring-2 ring-white/90 ring-offset-2 ring-offset-wheat-100' : '' }}">
            {{-- Glass reflection light sheen & ambient orb --}}
            <div class="absolute inset-0 bg-gradient-to-b from-white/25 via-transparent to-black/10 pointer-events-none"></div>
            <div class="absolute -right-6 -top-6 w-28 h-28 rounded-full bg-white/20 blur-xl pointer-events-none group-hover:scale-125 transition-transform duration-500"></div>

            <div class="flex items-start justify-between gap-3 relative z-10">
                <span class="text-[11px] font-bold text-white/95 uppercase tracking-wider">Disewa</span>
                <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md text-white flex items-center justify-center shrink-0 border border-white/30 shadow-xs">
                    <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
            </div>
            <p class="text-4xl font-black tracking-tight text-white font-admin-number tabular-nums relative z-10 leading-none">{{ $totalRented }}</p>
        </button>

        {{-- Perbaikan / Servis — Crimson Rose Frosted Glass --}}
        <button wire:click="setTab('maintenance')"
                class="group relative overflow-hidden rounded-2xl p-5 text-left cursor-pointer backdrop-blur-xl backdrop-saturate-150 bg-gradient-to-br from-rose-600/90 via-rose-700/85 to-rose-800/95 border border-white/25 border-t-white/45 shadow-[0_8px_30px_rgba(225,29,72,0.18)] flex flex-col justify-between gap-4 transition-all duration-200 hover:-translate-y-1 hover:shadow-[0_12px_36px_rgba(225,29,72,0.28)] active:scale-[0.98] {{ $filterStatus === 'maintenance' ? 'ring-2 ring-white/90 ring-offset-2 ring-offset-wheat-100' : '' }}">
            {{-- Glass reflection light sheen & ambient orb --}}
            <div class="absolute inset-0 bg-gradient-to-b from-white/20 via-transparent to-black/10 pointer-events-none"></div>
            <div class="absolute -right-6 -top-6 w-28 h-28 rounded-full bg-white/15 blur-xl pointer-events-none group-hover:scale-125 transition-transform duration-500"></div>

            <div class="flex items-start justify-between gap-3 relative z-10">
                <span class="text-[11px] font-bold text-white/95 uppercase tracking-wider">Perbaikan</span>
                <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md text-white flex items-center justify-center shrink-0 border border-white/30 shadow-xs">
                    <i data-lucide="wrench" class="w-5 h-5"></i>
                </div>
            </div>
            <p class="text-4xl font-black tracking-tight text-white font-admin-number tabular-nums relative z-10 leading-none">{{ $totalMaintenance }}</p>
        </button>
    </div>

    {{-- ═══════════════════════════════════════════════════ --}}
    {{-- 2. WORKSPACE TABLE CARD                            --}}
    {{-- ═══════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-2xl border border-wheat-200 shadow-sm overflow-hidden">
        {{-- Filter & Control Bar --}}
        <div class="p-4 sm:p-5 border-b border-wheat-200/80 bg-wheat-50/40 flex flex-col xl:flex-row xl:items-center justify-between gap-4">
            {{-- Tabs segmented filter (Modern Shadcn Segmented Control with No Ugly Browser Scrollbar) --}}
            <div class="inline-flex items-center p-1 bg-wheat-200/60 rounded-xl border border-wheat-300/70 shrink-0 overflow-x-auto scrollbar-none [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                 role="tablist"
                 aria-label="Filter Status Unit">
                
                {{-- Tab: Semua --}}
                <button type="button"
                        wire:click="setTab('all')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-2 cursor-pointer whitespace-nowrap active:scale-[0.97] {{ $filterStatus === 'all' ? 'text-darkbrown-950 font-bold bg-white shadow-xs border border-wheat-200/90' : 'text-darkbrown-600 hover:text-darkbrown-900 hover:bg-white/40' }}"
                        role="tab"
                        aria-selected="{{ $filterStatus === 'all' ? 'true' : 'false' }}">
                    <span>Semua</span>
                    <span class="px-1.5 py-0.5 text-[10px] font-mono rounded-md tabular-nums transition-colors {{ $filterStatus === 'all' ? 'bg-wheat-100 text-darkbrown-800 border border-wheat-200' : 'bg-wheat-300/50 text-darkbrown-600' }}">
                        {{ $totalAll }}
                    </span>
                </button>

                {{-- Tab: Tersedia --}}
                <button type="button"
                        wire:click="setTab('available')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-2 cursor-pointer whitespace-nowrap active:scale-[0.97] {{ $filterStatus === 'available' ? 'text-avocado-950 font-bold bg-white shadow-xs border border-wheat-200/90' : 'text-darkbrown-600 hover:text-darkbrown-900 hover:bg-white/40' }}"
                        role="tab"
                        aria-selected="{{ $filterStatus === 'available' ? 'true' : 'false' }}">
                    <span class="w-2 h-2 rounded-full {{ $filterStatus === 'available' ? 'bg-avocado-600 ring-2 ring-avocado-200' : 'bg-avocado-500' }} transition-all"></span>
                    <span>Tersedia</span>
                    <span class="px-1.5 py-0.5 text-[10px] font-mono rounded-md tabular-nums transition-colors {{ $filterStatus === 'available' ? 'bg-avocado-50 text-avocado-900 border border-avocado-200' : 'bg-wheat-300/50 text-darkbrown-600' }}">
                        {{ $totalAvailable }}
                    </span>
                </button>

                {{-- Tab: Disewa --}}
                <button type="button"
                        wire:click="setTab('rented')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-2 cursor-pointer whitespace-nowrap active:scale-[0.97] {{ $filterStatus === 'rented' ? 'text-sunglow-950 font-bold bg-white shadow-xs border border-wheat-200/90' : 'text-darkbrown-600 hover:text-darkbrown-900 hover:bg-white/40' }}"
                        role="tab"
                        aria-selected="{{ $filterStatus === 'rented' ? 'true' : 'false' }}">
                    <span class="w-2 h-2 rounded-full {{ $filterStatus === 'rented' ? 'bg-sunglow-500 ring-2 ring-sunglow-200' : 'bg-sunglow-400' }} transition-all"></span>
                    <span>Disewa</span>
                    <span class="px-1.5 py-0.5 text-[10px] font-mono rounded-md tabular-nums transition-colors {{ $filterStatus === 'rented' ? 'bg-sunglow-50 text-sunglow-900 border border-sunglow-200' : 'bg-wheat-300/50 text-darkbrown-600' }}">
                        {{ $totalRented }}
                    </span>
                </button>

                {{-- Tab: Perbaikan --}}
                <button type="button"
                        wire:click="setTab('maintenance')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-2 cursor-pointer whitespace-nowrap active:scale-[0.97] {{ $filterStatus === 'maintenance' ? 'text-rose-950 font-bold bg-white shadow-xs border border-wheat-200/90' : 'text-darkbrown-600 hover:text-darkbrown-900 hover:bg-white/40' }}"
                        role="tab"
                        aria-selected="{{ $filterStatus === 'maintenance' ? 'true' : 'false' }}">
                    <span class="w-2 h-2 rounded-full {{ $filterStatus === 'maintenance' ? 'bg-rose-600 ring-2 ring-rose-200' : 'bg-rose-400' }} transition-all"></span>
                    <span>Perbaikan</span>
                    <span class="px-1.5 py-0.5 text-[10px] font-mono rounded-md tabular-nums transition-colors {{ $filterStatus === 'maintenance' ? 'bg-rose-50 text-rose-900 border border-rose-200' : 'bg-wheat-300/50 text-darkbrown-600' }}">
                        {{ $totalMaintenance }}
                    </span>
                </button>
            </div>

            {{-- Search & Category Filter --}}
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 w-full xl:w-auto">
                {{-- Dropdown Kategori --}}
                <div class="relative shrink-0 w-44">
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none text-darkbrown-400">
                        <i data-lucide="tag" class="w-3.5 h-3.5"></i>
                    </div>
                    <select wire:model.live="filterCategory"
                            id="filter-category"
                            class="w-full appearance-none pl-8 pr-7 py-2 text-xs rounded-xl border border-wheat-300 bg-white text-darkbrown-800 focus:outline-none focus:ring-2 focus:ring-avocado-500/30 focus:border-avocado-600 cursor-pointer transition">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <div class="absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-darkbrown-400">
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                    </div>
                </div>

                {{-- Search Bar --}}
                <div class="relative w-full sm:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-darkbrown-400">
                        <i data-lucide="search" wire:loading.remove wire:target="search" class="w-4 h-4"></i>
                        <svg wire:loading wire:target="search" class="w-4 h-4 animate-spin text-avocado-700" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                    </div>
                    <input wire:model.live.debounce.250ms="search"
                           type="text"
                           placeholder="Cari kode unit, barcode, nama..."
                           class="w-full text-xs pl-10 pr-8 py-2 bg-white border border-wheat-300 rounded-xl text-darkbrown-900 placeholder-darkbrown-400 focus:outline-none focus:ring-2 focus:ring-avocado-500/30 focus:border-avocado-600 transition">

                    @if(!empty($search))
                        <button type="button"
                                wire:click="$set('search', '')"
                                title="Hapus pencarian"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-darkbrown-400 hover:text-darkbrown-700 p-0.5">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Table Content --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-wheat-100 text-darkbrown-800 font-bold uppercase tracking-wider border-b border-wheat-200">
                    <tr>
                        <th class="py-3 px-4 w-44">Kode & Barcode</th>
                        <th class="py-3 px-4">Nama Item Rental</th>
                        <th class="py-3 px-4 w-40">Kategori</th>
                        <th class="py-3 px-4 w-36">Status Unit</th>
                        <th class="py-3 px-4 text-right w-36">Aksi Kasir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-wheat-200/70 bg-white"
                       wire:loading.class="opacity-60 pointer-events-none"
                       wire:target="search, filterStatus, filterCategory, setTab">
                    @forelse($units as $unit)
                        <tr class="hover:bg-wheat-50/70 transition-colors">
                            {{-- Unit Code & Barcode --}}
                            <td class="py-3.5 px-4 font-mono">
                                <span class="font-extrabold text-sm text-darkbrown-900 block leading-tight tracking-tight">
                                    {{ $unit->unit_code }}
                                </span>
                                @if($unit->barcode)
                                    <span class="text-[11px] text-darkbrown-500 font-medium tracking-tight block mt-0.5">
                                        {{ $unit->barcode }}
                                    </span>
                                @else
                                    <span class="text-[11px] text-darkbrown-400 italic block mt-0.5">–</span>
                                @endif
                            </td>

                            {{-- Nama Item Rental --}}
                            <td class="py-3.5 px-4">
                                <p class="font-bold text-sm text-darkbrown-900 leading-snug">
                                    {{ $unit->masterItem->name ?? '—' }}
                                </p>
                                <p class="text-xs text-darkbrown-600 mt-0.5 font-admin-number font-bold tabular-nums">
                                    Rp {{ number_format($unit->masterItem->daily_rate ?? 0, 0, ',', '.') }} <span class="font-sans font-normal text-[11px] text-darkbrown-400">/ hari</span>
                                </p>
                            </td>

                            {{-- Kategori --}}
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-wheat-100 text-darkbrown-700 border border-wheat-200">
                                    <i data-lucide="tag" class="w-3 h-3 text-darkbrown-400"></i>
                                    <span>{{ $unit->masterItem->category->name ?? 'Tanpa Kategori' }}</span>
                                </span>
                            </td>

                            {{-- Status Unit --}}
                            <td class="py-3.5 px-4">
                                @if($unit->status === 'available')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-avocado-50 text-avocado-800 border border-avocado-200 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-avocado-600"></span>
                                        Tersedia
                                    </span>
                                @elseif($unit->status === 'rented')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Disewa
                                    </span>
                                @elseif($unit->status === 'maintenance')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-50 text-rose-800 border border-rose-200 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Perbaikan
                                    </span>
                                @elseif($unit->status === 'lost')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-900 border border-rose-300 shadow-2xs">
                                        Hilang
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-wheat-100 text-darkbrown-700">
                                        {{ ucfirst($unit->status) }}
                                    </span>
                                @endif
                            </td>

                            {{-- Aksi Kasir --}}
                            <td class="py-3.5 px-4 text-right">
                                @if($unit->status === 'maintenance')
                                    <button wire:click="moveToAvailable({{ $unit->id }})"
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-avocado-700 hover:bg-avocado-800 active:scale-[0.97] transition-all duration-150 cursor-pointer shadow-sm">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                        <span>Siap Sewa</span>
                                    </button>
                                @elseif($unit->status === 'available')
                                    <button @click="$dispatch('open-confirm-maintenance', { id: {{ $unit->id }}, code: '{{ $unit->unit_code }}' })"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-darkbrown-700 bg-wheat-100 hover:bg-wheat-200 border border-wheat-300 active:scale-[0.97] transition-all duration-150 cursor-pointer">
                                        <i data-lucide="wrench" class="w-3.5 h-3.5 text-darkbrown-500"></i>
                                        <span>Servis</span>
                                    </button>
                                @else
                                    <span class="text-darkbrown-400 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-16 text-center text-darkbrown-500">
                                <div class="w-12 h-12 rounded-2xl mx-auto mb-3 bg-wheat-100 border border-wheat-200 flex items-center justify-center text-darkbrown-400">
                                    <i data-lucide="package" class="w-6 h-6"></i>
                                </div>
                                <p class="font-extrabold text-sm text-darkbrown-800">Tidak ada unit ditemukan</p>
                                <p class="text-xs text-darkbrown-500 mt-1">Coba sesuaikan kata kunci pencarian atau ganti filter status di atas.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination & Summary Footer --}}
        <div class="px-5 py-4 border-t border-wheat-200 bg-wheat-50/50 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs text-darkbrown-600 font-medium">
                @if($units->total() > 0)
                    Menampilkan <span class="font-mono font-bold text-darkbrown-900">{{ $units->firstItem() }}</span> – <span class="font-mono font-bold text-darkbrown-900">{{ $units->lastItem() }}</span> dari <span class="font-mono font-bold text-darkbrown-900">{{ $units->total() }}</span> unit fisik
                @else
                    Menampilkan <span class="font-mono font-bold text-darkbrown-900">0</span> unit fisik
                @endif
            </div>

            @if($units->hasPages())
                <div class="w-full sm:w-auto flex justify-center sm:justify-end">
                    {{ $units->links('kasir.pagination') }}
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════ --}}
    {{-- MODAL KONFIRMASI PINDAH STATUS SERVIS               --}}
    {{-- ═══════════════════════════════════════════════════ --}}
    <template x-teleport="body">
        <div x-show="confirmOpen"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[100] flex items-center justify-center bg-darkbrown-950/60 p-4"
             x-on:click.self="confirmOpen = false"
             x-on:keydown.escape.window="confirmOpen = false"
             style="display: none;">

            <div x-show="confirmOpen"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="w-full max-w-sm bg-white rounded-2xl border border-wheat-200 shadow-xl overflow-hidden relative">

                <div class="px-5 py-4 border-b border-wheat-200 bg-wheat-50/50 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-800 border border-amber-200 flex items-center justify-center">
                            <i data-lucide="wrench" class="w-4 h-4"></i>
                        </div>
                        <h3 class="text-sm font-extrabold text-darkbrown-900 leading-tight">Kirim ke Perbaikan</h3>
                    </div>
                    <button type="button" @click="confirmOpen = false" class="text-darkbrown-400 hover:text-darkbrown-700 p-1">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <div class="p-5 space-y-3 text-xs">
                    <p class="text-darkbrown-700 leading-relaxed">
                        Unit dengan kode <strong class="font-mono text-darkbrown-900 bg-wheat-100 px-1.5 py-0.5 rounded border border-wheat-200" x-text="confirmUnitCode"></strong> akan dialihkan ke status <strong class="text-amber-800">Perbaikan / Servis</strong>.
                    </p>
                    <div class="p-3 bg-wheat-50 rounded-xl border border-wheat-200 text-darkbrown-600">
                        <p class="text-[11px]">Selama masa perbaikan, unit tidak akan muncul di katalog sewa kasir maupun pelanggan.</p>
                    </div>
                </div>

                <div class="px-5 py-3 border-t border-wheat-200 bg-wheat-50/50 flex items-center justify-end gap-2">
                    <button type="button"
                            @click="confirmOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-darkbrown-700 hover:bg-wheat-200/60 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="button"
                            @click="$wire.moveToMaintenance(confirmUnitId); confirmOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-amber-700 hover:bg-amber-800 active:scale-95 transition cursor-pointer shadow-sm">
                        Ya, Kirim Servis
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
