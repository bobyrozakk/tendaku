@props(['role' => 'customer'])

@if ($role === 'vendor')
    <x-vendor.sidebar />
@else
    <!-- Customer Top Navigation Bar Container -->
    <div x-data="{ mobileOpen: false, scrolled: false }"
         x-init="scrolled = window.scrollY > 8"
         @scroll.window.passive="scrolled = window.scrollY > 8"
         class="sticky top-0 z-40">
        <!-- Customer Top Navigation Bar -->
        <header x-ref="navbarHeader"
                class="tendaku-navbar"
                :class="{ 'is-scrolled': scrolled && !mobileOpen }">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <!-- Brand Logo -->
            <div class="flex items-center gap-6 lg:gap-10">
                <a href="{{ url('/') }}" class="focus:outline-none flex items-center group py-1" aria-label="Tendaku">
                    <img src="{{ asset('images/Logo.svg') }}"
                         alt="Tendaku"
                         class="h-10 sm:h-11 w-auto object-contain transition-transform duration-200 group-hover:scale-105"
                         style="filter: brightness(1.22) drop-shadow(0 1px 2px rgba(0,0,0,0.6));"
                         width="48" height="48">
                </a>

                <!-- Nav Links Desktop -->
                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ url('/') }}"
                       class="tendaku-nav-link {{ request()->is('/') ? 'is-active' : '' }}">
                        Beranda
                    </a>
                    <a href="{{ url('/katalog') }}"
                       class="tendaku-nav-link {{ request()->routeIs('customer.catalog.index', 'customer.product.show', 'checkout.index') ? 'is-active' : '' }}">
                        Katalog Alat
                    </a>
                    @guest
                        <a href="{{ route('customer.rental-status.index') }}"
                           title="Cek status penyewaan dengan kode dari receipt"
                           class="tendaku-nav-link {{ request()->routeIs('customer.rental-status.*') ? 'is-active' : '' }}">
                            Status Sewa
                        </a>
                    @endguest
                    @auth
                        <a href="{{ route('customer.packages.index') }}"
                           title="Paket camping siap sewa"
                           class="tendaku-nav-link {{ request()->routeIs('customer.packages.*') ? 'is-active' : '' }}">
                            Paket Camping
                        </a>
                    @endauth
                    @auth
                        <a href="{{ route('customer.booking.index') }}"
                           class="tendaku-nav-link {{ request()->routeIs('customer.booking.index', 'booking.status', 'booking.receipt', 'booking.receipt.photo') ? 'is-active' : '' }}">
                            Riwayat Sewa
                        </a>
                    @endauth
                </div>
            </div>

            <!-- Right Actions -->
            <div class="flex items-center gap-2 sm:gap-3">
                @guest
                    <!-- Akses cepat cek status di layar kecil (khusus tamu) -->
                    <a href="{{ route('customer.rental-status.index') }}"
                       class="tendaku-nav-icon md:hidden"
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
                           class="tendaku-nav-icon relative"
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
                        @php
                            $navbarUser = auth()->user();
                        @endphp
                        <a href="{{ route('profile') }}"
                           class="inline-flex items-center justify-center rounded-full p-0.5 transition-all duration-200 hover:ring-2 hover:ring-sunglow-400/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-sunglow-300"
                           title="{{ $navbarUser->name }} (Akun Saya)"
                           aria-label="Akun Saya">
                            @if ($navbarUser->avatar_url)
                                <img src="{{ $navbarUser->avatar_url }}"
                                     alt="{{ $navbarUser->name }}"
                                     referrerpolicy="no-referrer"
                                     class="w-9 h-9 rounded-full object-cover border border-white/20 shadow-sm"
                                     onerror="this.onerror=null; this.parentElement.innerHTML='<span class=\'w-9 h-9 rounded-full bg-gradient-to-br from-sunglow-300 to-goldenrod-500 text-darkbrown-900 font-bold text-xs flex items-center justify-center border border-white/20 shadow-sm\'>{{ addslashes($navbarUser->initials) }}</span>';">
                            @else
                                <span class="w-9 h-9 rounded-full bg-gradient-to-br from-sunglow-300 to-goldenrod-500 text-darkbrown-900 font-bold text-xs flex items-center justify-center border border-white/20 shadow-sm tracking-wider">
                                    {{ $navbarUser->initials }}
                                </span>
                            @endif
                        </a>
                    @endif

                    {{-- Logout (gaya sama dengan tombol logout kasir) --}}
                    <form method="POST" action="{{ route('logout') }}" class="inline-block">
                        @csrf
                        <button type="submit"
                                class="tendaku-nav-icon text-red-300/90 hover:text-red-200 hover:bg-red-500/15 cursor-pointer"
                                title="Keluar dari sesi akun">
                            <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="tendaku-nav-btn-secondary hidden sm:inline-flex">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}"
                       class="tendaku-nav-btn-primary">
                        Daftar
                    </a>
                @endauth

                <!-- Mobile Hamburger Toggle -->
                <button @click="mobileOpen = !mobileOpen"
                        class="tendaku-nav-icon md:hidden cursor-pointer"
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
             class="md:hidden px-4 pt-2 pb-5 space-y-1 border-t border-white/10">
            <a href="{{ url('/') }}"
               class="tendaku-nav-link tendaku-nav-link-block {{ request()->is('/') ? 'is-active' : '' }}">
                Beranda
            </a>
            <a href="{{ url('/katalog') }}"
               class="tendaku-nav-link tendaku-nav-link-block {{ request()->routeIs('customer.catalog.index', 'customer.product.show') ? 'is-active' : '' }}">
                Katalog Alat
            </a>
            @guest
                <a href="{{ route('customer.rental-status.index') }}"
                   class="tendaku-nav-link tendaku-nav-link-block {{ request()->routeIs('customer.rental-status.*') ? 'is-active' : '' }}">
                    Status Sewa
                </a>
            @endguest
            @auth
                <a href="{{ route('customer.packages.index') }}"
                   class="tendaku-nav-link tendaku-nav-link-block">
                    Paket Camping
                </a>
                <a href="{{ route('customer.booking.index') }}"
                   class="tendaku-nav-link tendaku-nav-link-block">
                    Riwayat Sewa
                </a>
                <a href="{{ route('profile') }}"
                   class="tendaku-nav-link tendaku-nav-link-block flex items-center gap-2.5">
                    @if (auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}"
                             alt="{{ auth()->user()->name }}"
                             referrerpolicy="no-referrer"
                             class="w-6 h-6 rounded-full object-cover border border-white/20 shadow-sm"
                             onerror="this.onerror=null; this.parentElement.querySelector('.mobile-initials-fallback')?.classList.remove('hidden'); this.remove();">
                        <span class="mobile-initials-fallback hidden w-6 h-6 rounded-full bg-gradient-to-br from-sunglow-300 to-goldenrod-500 text-darkbrown-900 font-bold text-[10px] flex items-center justify-center border border-white/20 shadow-sm">
                            {{ auth()->user()->initials }}
                        </span>
                    @else
                        <span class="w-6 h-6 rounded-full bg-gradient-to-br from-sunglow-300 to-goldenrod-500 text-darkbrown-900 font-bold text-[10px] flex items-center justify-center border border-white/20 shadow-sm">
                            {{ auth()->user()->initials }}
                        </span>
                    @endif
                    <span>Akun Saya</span>
                </a>
            @endauth
            @guest
                <div class="pt-3 mt-2 border-t border-white/10 flex items-center gap-2">
                    <a href="{{ route('login') }}" class="tendaku-nav-btn-secondary flex-1">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" class="tendaku-nav-btn-primary flex-1">
                        Daftar
                    </a>
                </div>
            @endguest
        </div>
    </header>
    </div>
@endif
