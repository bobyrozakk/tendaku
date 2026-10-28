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
<body class="min-h-full font-sans antialiased text-darkbrown-800" style="background-color: #f2ede3;" x-data="{ showLogoutModal: false }">
    <div class="min-h-screen flex">
        <!-- Vendor Shared Sidebar -->
        <x-navbar role="vendor" />

        <!-- Content area (automatically resizes alongside sidebar) -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top App Bar — matches dark sidebar tone -->
            <div class="sticky top-0 z-20 flex items-center justify-between gap-4 px-4 sm:px-6 py-3"
                 style="background: #ffffff; border-bottom: 1px solid #e8e0cc;">

                <!-- Left: Status Toko & Nama Toko -->

                <!-- Left: Status Toko & Nama Toko -->
                <div class="flex items-center gap-3">
                    <!-- Status Operasional Toko -->
                    <div
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border"
                        style="background: rgba(108,139,8,0.08); color: #4e6506; border-color: rgba(108,139,8,0.25);"
                    >
                        <span
                            class="w-2 h-2 rounded-full animate-pulse"
                            style="background: #6c8b08;"
                        ></span>
                        <span>Toko Rental Buka</span>
                    </div>

                    <!-- Nama Toko Vendor -->
                    <span
                        class="text-xs hidden sm:inline"
                        style="color: #b8a882;"
                    >
                        • {{ auth()->user()?->vendor?->name ?? 'Tendaku Store' }}
                    </span>
                </div>


                <!-- Right: Actions -->
                <div class="flex items-center gap-2">

                    <!-- Notification Bell -->
                    @if (auth()->check() && (auth()->user()->isVendorAdmin() || auth()->user()->isVendorOwner()))
                        <livewire:kasir.notification-bell />
                    @else
                        <button class="relative p-2 rounded-xl transition-all duration-150"
                                style="color: #7a6840;"
                                onmouseover="this.style.background='#f0e8d4'; this.style.color='#2b2202';"
                                onmouseout="this.style.background='transparent'; this.style.color='#7a6840';"
                                title="Notifikasi Baru">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full border-2 border-white"
                                  style="background: #d5a007;"></span>
                        </button>
                    @endif

                    <!-- View Website Link -->
                    <!-- <a href="{{ url('/') }}"
                       class="hidden md:inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg transition-all duration-150"
                       style="background: #f0e8d4; color: #4a3520; border: 1px solid #ddd0b0;"
                       onmouseover="this.style.background='#e6dcc5';"
                       onmouseout="this.style.background='#f0e8d4';">
                        <span>Lihat Website</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </a> -->

                    <!-- Divider -->
                    <div class="w-px h-6 mx-1" style="background: #e8e0cc;"></div>

                    <!-- User Pill (Clickable Profile) & Logout -->
                    <div class="flex items-center gap-2">
                        @php
                            $profileRoute = auth()->user()?->isVendorAdmin()
                                ? route('kasir.profile')
                                : route('profile');
                        @endphp
                        <a href="{{ $profileRoute }}"
                           wire:navigate
                           class="flex items-center gap-2.5 p-1 -m-1 rounded-2xl hover:bg-wheat-100/80 transition-all duration-150 group cursor-pointer"
                           title="Lihat Profil Kasir">
                            <div class="w-8 h-8 rounded-xl font-bold flex items-center justify-center text-xs text-white transition-transform group-hover:scale-105 duration-150 shadow-sm"
                                 style="background: linear-gradient(135deg, #6c8b08 0%, #4e6506 100%); box-shadow: 0 2px 8px rgba(108,139,8,0.3);">
                                {{ strtoupper(substr(auth()->user()->name ?? 'TK', 0, 2)) }}
                            </div>
                            <div class="hidden sm:block text-left">
                                <p class="text-xs font-bold leading-none text-darkbrown-900 group-hover:text-avocado-800 transition-colors">
                                    @if (auth()->user()?->isVendorOwner())
                                        Owner ({{ auth()->user()->name }})
                                    @elseif (auth()->user()?->isVendorAdmin())
                                        Admin ({{ auth()->user()->name }})
                                    @else
                                        {{ auth()->user()?->name ?? 'Staf Vendor' }}
                                    @endif
                                </p>
                                <p class="text-[10px] mt-0.5 truncate max-w-[120px] text-darkbrown-500 group-hover:text-darkbrown-700 transition-colors">
                                    {{ auth()->user()?->isVendorOwner()
                                        ? 'Vendor Owner Portal'
                                        : (auth()->user()?->isVendorAdmin()
                                            ? 'Vendor Admin Portal'
                                            : 'Kasir & Admin Portal') }}
                                </p>
                            </div>
                        </a>

                        <!-- Trigger Modal Logout -->
                        <button type="button"
                                @click="showLogoutModal = true"
                                class="p-1.5 rounded-lg text-darkbrown-500 hover:text-red-600 hover:bg-red-50 transition cursor-pointer"
                                title="Keluar / Logout">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

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
