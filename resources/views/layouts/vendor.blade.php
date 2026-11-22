<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Tendaku - Vendor & POS Portal' }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full font-sans antialiased text-darkbrown-800" style="background-color: #f2ede3;" x-data="{ showLogoutModal: false }" @open-logout-modal.window="showLogoutModal = true">
    <div class="min-h-screen flex">
        <!-- Vendor / Kasir Sidebar -->
        @if (auth()->check() && (auth()->user()->isVendorAdmin() || !auth()->user()->isVendorOwner()))
            <x-navbar-kasir />
        @else
            <x-navbar role="vendor" />
        @endif

        <!-- Content area (automatically resizes alongside sidebar) -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top POS App Bar (Semi-Modern Taste-Skill Standard) -->
            <header class="sticky top-0 z-20 flex items-center justify-between gap-4 px-4 sm:px-6 py-2.5 bg-white/95 backdrop-blur-md border-b border-wheat-200/90 shadow-2xs">

                <!-- Left: Nama Toko Vendor -->
                <div class="flex items-center gap-2.5 min-w-0">
                    <!-- Mobile Hamburger Button for Kasir / Vendor Sidebar -->
                    <button type="button"
                            @click="$dispatch('toggle-kasir-sidebar')"
                            class="lg:hidden p-2 -ml-1.5 rounded-xl text-darkbrown-700 hover:bg-wheat-100 active:scale-95 transition-all"
                            aria-label="Toggle Menu Sidebar">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>

                    <!-- Nama Toko Vendor -->
                    <div class="flex items-center gap-2 text-darkbrown-900 min-w-0">
                        <div class="w-7 h-7 rounded-lg bg-wheat-100 border border-wheat-200/90 flex items-center justify-center text-darkbrown-700 shrink-0">
                            <i data-lucide="store" class="w-3.5 h-3.5 text-avocado-800"></i>
                        </div>
                        <span class="font-extrabold text-sm tracking-tight text-darkbrown-900 truncate">
                            {{ auth()->user()?->vendor?->name ?? 'Tendaku Store Central' }}
                        </span>
                    </div>
                </div>

                <!-- Right: Notifications, Cashier Profile & Actions -->
                <div class="flex items-center gap-2 sm:gap-2.5 shrink-0">

                    <!-- Notification Bell -->
                    @if (auth()->check() && (auth()->user()->isVendorAdmin() || auth()->user()->isVendorOwner()))
                        <livewire:kasir.notification-bell />
                    @endif

                    <!-- Vertical Divider -->
                    <div class="w-px h-6 bg-wheat-200/90 mx-0.5"></div>

                    <!-- User Pill (Clickable Profile) -->
                    @php
                        $isVendorOwner = auth()->user()?->isVendorOwner() ?? false;
                        $profileRoute = auth()->user()?->isVendorAdmin()
                            ? route('kasir.profile')
                            : route('profile');
                        $userInitials = strtoupper(substr(auth()->user()->name ?? 'TK', 0, 2));
                        $userRoleLabel = $isVendorOwner
                            ? 'Owner'
                            : (auth()->user()?->isVendorAdmin() ? 'Kasir' : 'Staf');
                    @endphp

                    <div class="flex items-center gap-1.5">
                        <a href="{{ $profileRoute }}"
                           wire:navigate
                           class="flex items-center gap-2.5 p-1 pr-2 sm:pr-3 rounded-xl hover:bg-wheat-100/70 border border-transparent hover:border-wheat-200/80 active:scale-[0.98] transition-all duration-150 group cursor-pointer"
                           title="{{ $isVendorOwner ? 'Lihat Profil' : 'Lihat Profil Kasir' }}">
                            <div class="w-8 h-8 rounded-lg font-black flex items-center justify-center text-xs text-white bg-avocado-700 border border-avocado-600/40 shadow-2xs group-hover:bg-avocado-800 transition-colors">
                                {{ $userInitials }}
                            </div>
                            <div class="hidden sm:block text-left">
                                <div class="flex items-center gap-1.5">
                                    <p class="text-xs font-extrabold text-darkbrown-900 group-hover:text-avocado-800 transition-colors leading-none">
                                        {{ auth()->user()->name ?? 'Kasir Toko' }}
                                    </p>
                                    @unless ($isVendorOwner)
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-wheat-200 text-darkbrown-800 border border-wheat-300/80 uppercase">
                                            {{ $userRoleLabel }}
                                        </span>
                                    @endunless
                                </div>
                                @unless ($isVendorOwner)
                                    <p class="text-[10px] text-darkbrown-500 group-hover:text-darkbrown-700 transition-colors mt-0.5 font-medium truncate max-w-[140px]">
                                        {{ auth()->user()?->vendor?->name ?? 'Tendaku Rental' }}
                                    </p>
                                @endunless
                            </div>
                        </a>

                        @unless ($isVendorOwner)
                            <!-- Trigger Modal Logout Button (Kasir / Non-Owner) -->
                            <button type="button"
                                    @click="showLogoutModal = true"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center text-darkbrown-400 hover:text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 active:scale-95 transition-all duration-150 cursor-pointer"
                                    title="Keluar / Logout">
                                <i data-lucide="log-out" class="w-4 h-4"></i>
                            </button>
                        @endunless
                    </div>
                </div>
            </header>

            <!-- Page Header Slot -->
            @if (isset($header))
                <header style="background: #ffffff; border-bottom: 1px solid rgba(232,224,204,0.7);">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Global Toast Notification -->
            <x-toast />

            <!-- Main Content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full">
                {{ $slot }}
            </main>
        </div>
    </div>

    <!-- ==========================================
         MODAL KONFIRMASI LOGOUT (Semi-Modern Modal Card)
         ========================================== -->
    <template x-teleport="body">
        <div x-show="showLogoutModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-darkbrown-950/60 backdrop-blur-sm"
             style="display: none;"
             @keydown.escape.window="showLogoutModal = false">

            <!-- Backdrop Click -->
            <div class="absolute inset-0" @click="showLogoutModal = false"></div>

            <!-- Modal Card -->
            <div x-show="showLogoutModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                 class="relative w-full max-w-sm bg-white rounded-3xl shadow-2xl border border-wheat-200 overflow-hidden z-10">

                <!-- Decorative Top Ambient Header -->
                <div class="h-2 w-full"
                     style="background: linear-gradient(90deg, #6c8b08 0%, #d5a007 50%, #dc2626 100%);"></div>

                <div class="p-6 text-center">
                    <!-- Icon Bubble -->
                    <div class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center shadow-inner relative"
                         style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border: 1px solid #fecaca;">
                        <svg class="w-7 h-7 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-red-500 border-2 border-white animate-ping"></span>
                    </div>

                    @php
                        $isKasir = auth()->check() && (auth()->user()->isVendorAdmin() || !auth()->user()->isVendorOwner());
                    @endphp

                    @if($isKasir)
                        <!-- Tampilan Khusus Kasir -->
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-sunglow-100 text-darkbrown-800 border border-sunglow-300 mb-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-sunglow-600"></span>
                            Sesi Kasir POS
                        </span>
                        <h3 class="text-lg font-extrabold text-darkbrown-900 tracking-tight">
                            Apakah Anda ingin logout?
                        </h3>
                        <p class="text-xs text-darkbrown-500 mt-2 leading-relaxed">
                            Pastikan seluruh transaksi pickup, penerimaan denda, dan scan barcode saat ini telah tersimpan rapi sebelum mengakhiri sesi kerja Anda.
                        </p>
                    @else
                        <!-- Tampilan Khusus Owner / Vendor Lain -->
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-avocado-100 text-avocado-900 border border-avocado-300 mb-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-avocado-600"></span>
                            Portal Vendor
                        </span>
                        <h3 class="text-lg font-extrabold text-darkbrown-900 tracking-tight">
                            Konfirmasi Keluar Akun
                        </h3>
                        <p class="text-xs text-darkbrown-500 mt-2 leading-relaxed">
                            Apakah Anda yakin ingin keluar dari portal toko? Anda harus memasukkan email dan password untuk masuk kembali.
                        </p>
                    @endif

                    <!-- User Identifier Pill -->
                    <div class="mt-4 p-2.5 rounded-xl bg-wheat-50 border border-wheat-200/80 flex items-center justify-center gap-2">
                        <div class="w-5 h-5 rounded-lg bg-darkbrown-800 text-white font-bold text-[10px] flex items-center justify-center">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <span class="text-xs font-bold text-darkbrown-800 truncate max-w-[200px]">
                            {{ auth()->user()->name ?? 'Pengguna' }}
                        </span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 flex items-center gap-3">
                        <button type="button"
                                @click="showLogoutModal = false"
                                class="flex-1 py-2.5 px-4 rounded-xl text-xs font-bold text-darkbrown-700 bg-wheat-100 hover:bg-wheat-200 border border-wheat-300/80 transition-all duration-150 active:scale-95 cursor-pointer">
                            Tetap di Sini
                        </button>

                        <form method="POST" action="{{ route('logout') }}" class="flex-1 m-0">
                            @csrf
                            <button type="submit"
                                    class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-red-600 to-rose-700 hover:from-red-700 hover:to-rose-800 transition-all duration-150 active:scale-95 shadow-md shadow-red-600/25 cursor-pointer">
                                Ya, Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>

    @livewireScripts
</body>
</html>
