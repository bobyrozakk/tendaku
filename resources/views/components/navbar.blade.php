@props(['role' => 'customer'])

@if ($role === 'vendor')
    <!-- Vendor Sidebar — Collapse to Icons, Expand on Hover -->
    <aside x-data="{ open: false }"
           @mouseenter="open = true"
           @mouseleave="open = false"
           :class="open ? 'w-64' : 'w-[72px]'"
           class="sticky top-0 h-screen shrink-0 z-40 flex flex-col transition-all duration-300 ease-in-out shadow-xl"
           style="background: linear-gradient(180deg, #1e1a10 0%, #2b2202 60%, #1a1800 100%);">

        <!-- Brand Header -->
        <div class="px-3 py-3 flex items-center gap-3 border-b overflow-hidden" style="border-color: rgba(255,255,255,0.08);">
            {{-- Logo container — ukuran tetap 48x48 agar presisi di tengah saat collapsed (72px) --}}
            <a href="{{ url('/') }}"
               class="shrink-0 flex items-center justify-center rounded-xl transition-all duration-200 hover:opacity-90 shadow-sm"
               style="width: 48px; height: 48px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);"
               title="Tendaku — Kembali ke Beranda"
               aria-label="Tendaku Home">
                <img src="{{ asset('images/Logo.svg') }}"
                     alt="Tendaku Logo"
                     width="40" height="40"
                     class="w-10 h-10 object-contain block"
                     style="filter: brightness(1.7) contrast(1.1) drop-shadow(0 2px 4px rgba(0,0,0,0.5));">
            </a>
            {{-- Teks brand — tampil dengan transisi saat sidebar expanded --}}
            <div class="flex-1 min-w-0 transition-all duration-200" :class="open ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-2 w-0 overflow-hidden pointer-events-none'">
                <h1 class="text-sm font-extrabold tracking-tight leading-none whitespace-nowrap" style="color: #FAF6ED;">TENDAKU</h1>
                <span class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider mt-1 whitespace-nowrap" style="color: #D5A007;">
                    <span class="w-1.5 h-1.5 rounded-full bg-avocado-400 animate-pulse" aria-hidden="true"></span>
                    {{ auth()->check() && auth()->user()->isVendorOwner() ? 'Portal Owner' : 'Portal Kasir' }}
                </span>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 pt-4 px-2.5 space-y-1 overflow-hidden">
            <p class="px-2 text-[10px] font-bold uppercase tracking-widest mb-3 transition-all duration-200 whitespace-nowrap"
               :class="open ? 'opacity-60' : 'opacity-0 h-0 mb-0 overflow-hidden'" style="color: rgba(245,233,200,0.5);">Menu Utama</p>

            @if (auth()->check() && auth()->user()->isVendorOwner())
                <!-- Dashboard Owner -->
                <a href="{{ route('vendor.owner.dashboard') }}"
                   wire:navigate
                   class="group relative flex items-center gap-3 rounded-xl text-sm font-semibold transition-all duration-150 overflow-hidden"
                   :class="open ? 'px-3.5 py-2.5' : 'px-0 py-2.5 justify-center'"
                   style="{{ request()->routeIs('vendor.owner.dashboard')
                       ? 'background: linear-gradient(135deg, #6c8b08 0%, #4e6506 100%); box-shadow: 0 4px 14px rgba(108,139,8,0.35); color: white;'
                       : 'color: rgba(245,233,200,0.65);' }}"
                   onmouseover="if (!{{ request()->routeIs('vendor.owner.dashboard') ? 'true' : 'false' }}) { this.style.background='rgba(255,255,255,0.08)'; this.style.color='#ffffff'; }"
                   onmouseout="if (!{{ request()->routeIs('vendor.owner.dashboard') ? 'true' : 'false' }}) { this.style.background='transparent'; this.style.color='rgba(245,233,200,0.65)'; }">
                    <i data-lucide="house" class="w-5 h-5 shrink-0"></i>
                    <span class="whitespace-nowrap transition-all duration-200" :class="open ? 'opacity-100 w-auto' : 'opacity-0 w-0 overflow-hidden'">Dashboard Owner</span>
                </a>

                <!-- Pendapatan & Pencairan Owner -->
                <a href="{{ route('vendor.pendapatan') }}"
                   wire:navigate
                   class="group relative flex items-center gap-3 rounded-xl text-sm font-medium transition-all duration-150 overflow-hidden"
                   :class="open ? 'px-3.5 py-2.5' : 'px-0 py-2.5 justify-center'"
                   style="{{ request()->routeIs('vendor.pendapatan*')
                       ? 'background: linear-gradient(135deg, #6c8b08 0%, #4e6506 100%); box-shadow: 0 4px 14px rgba(108,139,8,0.35); color: white;'
                       : 'color: rgba(245,233,200,0.65);' }}"
                   onmouseover="if (!{{ request()->routeIs('vendor.pendapatan*') ? 'true' : 'false' }}) { this.style.background='rgba(255,255,255,0.08)'; this.style.color='#ffffff'; }"
                   onmouseout="if (!{{ request()->routeIs('vendor.pendapatan*') ? 'true' : 'false' }}) { this.style.background='transparent'; this.style.color='rgba(245,233,200,0.65);' }">
                    <i data-lucide="wallet" class="w-5 h-5 shrink-0"></i>
                    <span class="whitespace-nowrap transition-all duration-200" :class="open ? 'opacity-100 w-auto' : 'opacity-0 w-0 overflow-hidden'">Pendapatan</span>
                </a>

                <!-- Produk Owner -->
                <a href="{{ route('vendor.products.index') }}"
                   wire:navigate
                   class="group relative flex items-center gap-3 rounded-xl text-sm font-medium transition-all duration-150 overflow-hidden"
                   :class="open ? 'px-3.5 py-2.5' : 'px-0 py-2.5 justify-center'"
                   style="{{ request()->routeIs('vendor.products.*')
                       ? 'background: linear-gradient(135deg, #6c8b08 0%, #4e6506 100%); box-shadow: 0 4px 14px rgba(108,139,8,0.35); color: white;'
                       : 'color: rgba(245,233,200,0.65);' }}"
                   onmouseover="if (!{{ request()->routeIs('vendor.products.*') ? 'true' : 'false' }}) { this.style.background='rgba(255,255,255,0.08)'; this.style.color='#ffffff'; }"
                   onmouseout="if (!{{ request()->routeIs('vendor.products.*') ? 'true' : 'false' }}) { this.style.background='transparent'; this.style.color='rgba(245,233,200,0.65)'; }">
                    <i data-lucide="package" class="w-5 h-5 shrink-0"></i>
                    <span class="whitespace-nowrap transition-all duration-200" :class="open ? 'opacity-100 w-auto' : 'opacity-0 w-0 overflow-hidden'">Produk Owner</span>
                </a>

                <!-- Paket Camping Owner -->
                <a href="{{ route('vendor.packages.index') }}"
                   wire:navigate
                   class="group relative flex items-center gap-3 rounded-xl text-sm font-medium transition-all duration-150 overflow-hidden"
                   :class="open ? 'px-3.5 py-2.5' : 'px-0 py-2.5 justify-center'"
                   style="{{ request()->routeIs('vendor.packages.*')
                       ? 'background: linear-gradient(135deg, #6c8b08 0%, #4e6506 100%); box-shadow: 0 4px 14px rgba(108,139,8,0.35); color: white;'
                       : 'color: rgba(245,233,200,0.65);' }}"
                   onmouseover="if (!{{ request()->routeIs('vendor.packages.*') ? 'true' : 'false' }}) { this.style.background='rgba(255,255,255,0.08)'; this.style.color='#ffffff'; }"
                   onmouseout="if (!{{ request()->routeIs('vendor.packages.*') ? 'true' : 'false' }}) { this.style.background='transparent'; this.style.color='rgba(245,233,200,0.65)'; }">
                    <i data-lucide="tent-tree" class="w-5 h-5 shrink-0"></i>
                    <span class="whitespace-nowrap transition-all duration-200" :class="open ? 'opacity-100 w-auto' : 'opacity-0 w-0 overflow-hidden'">Paket Camping</span>
                </a>

                <!-- Kategori Owner -->
                <a href="{{ route('vendor.categories.index') }}"
                   wire:navigate
                   class="group relative flex items-center gap-3 rounded-xl text-sm font-medium transition-all duration-150 overflow-hidden"
                   :class="open ? 'px-3.5 py-2.5' : 'px-0 py-2.5 justify-center'"
                   style="{{ request()->routeIs('vendor.categories.*')
                       ? 'background: linear-gradient(135deg, #6c8b08 0%, #4e6506 100%); box-shadow: 0 4px 14px rgba(108,139,8,0.35); color: white;'
                       : 'color: rgba(245,233,200,0.65);' }}"
                   onmouseover="if (!{{ request()->routeIs('vendor.categories.*') ? 'true' : 'false' }}) { this.style.background='rgba(255,255,255,0.08)'; this.style.color='#ffffff'; }"
                   onmouseout="if (!{{ request()->routeIs('vendor.categories.*') ? 'true' : 'false' }}) { this.style.background='transparent'; this.style.color='rgba(245,233,200,0.65)'; }">
                    <i data-lucide="layers" class="w-5 h-5 shrink-0"></i>
                    <span class="whitespace-nowrap transition-all duration-200" :class="open ? 'opacity-100 w-auto' : 'opacity-0 w-0 overflow-hidden'">Kategori Owner</span>
                </a>

                <!-- Unit Owner -->
                <a href="{{ route('vendor.units') }}"
                   wire:navigate
                   class="group relative flex items-center gap-3 rounded-xl text-sm font-medium transition-all duration-150 overflow-hidden"
                   :class="open ? 'px-3.5 py-2.5' : 'px-0 py-2.5 justify-center'"
                   style="{{ request()->routeIs('vendor.units')
                       ? 'background: linear-gradient(135deg, #6c8b08 0%, #4e6506 100%); box-shadow: 0 4px 14px rgba(108,139,8,0.35); color: white;'
                       : 'color: rgba(245,233,200,0.65);' }}"
                   onmouseover="if (!{{ request()->routeIs('vendor.units') ? 'true' : 'false' }}) { this.style.background='rgba(255,255,255,0.08)'; this.style.color='#ffffff'; }"
                   onmouseout="if (!{{ request()->routeIs('vendor.units') ? 'true' : 'false' }}) { this.style.background='transparent'; this.style.color='rgba(245,233,200,0.65)'; }">
                    <i data-lucide="tag" class="w-5 h-5 shrink-0"></i>
                    <span class="whitespace-nowrap transition-all duration-200" :class="open ? 'opacity-100 w-auto' : 'opacity-0 w-0 overflow-hidden'">Unit Owner</span>
                </a>

                <!-- Manajemen Kasir -->
                <a href="{{ route('vendor.cashiers.index') }}"
                   wire:navigate
                   class="group relative flex items-center gap-3 rounded-xl text-sm font-medium transition-all duration-150 overflow-hidden"
                   :class="open ? 'px-3.5 py-2.5' : 'px-0 py-2.5 justify-center'"
                   style="{{ request()->routeIs('vendor.cashiers.*')
                       ? 'background: linear-gradient(135deg, #6c8b08 0%, #4e6506 100%); box-shadow: 0 4px 14px rgba(108,139,8,0.35); color: white;'
                       : 'color: rgba(245,233,200,0.65);' }}"
                   onmouseover="if (!{{ request()->routeIs('vendor.cashiers.*') ? 'true' : 'false' }}) { this.style.background='rgba(255,255,255,0.08)'; this.style.color='#ffffff'; }"
                   onmouseout="if (!{{ request()->routeIs('vendor.cashiers.*') ? 'true' : 'false' }}) { this.style.background='transparent'; this.style.color='rgba(245,233,200,0.65)'; }">
                    <i data-lucide="users" class="w-5 h-5 shrink-0"></i>
                    <span class="whitespace-nowrap transition-all duration-200" :class="open ? 'opacity-100 w-auto' : 'opacity-0 w-0 overflow-hidden'">Manajemen Kasir</span>
                </a>

                <!-- Pengaturan Pengiriman -->
                <a href="{{ route('vendor.shipping.index') }}"
                   wire:navigate
                   class="group relative flex items-center gap-3 rounded-xl text-sm font-medium transition-all duration-150 overflow-hidden"
                   :class="open ? 'px-3.5 py-2.5' : 'px-0 py-2.5 justify-center'"
                   style="{{ request()->routeIs('vendor.shipping.*')
                       ? 'background: linear-gradient(135deg, #6c8b08 0%, #4e6506 100%); box-shadow: 0 4px 14px rgba(108,139,8,0.35); color: white;'
                       : 'color: rgba(245,233,200,0.65);' }}"
                   onmouseover="if (!{{ request()->routeIs('vendor.shipping.*') ? 'true' : 'false' }}) { this.style.background='rgba(255,255,255,0.08)'; this.style.color='#ffffff'; }"
                   onmouseout="if (!{{ request()->routeIs('vendor.shipping.*') ? 'true' : 'false' }}) { this.style.background='transparent'; this.style.color='rgba(245,233,200,0.65)'; }">
                    <i data-lucide="truck" class="w-5 h-5 shrink-0"></i>
                    <span class="whitespace-nowrap transition-all duration-200" :class="open ? 'opacity-100 w-auto' : 'opacity-0 w-0 overflow-hidden'">Pengaturan Pengiriman</span>
                </a>
            @else
                <!-- Dashboard Kasir -->
                <a href="{{ route('kasir.dashboard') }}"
                   wire:navigate
                   class="group relative flex items-center gap-3 rounded-xl text-sm font-semibold transition-all duration-150 overflow-hidden"
                   :class="open ? 'px-3.5 py-2.5' : 'px-0 py-2.5 justify-center'"
                   style="{{ request()->routeIs('kasir.dashboard')
                       ? 'background: linear-gradient(135deg, #6c8b08 0%, #4e6506 100%); box-shadow: 0 4px 14px rgba(108,139,8,0.35); color: white;'
                       : 'color: rgba(245,233,200,0.65);' }}"
                   onmouseover="if (!{{ request()->routeIs('kasir.dashboard') ? 'true' : 'false' }}) { this.style.background='rgba(255,255,255,0.08)'; this.style.color='#ffffff'; }"
                   onmouseout="if (!{{ request()->routeIs('kasir.dashboard') ? 'true' : 'false' }}) { this.style.background='transparent'; this.style.color='rgba(245,233,200,0.65)'; }">
                    <i data-lucide="house" class="w-5 h-5 shrink-0"></i>
                    <span class="whitespace-nowrap transition-all duration-200" :class="open ? 'opacity-100 w-auto' : 'opacity-0 w-0 overflow-hidden'">Dashboard Kasir</span>
                </a>

                <!-- Status Unit Alat (Kasir View) -->
                <a href="{{ route('kasir.units') }}"
                   wire:navigate
                   class="group relative flex items-center gap-3 rounded-xl text-sm font-medium transition-all duration-150 overflow-hidden"
                   :class="open ? 'px-3.5 py-2.5' : 'px-0 py-2.5 justify-center'"
                   style="{{ request()->routeIs('kasir.units')
                       ? 'background: linear-gradient(135deg, #6c8b08 0%, #4e6506 100%); box-shadow: 0 4px 14px rgba(108,139,8,0.35); color: white;'
                       : 'color: rgba(245,233,200,0.65);' }}"
                   onmouseover="if (!{{ request()->routeIs('kasir.units') ? 'true' : 'false' }}) { this.style.background='rgba(255,255,255,0.08)'; this.style.color='#ffffff'; }"
                   onmouseout="if (!{{ request()->routeIs('kasir.units') ? 'true' : 'false' }}) { this.style.background='transparent'; this.style.color='rgba(245,233,200,0.65)'; }">
                    <i data-lucide="boxes" class="w-5 h-5 shrink-0"></i>
                    <span class="whitespace-nowrap transition-all duration-200" :class="open ? 'opacity-100 w-auto' : 'opacity-0 w-0 overflow-hidden'">Status Unit Alat</span>
                </a>

                <!-- Pickup / Serah Terima -->
                <a href="{{ route('kasir.pickup') }}"
                   wire:navigate
                   class="group relative flex items-center gap-3 rounded-xl text-sm font-medium transition-all duration-150 overflow-hidden"
                   :class="open ? 'px-3.5 py-2.5' : 'px-0 py-2.5 justify-center'"
                   style="{{ request()->routeIs('kasir.pickup')
                       ? 'background: linear-gradient(135deg, #6c8b08 0%, #4e6506 100%); box-shadow: 0 4px 14px rgba(108,139,8,0.35); color: white;'
                       : 'color: rgba(245,233,200,0.65);' }}"
                   onmouseover="if (!{{ request()->routeIs('kasir.pickup') ? 'true' : 'false' }}) { this.style.background='rgba(255,255,255,0.08)'; this.style.color='#ffffff'; }"
                   onmouseout="if (!{{ request()->routeIs('kasir.pickup') ? 'true' : 'false' }}) { this.style.background='transparent'; this.style.color='rgba(245,233,200,0.65)'; }">
                    <i data-lucide="arrow-left-right" class="w-5 h-5 shrink-0"></i>
                    <span class="whitespace-nowrap transition-all duration-200" :class="open ? 'opacity-100 w-auto' : 'opacity-0 w-0 overflow-hidden'">Pickup / Serah Terima</span>
                </a>

                <!-- Inspeksi & Retur -->
                <a href="{{ route('kasir.return') }}"
                   wire:navigate
                   class="group relative flex items-center gap-3 rounded-xl text-sm font-medium transition-all duration-150 overflow-hidden"
                   :class="open ? 'px-3.5 py-2.5' : 'px-0 py-2.5 justify-center'"
                   style="{{ request()->routeIs('kasir.return')
                       ? 'background: linear-gradient(135deg, #6c8b08 0%, #4e6506 100%); box-shadow: 0 4px 14px rgba(108,139,8,0.35); color: white;'
                       : 'color: rgba(245,233,200,0.65);' }}"
                   onmouseover="if (!{{ request()->routeIs('kasir.return') ? 'true' : 'false' }}) { this.style.background='rgba(255,255,255,0.08)'; this.style.color='#ffffff'; }"
                   onmouseout="if (!{{ request()->routeIs('kasir.return') ? 'true' : 'false' }}) { this.style.background='transparent'; this.style.color='rgba(245,233,200,0.65)'; }">
                    <i data-lucide="clipboard-check" class="w-5 h-5 shrink-0"></i>
                    <span class="whitespace-nowrap transition-all duration-200" :class="open ? 'opacity-100 w-auto' : 'opacity-0 w-0 overflow-hidden'">Inspeksi & Retur</span>
                </a>
            @endif

            <!-- Divider -->
            <div class="my-3 mx-1 border-t" style="border-color: rgba(255,255,255,0.07);"></div>

            <!-- Design System -->
            <!-- <a href="{{ url('/design-system') }}"
               class="group relative flex items-center gap-3 rounded-xl text-sm font-semibold transition-all duration-150 overflow-hidden"
               :class="open ? 'px-3.5 py-2.5' : 'px-0 py-2.5 justify-center'"
               style="background: rgba(213,160,7,0.10); border: 1px solid rgba(213,160,7,0.22); color: rgba(251,206,107,0.9);">
                <svg class="w-5 h-5 shrink-0" style="color: #d5a007;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                </svg>
                <span class="whitespace-nowrap transition-all duration-200" :class="open ? 'opacity-100 w-auto' : 'opacity-0 w-0 overflow-hidden'">Design System</span>
            </a> -->
        </nav>
    </aside>

@else
    @php
        $isHome = request()->is('/') || request()->routeIs('home');
    @endphp

    <!-- Customer Top Navigation Bar Container -->
    <div x-data="{
        isHome: {{ $isHome ? 'true' : 'false' }},
        isHovered: false,
        mobileOpen: false,
        isTouch: false,
        headerHeight: 64,
        lastScrollY: 0,
        get isVisible() {
            return this.isHome || this.isTouch || this.isHovered || this.mobileOpen;
        },
        updateHeight() {
            if (this.$refs.navbarHeader) {
                this.headerHeight = Math.max(this.$refs.navbarHeader.offsetHeight, 64);
            }
        },
        init() {
            if (!this.isHome) {
                this.isTouch = !window.matchMedia('(hover: hover)').matches;
                this.$nextTick(() => this.updateHeight());
                window.addEventListener('resize', () => this.updateHeight(), { passive: true });
                document.fonts?.ready?.then(() => this.updateHeight());
                this.$watch('mobileOpen', () => this.$nextTick(() => this.updateHeight()));
            }
        },
        handleMouseMove(e) {
            if (this.isHome || this.mobileOpen) return;
            // Deteksi jangkauan kursor: muncul saat kursor berada dalam jangkauan 70px dari atas layar
            if (e.clientY <= 70) {
                this.isHovered = true;
            } else if (e.clientY > this.headerHeight && this.isHovered) {
                this.isHovered = false;
            }
        },
        handleScroll() {
            if (this.isHome || this.mobileOpen) return;
            const currentScrollY = window.scrollY;
            if (currentScrollY > this.lastScrollY && currentScrollY > 60 && this.isHovered) {
                this.isHovered = false;
            }
            this.lastScrollY = currentScrollY;
        }
    }"
    @if (!$isHome)
        @mousemove.window.passive="handleMouseMove($event)"
        @scroll.window.passive="handleScroll()"
        @mouseleave.window="if (!isHome && !mobileOpen) isHovered = false"
    @endif
    >
        @if (!$isHome)
            {{-- Sensor hotspot transparan di area atas layar (tinggi 64px) untuk memicu hover instan --}}
            <div class="fixed top-0 inset-x-0 h-16 z-40 pointer-events-auto"
                 x-show="!isVisible"
                 @mouseenter="isHovered = true"
                 aria-hidden="true"></div>
        @endif

        <!-- Customer Top Navigation Bar -->
        <header x-ref="navbarHeader"
                @if (!$isHome)
                    @mouseenter="isHovered = true"
                    @mouseleave="isHovered = false"
                    @focusin="isHovered = true"
                    @focusout="isHovered = false"
                    class="fixed top-0 inset-x-0 z-50 backdrop-blur-md border-b transition-all duration-300 ease-in-out"
                    :class="isVisible ? 'translate-y-0 opacity-100 shadow-xl pointer-events-auto' : '-translate-y-full opacity-0 pointer-events-none'"
                @else
                    class="sticky top-0 z-40 backdrop-blur-md border-b"
                @endif
                style="background: rgba(22, 29, 21, 0.96); border-color: rgba(249, 227, 182, 0.15);">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <!-- Brand Logo -->
            <div class="flex items-center gap-6">
                <a href="{{ url('/') }}" class="focus:outline-none flex items-center group py-1" aria-label="Tendaku">
                    <img src="{{ asset('images/Logo.svg') }}"
                         alt="Tendaku"
                         class="h-11 sm:h-12 w-auto object-contain transition-transform duration-200 group-hover:scale-105"
                         style="filter: brightness(1.22) drop-shadow(0 1px 2px rgba(0,0,0,0.6));"
                         width="48" height="48">
                </a>

                <!-- Nav Links Desktop -->
                <div class="hidden md:flex items-center space-x-1">
                    <a href="{{ url('/') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-150
                              {{ request()->is('/') ? 'text-sunglow-300 bg-white/10 shadow-sm border border-sunglow-300/30' : 'text-wheat-200 hover:text-white hover:bg-white/10' }}">
                        Beranda
                    </a>
                    <a href="{{ url('/katalog') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-150
                              {{ request()->routeIs('customer.catalog.index', 'customer.product.show', 'checkout.index') ? 'text-sunglow-300 bg-white/10 shadow-sm border border-sunglow-300/30' : 'text-wheat-200 hover:text-white hover:bg-white/10' }}">
                        Katalog Alat
                    </a>
                    @guest
                        <a href="{{ route('customer.rental-status.index') }}"
                           title="Cek status penyewaan dengan kode dari receipt"
                           class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-150
                                  {{ request()->routeIs('customer.rental-status.*') ? 'text-sunglow-300 bg-white/10 shadow-sm border border-sunglow-300/30' : 'text-wheat-200 hover:text-white hover:bg-white/10' }}">
                            Status Sewa
                        </a>
                    @endguest
                    @auth
                        <a href="{{ route('customer.packages.index') }}"
                           title="Paket camping siap sewa"
                           class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-150
                                  {{ request()->routeIs('customer.packages.*') ? 'text-sunglow-300 bg-white/10 shadow-sm border border-sunglow-300/30' : 'text-wheat-200 hover:text-white hover:bg-white/10' }}">
                            Paket Camping
                        </a>
                    @endauth
                    @auth
                        <a href="{{ route('customer.booking.index') }}"
                           class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-150
                                  {{ request()->routeIs('customer.booking.index', 'booking.status', 'booking.receipt', 'booking.receipt.photo') ? 'text-sunglow-300 bg-white/10 shadow-sm border border-sunglow-300/30' : 'text-wheat-200 hover:text-white hover:bg-white/10' }}">
                            Riwayat Sewa
                        </a>
                    @endauth
                    <!-- Design System pill -->
                    <a href="{{ url('/design-system') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all duration-150"
                       style="background: rgba(251, 206, 107, 0.12); color: #fbce6b; border: 1px solid rgba(251, 206, 107, 0.3);"
                       onmouseover="this.style.background='rgba(251,206,107,0.22)'" onmouseout="this.style.background='rgba(251,206,107,0.12)'">
                        <span class="w-1.5 h-1.5 rounded-full" style="background: #fbce6b;"></span>
                        Design System
                    </a>
                </div>
            </div>

            <!-- Right Actions -->
            <div class="flex items-center gap-2 sm:gap-2.5">
                @guest
                    <!-- Akses cepat cek status di layar kecil (khusus tamu) -->
                    <a href="{{ route('customer.rental-status.index') }}"
                       class="md:hidden p-2 text-sunglow-300 bg-white/10 hover:bg-white/15 rounded-xl transition-all duration-150 flex items-center justify-center"
                       title="Cek Status Sewa"
                       aria-label="Cek Status Sewa">
                        <i data-lucide="package-search" class="w-5 h-5"></i>
                    </a>
                @endguest


                <!-- Cart (khusus member yang sudah login) -->
                @auth
                    @php
                        $activeCart = app(\App\Services\Booking\CartService::class)->get(auth()->user());
                        $cartQty = is_array($activeCart) ? (int) ($activeCart['qty'] ?? 0) : 0;
                    @endphp
                    <div
                        x-data="{ qty: {{ $cartQty }} }"
                        @cart-updated.window="
                            const evCount = ($event && $event.detail) ? ($event.detail.count ?? $event.detail[0]?.count) : null;
                            qty = (typeof evCount === 'number') ? evCount : (qty > 0 ? qty : 1);
                        "
                    >
                        <a href="{{ route('customer.cart.index') }}"
                           class="relative p-2 text-wheat-200 hover:text-white hover:bg-white/10 rounded-xl transition-all duration-150 flex items-center justify-center"
                           title="Keranjang Sewa"
                        >
                            <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                            <span
                                x-show="qty > 0"
                                x-cloak
                                x-text="qty"
                                class="absolute -top-0.5 -right-0.5 min-w-[1.1rem] h-[1.1rem] px-0.5 rounded-full font-bold text-[10px] flex items-center justify-center text-darkbrown-900 font-bold"
                                style="background: linear-gradient(135deg, #fbce6b, #d5a007); box-shadow: 0 1px 6px rgba(213,160,7,0.5);"
                            >
                                {{ $cartQty > 0 ? $cartQty : '' }}
                            </span>
                        </a>
                    </div>
                @endauth

                <!-- Auth -->
                @auth
                    @if (auth()->user()->isVendorStaff())
                        @php
                            $user = auth()->user();

                            $vendorDashboard = $user->isVendorOwner()
                                ? route('vendor.owner.dashboard')
                                : route('kasir.dashboard');
                        @endphp

                        <a href="{{ $vendorDashboard }}"
                           class="btn-tendaku-primary !py-2 !px-3.5 text-xs sm:text-sm">
                            Dashboard Vendor
                        </a>
                    @elseif (auth()->user()->isSuperAdmin())
                        <a href="{{ route('dashboard') }}"
                           class="btn-tendaku-primary !py-2 !px-3.5 text-xs sm:text-sm">
                            Dashboard Admin
                        </a>
                    @else
                        <a href="{{ route('profile') }}"
                           class="hidden sm:inline-flex px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-wheat-200 hover:text-white border border-wheat-400/30 hover:bg-white/10 transition-all duration-150">
                            Akun Saya
                        </a>
                    @endif

                    {{-- Logout (gaya sama dengan tombol logout kasir) --}}
                    <form method="POST" action="{{ route('logout') }}" class="inline-block">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold text-red-300 bg-red-950/40 hover:bg-red-900/60 border border-red-500/30 transition-all duration-150 shadow-sm cursor-pointer"
                                title="Keluar dari sesi akun">
                            <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-wheat-200 hover:text-white border border-wheat-400/30 hover:bg-white/10 transition-all duration-150">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs sm:text-sm font-bold text-darkbrown-900 shadow-sm transition-all duration-150"
                       style="background: #fbce6b;"
                       onmouseover="this.style.background='#f5b63e'" onmouseout="this.style.background='#fbce6b'">
                        Daftar
                    </a>
                @endauth

                <!-- Mobile Hamburger Toggle -->
                <button @click="mobileOpen = !mobileOpen"
                        class="md:hidden p-2 text-wheat-200 hover:text-white hover:bg-white/10 rounded-xl transition-all duration-150 flex items-center justify-center cursor-pointer"
                        aria-label="Menu Navigasi">
                    <i data-lucide="menu" class="w-5 h-5" x-show="!mobileOpen"></i>
                    <i data-lucide="x" class="w-5 h-5" x-show="mobileOpen" x-cloak></i>
                </button>
            </div>
        </nav>

        <!-- Mobile Drawer Navigation -->
        <div x-show="mobileOpen"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden px-4 pt-2 pb-5 space-y-1.5 border-t"
             style="background: rgba(18, 24, 17, 0.98); border-color: rgba(255, 255, 255, 0.08);">
            <a href="{{ url('/') }}"
               class="block px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all
                      {{ request()->is('/') ? 'text-sunglow-300 bg-white/10 border border-sunglow-300/30' : 'text-wheat-200 hover:text-white hover:bg-white/10' }}">
                Beranda
            </a>
            <a href="{{ url('/katalog') }}"
               class="block px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all
                      {{ request()->routeIs('customer.catalog.index', 'customer.product.show') ? 'text-sunglow-300 bg-white/10 border border-sunglow-300/30' : 'text-wheat-200 hover:text-white hover:bg-white/10' }}">
                Katalog Alat
            </a>
            @guest
                <a href="{{ route('customer.rental-status.index') }}"
                   class="block px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all
                          {{ request()->routeIs('customer.rental-status.*') ? 'text-sunglow-300 bg-white/10 border border-sunglow-300/30' : 'text-wheat-200 hover:text-white hover:bg-white/10' }}">
                    Status Sewa
                </a>
            @endguest
            @auth
                <a href="{{ route('customer.packages.index') }}"
                   class="block px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all text-wheat-200 hover:text-white hover:bg-white/10">
                    Paket Camping
                </a>
                <a href="{{ route('customer.booking.index') }}"
                   class="block px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all text-wheat-200 hover:text-white hover:bg-white/10">
                    Riwayat Sewa
                </a>
                <a href="{{ route('profile') }}"
                   class="block px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all text-wheat-200 hover:text-white hover:bg-white/10">
                    Akun Saya
                </a>
            @endauth
            <a href="{{ url('/design-system') }}"
               class="block px-3.5 py-2 rounded-xl text-xs font-bold text-sunglow-300 bg-sunglow-400/10 border border-sunglow-300/30">
                • Design System
            </a>
            @guest
                <div class="pt-2 border-t border-white/10 flex items-center gap-2">
                    <a href="{{ route('login') }}" class="flex-1 text-center py-2 rounded-xl text-xs font-semibold text-wheat-200 border border-wheat-400/30 hover:bg-white/10">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" class="flex-1 text-center py-2 rounded-xl text-xs font-bold text-darkbrown-900 bg-sunglow-400">
                        Daftar
                    </a>
                </div>
            @endguest
        </div>
    </header>

    @if (!$isHome)
        {{-- Spacer dinamis: mendorong konten ke bawah (turun) secara sinkron saat topbar muncul, sehingga topbar tidak menutupi konten --}}
        <div class="transition-all duration-300 ease-in-out overflow-hidden"
             :style="isVisible ? 'height: ' + headerHeight + 'px;' : 'height: 0px;'"
             aria-hidden="true"></div>
    @endif
    </div>
@endif
