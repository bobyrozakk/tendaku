{{-- Kasir POS Sidebar Component (Modern POS Terminal Aesthetics with Hover-to-Expand & Mobile Drawer) --}}
<div x-data="{
        open: false,
        mobileOpen: false
    }"
    @toggle-kasir-sidebar.window="mobileOpen = !mobileOpen"
    @keydown.escape.window="mobileOpen = false"
    class="contents">

    <!-- Mobile Overlay Backdrop -->
    <div x-show="mobileOpen"
         x-cloak
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileOpen = false"
         class="fixed inset-0 z-30 bg-darkbrown-950/75 backdrop-blur-sm lg:hidden pointer-events-auto">
    </div>

    <aside
    @mouseenter="open = true"
    @mouseleave="open = false"
    :class="{
        'w-64': open,
        'w-[74px]': !open,
        '-translate-x-full lg:translate-x-0': !mobileOpen,
        'translate-x-0 !w-64': mobileOpen
    }"
    class="fixed inset-y-0 left-0 lg:sticky lg:top-0 h-screen shrink-0 z-40 flex flex-col transition-all duration-200 ease-out shadow-2xl select-none"
    style="background: #141b12; border-right: 1px solid rgba(255,255,255,0.08);">

    <!-- Top Header: POS Terminal Branding -->
    <div class="px-3 py-3.5 flex items-center justify-between border-b shrink-0 overflow-hidden"
         style="border-color: rgba(255,255,255,0.08); height: 72px;">
        <div class="flex items-center gap-3 overflow-hidden">
            <!-- Brand Logo Frame (48x48 centered to match 74px sidebar width) -->
            <a href="{{ url('/') }}"
               class="shrink-0 flex items-center justify-center rounded-xl transition-all duration-150 hover:brightness-110 active:scale-95 shadow-sm"
               style="width: 48px; height: 48px; background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.14);"
               title="Tendaku — Beranda"
               aria-label="Tendaku Home">
                <img src="{{ asset('images/Logo.svg') }}"
                     alt="Tendaku Logo"
                     width="36" height="36"
                     class="w-8 h-8 object-contain block"
                     style="filter: brightness(1.7) contrast(1.1) drop-shadow(0 2px 4px rgba(0,0,0,0.5));">
            </a>

            <!-- Brand Title & POS Status (Smooth fade on expand) -->
            <div :class="(open || mobileOpen) ? 'opacity-100 translate-x-0 pointer-events-auto' : 'opacity-0 -translate-x-2 w-0 overflow-hidden pointer-events-none'"
                 class="min-w-0 transition-all duration-200 ease-out">
                <div class="flex items-center gap-1.5">
                    <span class="text-sm font-black tracking-tight leading-none text-wheat-100 whitespace-nowrap">TENDAKU</span>
                    <span class="text-[9px] font-mono px-1.5 py-0.2 rounded font-extrabold bg-avocado-500/20 text-avocado-300 border border-avocado-500/30 uppercase">
                        POS
                    </span>
                </div>
                <div class="inline-flex items-center gap-1.5 mt-1 whitespace-nowrap">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" aria-hidden="true"></span>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-sunglow-300">
                        Terminal Kasir
                    </span>
                </div>
            </div>
        </div>

        <!-- Mobile Close Button (Hidden on Desktop) -->
        <button type="button"
                @click="mobileOpen = false"
                class="lg:hidden flex items-center justify-center w-8 h-8 rounded-lg text-wheat-300 hover:text-white hover:bg-white/10 transition cursor-pointer">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <!-- Navigation Links Section -->
    <nav class="flex-1 pt-3.5 pb-4 px-2.5 space-y-1.5 overflow-y-auto overflow-x-hidden scrollbar-none [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        <!-- Section Header (Shown only when expanded) -->
        <div :class="(open || mobileOpen) ? 'opacity-50 h-auto pb-1.5 px-2' : 'opacity-0 h-0 pb-0 overflow-hidden'"
             class="text-[10px] font-bold uppercase tracking-wider transition-all duration-150 whitespace-nowrap text-wheat-300">
            Menu Operasional
        </div>

        @php
            $navItems = [
                [
                    'name' => 'Dashboard Validasi',
                    'route' => 'kasir.dashboard',
                    'active' => request()->routeIs('kasir.dashboard'),
                    'icon' => 'layout-dashboard',
                ],
                [
                    'name' => 'Status Unit Alat',
                    'route' => 'kasir.units',
                    'active' => request()->routeIs('kasir.units'),
                    'icon' => 'boxes',
                ],
                [
                    'name' => 'Pickup & Serah Terima',
                    'route' => 'kasir.pickup',
                    'active' => request()->routeIs('kasir.pickup*'),
                    'icon' => 'package-check',
                ],
                [
                    'name' => 'Inspeksi & Retur',
                    'route' => 'kasir.return',
                    'active' => request()->routeIs('kasir.return*'),
                    'icon' => 'clipboard-check',
                ],
            ];
        @endphp

        @foreach ($navItems as $item)
            <div class="relative group/nav">
                <a href="{{ route($item['route']) }}"
                   wire:navigate
                   @click="if (window.innerWidth < 1024) mobileOpen = false"
                   title="{{ $item['name'] }}"
                   class="flex items-center rounded-xl text-xs font-bold transition-all duration-150 relative h-11 overflow-hidden active:scale-[0.97] {{ $item['active']
                       ? 'bg-avocado-700 text-white shadow-sm border border-avocado-500/40'
                       : 'text-wheat-200/80 hover:text-white hover:bg-white/8' }}"
                   :class="(open || mobileOpen) ? 'px-3 justify-start gap-3' : 'px-0 justify-center'">

                    <!-- Icon Container with fixed dimensions (24x24) -->
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <i data-lucide="{{ $item['icon'] }}"
                           class="w-5 h-5 {{ $item['active'] ? 'text-white' : 'text-wheat-300/80 group-hover/nav:text-white' }}"></i>
                    </div>

                    <!-- Menu Label -->
                    <span :class="(open || mobileOpen) ? 'opacity-100 w-auto' : 'opacity-0 w-0 overflow-hidden pointer-events-none'"
                          class="truncate tracking-wide whitespace-nowrap transition-all duration-150 ease-out flex-1">
                        {{ $item['name'] }}
                    </span>

                    <!-- Active Indicator Dot -->
                    @if ($item['active'])
                        <span :class="(open || mobileOpen) ? 'block' : 'hidden'"
                              class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1 shrink-0"></span>
                    @endif
                </a>

                <!-- Hover Tooltip pill when sidebar is collapsed (Desktop Only) -->
                <div x-show="!open && !mobileOpen"
                     class="hidden lg:group-hover/nav:block absolute left-full top-1/2 -translate-y-1/2 ml-3 px-3 py-1.5 rounded-xl bg-darkbrown-950 text-wheat-100 text-xs font-bold whitespace-nowrap shadow-2xl border border-white/15 z-[60] pointer-events-none animate-fade-in">
                    <span>{{ $item['name'] }}</span>
                    <div class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-darkbrown-950"></div>
                </div>
            </div>
        @endforeach
    </nav>

    <!-- Bottom POS User Profile Bar -->
    <div class="p-2.5 border-t overflow-hidden shrink-0" style="border-color: rgba(255,255,255,0.08); background: rgba(0,0,0,0.2);">
        <div class="flex items-center gap-2.5 rounded-xl transition-colors"
             :class="(open || mobileOpen) ? 'p-1.5 justify-start' : 'p-0 justify-center'">
            <!-- Cashier Avatar Indicator -->
            <div class="w-10 h-10 rounded-xl font-black flex items-center justify-center text-xs text-white shrink-0 shadow-xs bg-avocado-800 border border-white/15"
                 title="{{ auth()->user()->name ?? 'Kasir Toko' }}">
                {{ strtoupper(substr(auth()->user()->name ?? 'K', 0, 2)) }}
            </div>

            <!-- Cashier Info detail (Reveals smoothly on expand) -->
            <div :class="(open || mobileOpen) ? 'opacity-100 w-auto' : 'opacity-0 w-0 overflow-hidden pointer-events-none'"
                 class="min-w-0 flex-1 transition-all duration-150 ease-out">
                <p class="text-xs font-bold text-wheat-100 truncate leading-tight whitespace-nowrap">
                    {{ auth()->user()->name ?? 'Kasir Toko' }}
                </p>
                <p class="text-[10px] text-wheat-400/80 truncate mt-0.5 whitespace-nowrap">
                    {{ auth()->user()->vendor?->name ?? 'Tendaku Rental' }}
                </p>
            </div>
        </div>
    </div>
</aside>
</div>
