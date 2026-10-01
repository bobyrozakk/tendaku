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
<body class="min-h-full font-sans antialiased bg-[#FAF7F0] text-darkbrown-800">
    <div class="min-h-screen flex flex-col sm:flex-row">
        <!-- Vendor Shared Sidebar (Dark Brown + Avocado/Sunglow highlights) -->
        <x-navbar role="vendor" />

        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top App Bar for Vendor -->
            <div class="bg-white border-b border-wheat-200/80 px-4 sm:px-8 py-3.5 flex items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-avocado-100 text-avocado-800 border border-avocado-200">
                        <span class="w-2 h-2 rounded-full bg-avocado-500 animate-pulse"></span>
                        Toko Rental Buka
                    </span>
                    <span class="text-xs text-darkbrown-400 hidden sm:inline">• Tendaku Central Camp</span>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Notification Bell -->
                    <button class="relative p-2 text-darkbrown-600 hover:text-darkbrown-900 hover:bg-wheat-100 rounded-xl transition" title="Notifikasi Baru">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-goldenrod-500"></span>
                    </button>

                    <!-- Quick Customer View Switcher -->
                    <a href="{{ url('/') }}" class="hidden md:inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg bg-wheat-100 text-darkbrown-800 hover:bg-wheat-200 border border-wheat-200 transition">
                        <span>Lihat Website</span>
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </a>

                    <!-- User Pill -->
                    <div class="flex items-center gap-2 pl-2 border-l border-wheat-200">
                        <div class="w-8 h-8 rounded-xl bg-sunglow-200 text-darkbrown-900 font-bold flex items-center justify-center text-xs border border-sunglow-300">
                            TK
                        </div>
                        <span class="text-xs font-bold text-darkbrown-800 hidden sm:inline">Admin Vendor</span>
                    </div>
                </div>
            </div>

            <!-- Page Header -->
            @if (isset($header))
                <header class="bg-white border-b border-wheat-200/60 shadow-[0_1px_2px_rgba(43,34,2,0.02)]">
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
