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
        <div class="px-4 py-4 flex items-center gap-3 border-b overflow-hidden" style="border-color: rgba(255,255,255,0.08);">
            <div class="p-1.5 rounded-xl shrink-0" style="background: rgba(108,139,8,0.18); border: 1px solid rgba(108,139,8,0.3);">
                <x-application-logo size="md" :withText="false" />
            </div>
            <div class="flex-1 min-w-0 transition-opacity duration-200" :class="open ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">
                <h1 class="text-sm font-extrabold tracking-tight leading-none whitespace-nowrap" style="color: #f5e9c8;">Tendaku</h1>
                <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-widest mt-1 whitespace-nowrap" style="color: rgba(245,233,200,0.45);">
                    <span class="w-1.5 h-1.5 rounded-full bg-avocado-400 animate-pulse"></span>
                    Portal Vendor
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
    <!-- Customer Top Navigation Bar -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-wheat-200">
        <!-- Top Announcement Bar -->
        <div class="text-darkbrown-900 text-[11px] sm:text-xs font-semibold py-1.5 px-4 text-center border-b border-wheat-300/60"
             style="background: linear-gradient(90deg, #f9e3b6 0%, #fbce6b 40%, #f5d97a 60%, #f9e3b6 100%);">
            ⛺ Promo Petualangan Spesial: Diskon Sewa Tenda Dome & Paket Camping Hemat Akhir Pekan!
        </div>

        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <!-- Brand Logo -->
            <div class="flex items-center gap-6">
                <a href="{{ url('/') }}" class="focus:outline-none">
                    <x-application-logo size="md" />
                </a>

                <!-- Nav Links Desktop -->
                <div class="hidden md:flex items-center space-x-1">
                    <a href="{{ url('/') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-150
                              {{ request()->is('/') ? 'text-avocado-700 bg-avocado-50 shadow-sm' : 'text-darkbrown-700 hover:text-darkbrown-900 hover:bg-wheat-100' }}">
                        Beranda
                    </a>
                    <a href="{{ url('/katalog') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-150
                              {{ request()->is('katalog*') ? 'text-avocado-700 bg-avocado-50 shadow-sm' : 'text-darkbrown-700 hover:text-darkbrown-900 hover:bg-wheat-100' }}">
                        Katalog Alat
                    </a>
                    <a href="#" class="px-3.5 py-2 rounded-xl text-sm font-medium text-darkbrown-700 hover:text-darkbrown-900 hover:bg-wheat-100 transition-all duration-150">
                        Paket Camping
                    </a>
                    <a href="{{ route('customer.weather.index') }}"
                       class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-150
                              {{ request()->routeIs('customer.weather.index') ? 'text-avocado-700 bg-avocado-50 shadow-sm' : 'text-darkbrown-700 hover:text-darkbrown-900 hover:bg-wheat-100' }}">
                        Cuaca
                    </a>
                    @auth
                        <a href="{{ route('customer.booking.index') }}"
                           class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-all duration-150
                                  {{ request()->routeIs('customer.booking.index') ? 'text-avocado-700 bg-avocado-50 shadow-sm' : 'text-darkbrown-700 hover:text-darkbrown-900 hover:bg-wheat-100' }}">
                            Riwayat Sewa
                        </a>
                    @endauth
                    <!-- Design System pill -->
                    <a href="{{ url('/design-system') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all duration-150"
                       style="background: rgba(213,160,7,0.12); color: #8a6500; border: 1px solid rgba(213,160,7,0.3);"
                       onmouseover="this.style.background='rgba(213,160,7,0.2)'" onmouseout="this.style.background='rgba(213,160,7,0.12)'">
                        <span class="w-1.5 h-1.5 rounded-full" style="background: #d5a007;"></span>
                        Design System
                    </a>
                </div>
            </div>

            <!-- Right Actions -->
            <div class="flex items-center gap-2 sm:gap-2.5">
                <!-- Search -->
                <button class="p-2 text-darkbrown-600 hover:text-darkbrown-900 hover:bg-wheat-100 rounded-xl transition-all duration-150" title="Cari alat camping">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>

                <!-- Cart -->
                <a href="#" class="relative p-2 text-darkbrown-700 hover:text-darkbrown-900 hover:bg-wheat-100 rounded-xl transition-all duration-150" title="Keranjang Sewa">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span class="absolute top-1 right-1 w-4 h-4 rounded-full font-bold text-[10px] flex items-center justify-center text-white"
                          style="background: linear-gradient(135deg, #d5a007, #fbce6b); box-shadow: 0 1px 6px rgba(213,160,7,0.5);">
                        2
                    </span>
                </a>

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
                           class="hidden sm:inline-flex btn-tendaku-secondary !py-2 !px-3.5 text-xs sm:text-sm">
                            Akun Saya
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex btn-tendaku-secondary !py-2 !px-3.5 text-xs sm:text-sm">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" class="btn-tendaku-primary !py-2 !px-3.5 text-xs sm:text-sm">
                        Daftar
                    </a>
                @endauth
            </div>
        </nav>
    </header>
@endif
