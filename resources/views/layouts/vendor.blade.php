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
<body class="min-h-full font-sans antialiased text-darkbrown-800" style="background-color: #f2ede3;">
    <div class="min-h-screen flex">
        <!-- Vendor Shared Sidebar -->
        <x-navbar role="vendor" />

        <!-- Content area (automatically resizes alongside sidebar) -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top App Bar — matches dark sidebar tone -->
            <div class="sticky top-0 z-20 flex items-center justify-between gap-4 px-4 sm:px-6 py-3"
                 style="background: #ffffff; border-bottom: 1px solid #e8e0cc;">

                <!-- Left: Store Status -->
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border"
                          style="background: rgba(108,139,8,0.08); color: #4e6506; border-color: rgba(108,139,8,0.25);">
                        <span class="w-2 h-2 rounded-full animate-pulse" style="background:#6c8b08;"></span>
                        Toko Rental Buka
                    </span>
                    <span class="text-xs hidden sm:inline" style="color: #b8a882;">• Tendaku Central Camp</span>
                </div>

                <!-- Right: Actions -->
                <div class="flex items-center gap-2">

                    <!-- Notification Bell -->
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

                    <!-- View Website Link -->
                    <a href="{{ url('/') }}"
                       class="hidden md:inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg transition-all duration-150"
                       style="background: #f0e8d4; color: #4a3520; border: 1px solid #ddd0b0;"
                       onmouseover="this.style.background='#e6dcc5';"
                       onmouseout="this.style.background='#f0e8d4';">
                        <span>Lihat Website</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </a>

                    <!-- Divider -->
                    <div class="w-px h-6 mx-1" style="background: #e8e0cc;"></div>

                    <!-- User Pill -->
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl font-bold flex items-center justify-center text-xs text-white"
                             style="background: linear-gradient(135deg, #6c8b08 0%, #4e6506 100%); box-shadow: 0 2px 8px rgba(108,139,8,0.3);">
                            TK
                        </div>
                        <div class="hidden sm:block">
                            <p class="text-xs font-bold leading-none" style="color: #2b2202;">Admin Vendor</p>
                            <p class="text-[10px] mt-0.5" style="color: #9a8060;">Tendaku Portal</p>
                        </div>
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

            <!-- Main Content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
