<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Super Admin Panel - Tendaku' }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full font-sans antialiased text-darkbrown-800" style="background-color: #f4ede2;">
    <div class="min-h-screen flex">
        <!-- Super Admin Sidebar -->
        <aside x-data="{ open: false }"
               @mouseenter="open = true"
               @mouseleave="open = false"
               :class="open ? 'w-64' : 'w-[74px]'"
               class="sticky top-0 h-screen shrink-0 z-40 flex flex-col transition-all duration-300 ease-in-out shadow-2xl"
               style="background: linear-gradient(180deg, #18140c 0%, #241d08 55%, #151206 100%); border-right: 1px solid rgba(213, 160, 7, 0.15);">

            <!-- Brand Header -->
            <div class="px-4 py-4 flex items-center gap-3 border-b overflow-hidden" style="border-color: rgba(255,255,255,0.08);">
                <div class="p-1.5 rounded-xl shrink-0 shadow-md" style="background: linear-gradient(135deg, #d5a007 0%, #8a6500 100%);">
                    <x-application-logo size="md" :withText="false" />
                </div>
                <div class="flex-1 min-w-0 transition-opacity duration-200" :class="open ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">
                    <h1 class="text-sm font-extrabold tracking-tight leading-none whitespace-nowrap text-amber-100">Tendaku</h1>
                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest mt-1 text-goldenrod-400 whitespace-nowrap">
                        <span class="w-1.5 h-1.5 rounded-full bg-goldenrod-500 animate-pulse"></span>
                        Super Admin Panel
                    </span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 pt-4 px-2.5 space-y-1.5 overflow-hidden">
                <p class="px-2 text-[10px] font-bold uppercase tracking-widest mb-2 transition-all duration-200 whitespace-nowrap"
                   :class="open ? 'opacity-50' : 'opacity-0 h-0 mb-0 overflow-hidden'" style="color: #f5e9c8;">
                    Pengawasan Sistem
                </p>

                <!-- Dashboard GMV -->
                <a href="{{ route('admin.dashboard') }}"
                   class="group relative flex items-center gap-3 rounded-xl text-sm font-semibold transition-all duration-150 overflow-hidden"
                   :class="open ? 'px-3.5 py-2.5' : 'px-0 py-2.5 justify-center'"
                   style="{{ request()->routeIs('admin.dashboard')
                       ? 'background: linear-gradient(135deg, #d5a007 0%, #9e7500 100%); box-shadow: 0 4px 14px rgba(213,160,7,0.35); color: #18140c;'
                       : 'color: rgba(245,233,200,0.7);' }}"
                   onmouseover="if (!{{ request()->routeIs('admin.dashboard') ? 'true' : 'false' }}) { this.style.background='rgba(255,255,255,0.08)'; this.style.color='#ffffff'; }"
                   onmouseout="if (!{{ request()->routeIs('admin.dashboard') ? 'true' : 'false' }}) { this.style.background='transparent'; this.style.color='rgba(245,233,200,0.7)'; }">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="whitespace-nowrap transition-all duration-200" :class="open ? 'opacity-100 w-auto' : 'opacity-0 w-0 overflow-hidden'">
                        Dashboard GMV
                    </span>
                </a>

                <!-- Kelola Vendor & Approval -->
                <a href="{{ route('admin.vendors.index') }}"
                   class="group relative flex items-center gap-3 rounded-xl text-sm font-semibold transition-all duration-150 overflow-hidden"
                   :class="open ? 'px-3.5 py-2.5' : 'px-0 py-2.5 justify-center'"
                   style="{{ request()->routeIs('admin.vendors.*')
                       ? 'background: linear-gradient(135deg, #d5a007 0%, #9e7500 100%); box-shadow: 0 4px 14px rgba(213,160,7,0.35); color: #18140c;'
                       : 'color: rgba(245,233,200,0.7);' }}"
                   onmouseover="if (!{{ request()->routeIs('admin.vendors.*') ? 'true' : 'false' }}) { this.style.background='rgba(255,255,255,0.08)'; this.style.color='#ffffff'; }"
                   onmouseout="if (!{{ request()->routeIs('admin.vendors.*') ? 'true' : 'false' }}) { this.style.background='transparent'; this.style.color='rgba(245,233,200,0.7)'; }">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span class="whitespace-nowrap transition-all duration-200" :class="open ? 'opacity-100 w-auto' : 'opacity-0 w-0 overflow-hidden'">
                        Daftar & Approval Vendor
                    </span>
                </a>

                <!-- Divider -->
                <div class="my-3 mx-1 border-t" style="border-color: rgba(255,255,255,0.08);"></div>

                <!-- Tautan Publik -->
                <a href="{{ url('/katalog') }}" target="_blank"
                   class="group relative flex items-center gap-3 rounded-xl text-sm font-medium transition-all duration-150 overflow-hidden"
                   :class="open ? 'px-3.5 py-2.5' : 'px-0 py-2.5 justify-center'"
                   style="color: rgba(245,233,200,0.55);"
                   onmouseover="this.style.background='rgba(255,255,255,0.08)'; this.style.color='#ffffff';"
                   onmouseout="this.style.background='transparent'; this.style.color='rgba(245,233,200,0.55)';">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    <span class="whitespace-nowrap transition-all duration-200" :class="open ? 'opacity-100 w-auto' : 'opacity-0 w-0 overflow-hidden'">
                        Katalog Publik ↗
                    </span>
                </a>
            </nav>

            <!-- Bottom User Profile & Logout -->
            <div class="p-3 border-t overflow-hidden" style="border-color: rgba(255,255,255,0.08); background: rgba(0,0,0,0.2);">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl font-extrabold flex items-center justify-center text-xs shrink-0 text-darkbrown-900 shadow-md"
                         style="background: linear-gradient(135deg, #fbce6b 0%, #d5a007 100%);">
                        SA
                    </div>
                    <div class="flex-1 min-w-0 transition-opacity duration-200" :class="open ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'">
                        <p class="text-xs font-bold leading-tight truncate text-amber-100">{{ auth()->user()->name ?? 'Super Administrator' }}</p>
                        <p class="text-[10px] text-goldenrod-400 uppercase tracking-wider font-semibold">Super Admin</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" :class="open ? 'block' : 'hidden'">
                        @csrf
                        <button type="submit" title="Keluar / Logout"
                                class="p-1.5 rounded-lg text-amber-200/60 hover:text-red-400 hover:bg-white/10 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top App Bar -->
            <header class="sticky top-0 z-20 flex items-center justify-between gap-4 px-4 sm:px-6 py-3 bg-white border-b border-wheat-300 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border border-goldenrod-300 bg-goldenrod-50 text-darkbrown-900">
                        <span class="w-2 h-2 rounded-full bg-goldenrod-600 animate-pulse"></span>
                        <span>Mode Super Administrator</span>
                    </div>
                    <span class="text-xs text-darkbrown-400 hidden md:inline">• Akses Penuh Seluruh Tenant Toko</span>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ url('/') }}"
                       class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-wheat-300 bg-wheat-50 text-darkbrown-700 hover:bg-wheat-100 transition">
                        Beranda Toko
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="inline-block">
                        @csrf
                        <button type="submit" class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 transition">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </header>

            <!-- Global Alert Slot -->
            <div class="px-4 sm:px-6 lg:px-8 pt-4">
                @if (session()->has('success'))
                    <x-alert type="success" :title="'Berhasil!'">
                        {{ session('success') }}
                    </x-alert>
                @endif
                @if (session()->has('error'))
                    <x-alert type="danger" :title="'Perhatian'">
                        {{ session('error') }}
                    </x-alert>
                @endif
            </div>

            <!-- Page Slot -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
