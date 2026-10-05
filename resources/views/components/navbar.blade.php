@props(['role' => 'customer'])

@if ($role === 'vendor')
    <!-- Vendor Sidebar / Navigation with Balanced Wheat & Warm Cream Palette -->
    <aside class="w-full sm:w-64 bg-wheat-200 text-darkbrown-900 flex-shrink-0 flex flex-col border-r border-wheat-300 min-h-screen shadow-sm">
        <!-- Brand Header -->
        <div class="p-5 border-b border-wheat-300 flex items-center justify-between">
            <x-application-logo size="md" :withText="false" />
            <div class="ml-3 flex-1 min-w-0">
                <h1 class="text-base font-extrabold text-darkbrown-900 tracking-tight leading-none">Tendaku</h1>
                <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-darkbrown-600 mt-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-avocado-600 animate-pulse"></span>
                    Portal Vendor
                </span>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="p-4 space-y-1.5 flex-1">
            <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-darkbrown-500 mb-2">Menu Utama</p>

            <a href="{{ route('vendor.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition duration-150 {{ request()->routeIs('vendor.dashboard') ? 'bg-avocado-600 text-white shadow-sm' : 'text-darkbrown-800 hover:text-darkbrown-950 hover:bg-wheat-300/60' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Dashboard & POS</span>
            </a>

            <a href="{{ route('vendor.units') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition duration-150 {{ request()->routeIs('vendor.units') ? 'bg-avocado-600 text-white shadow-sm' : 'text-darkbrown-800 hover:text-darkbrown-950 hover:bg-wheat-300/60' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <span>Kelola Alat & Stok</span>
            </a>

            <a href="{{ route('vendor.pickup') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition duration-150 {{ request()->routeIs('vendor.pickup') ? 'bg-avocado-600 text-white shadow-sm' : 'text-darkbrown-800 hover:text-darkbrown-950 hover:bg-wheat-300/60' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span>Pickup / Serah Terima</span>
            </a>

            <a href="{{ route('vendor.return') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition duration-150 {{ request()->routeIs('vendor.return') ? 'bg-avocado-600 text-white shadow-sm' : 'text-darkbrown-800 hover:text-darkbrown-950 hover:bg-wheat-300/60' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 022 2h2a2 2 0 022-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                <span>Inspeksi & Retur</span>
            </a>

            <a href="{{ url('/design-system') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition duration-150 bg-goldenrod-500/20 text-darkbrown-900 border border-goldenrod-500/40 hover:bg-goldenrod-500/30">
                <svg class="w-5 h-5 shrink-0 text-goldenrod-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                </svg>
                <span>Design System Guide</span>
                <span class="ml-auto px-1.5 py-0.5 text-[10px] font-bold rounded-md bg-goldenrod-500 text-white">Preview</span>
            </a>
        </nav>

        <!-- Quick POS Kasir Action & Switch Mode -->
        <div class="p-4 border-t border-wheat-300 space-y-3 bg-wheat-300/40">
            <div class="p-3 bg-white rounded-xl border border-wheat-300 shadow-sm">
                <div class="flex items-center justify-between text-xs mb-1">
                    <span class="text-darkbrown-600 font-medium">Kasir POS Offline</span>
                    <span class="w-2 h-2 rounded-full bg-avocado-600"></span>
                </div>
                <button class="w-full mt-2 py-2 px-3 rounded-lg text-xs font-bold text-white bg-avocado-600 hover:bg-avocado-700 active:bg-avocado-800 transition flex items-center justify-center gap-1.5 shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Transaksi Baru (POS)
                </button>
            </div>

            <div class="flex items-center justify-between text-xs text-darkbrown-700 pt-1">
                <a href="{{ url('/') }}" class="hover:text-darkbrown-950 flex items-center gap-1 font-medium">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Lihat Mode Pelanggan
                </a>
            </div>
        </div>
    </aside>
@else
    <!-- Customer Top Navigation Bar -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-wheat-200">
        <!-- Top Announcement Bar -->
        <div class="bg-gradient-to-r from-wheat-200 via-sunglow-200 to-wheat-200 text-darkbrown-800 text-[11px] sm:text-xs font-semibold py-1.5 px-4 text-center border-b border-wheat-300/60">
            ⛺ Promo Petualangan Spesial: Diskon Sewa Tenda Dome & Paket Camping Hemat Akhir Pekan!
        </div>

        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <!-- Brand Logo -->
            <div class="flex items-center gap-6">
                <a href="{{ url('/') }}" class="focus:outline-none">
                    <x-application-logo size="md" />
                </a>

                <!-- Nav Links Desktop -->
                <div class="hidden md:flex items-center space-x-1 lg:space-x-2">
                    <a href="{{ url('/') }}" class="px-3 py-2 rounded-xl text-sm font-semibold transition {{ request()->is('/') ? 'text-avocado-600 bg-avocado-50' : 'text-darkbrown-700 hover:text-darkbrown-900 hover:bg-wheat-100/60' }}">
                        Beranda
                    </a>
                    <a href="{{ url('/katalog') }}" class="px-3 py-2 rounded-xl text-sm font-semibold transition {{ request()->is('katalog*') ? 'text-avocado-600 bg-avocado-50' : 'text-darkbrown-700 hover:text-darkbrown-900 hover:bg-wheat-100/60' }}">
                        Katalog Alat
                    </a>
                    <a href="#" class="px-3 py-2 rounded-xl text-sm font-medium text-darkbrown-700 hover:text-darkbrown-900 hover:bg-wheat-100/60 transition">
                        Paket Camping
                    </a>
                    <a href="{{ url('/design-system') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-darkbrown-800 bg-sunglow-200 hover:bg-sunglow-300 transition">
                        <span class="w-1.5 h-1.5 rounded-full bg-goldenrod-500"></span>
                        Design System
                    </a>
                </div>
            </div>

            <!-- Right Actions -->
            <div class="flex items-center gap-2 sm:gap-3">
                <button class="p-2 text-darkbrown-600 hover:text-darkbrown-900 hover:bg-wheat-100 rounded-xl transition" title="Cari alat camping">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>

                <a href="#" class="relative p-2 text-darkbrown-700 hover:text-darkbrown-900 hover:bg-wheat-100 rounded-xl transition" title="Keranjang Sewa">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span class="absolute top-1 right-1 w-4 h-4 rounded-full bg-goldenrod-500 text-white font-bold text-[10px] flex items-center justify-center">
                        2
                    </span>
                </a>

                @auth
                    <a href="{{ route('dashboard') }}" class="btn-tendaku-primary !py-2 !px-3 text-xs sm:text-sm">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex btn-tendaku-secondary !py-2 !px-3 text-xs sm:text-sm">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" class="btn-tendaku-primary !py-2 !px-3 text-xs sm:text-sm">
                        Daftar
                    </a>
                @endauth
            </div>
        </nav>
    </header>
@endif