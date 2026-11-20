@props(['role' => 'customer'])

@if ($role === 'vendor')
    <x-vendor.sidebar />
@else
    <!-- Customer Top Navigation Bar Container -->
    <div x-data="{ mobileOpen: false }">
        <!-- Customer Top Navigation Bar -->
        <header x-ref="navbarHeader"
                class="sticky top-0 z-40 backdrop-blur-md border-b"
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
    </div>
@endif
