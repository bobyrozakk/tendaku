<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard Super Admin - Tendaku' }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full font-sans antialiased bg-wheat-50 text-darkbrown-800">
    @php
        $initials = collect(explode(' ', auth()->user()->name ?? 'Super Admin'))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_substr($part, 0, 1))
            ->implode('');
    @endphp

    <div class="flex min-h-screen flex-col">
        <header class="sticky top-0 z-30 border-b border-wheat-200 bg-white/95 shadow-tendaku-sm backdrop-blur">
            <div class="mx-auto flex min-h-[4.25rem] max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-2.5 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <a href="{{ route('dashboard') }}" class="shrink-0">
                        <x-application-logo size="md" />
                    </a>
                    <span class="hidden items-center gap-1.5 rounded-full border border-darkbrown-700 bg-darkbrown-800 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-wheat-200 sm:inline-flex">
                        <span class="h-1.5 w-1.5 rounded-full bg-sunglow-300"></span>
                        Super Admin
                    </span>
                </div>

                <!-- Admin Navigation Menu -->
                <nav class="hidden md:flex items-center gap-1 rounded-2xl border border-wheat-200 bg-wheat-50/80 p-1">
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-2 rounded-xl px-3.5 py-1.5 text-xs transition {{ request()->routeIs('dashboard') ? 'bg-white font-bold text-darkbrown-900 shadow-sm border border-wheat-300/70' : 'font-medium text-darkbrown-600 hover:bg-white/60 hover:text-darkbrown-900' }}">
                        <svg class="h-3.5 w-3.5 text-avocado-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        Dashboard
                    </a>
                    <a href="{{ route('admin.pendapatan') }}"
                       class="inline-flex items-center gap-2 rounded-xl px-3.5 py-1.5 text-xs transition {{ request()->routeIs('admin.pendapatan*') ? 'bg-white font-bold text-darkbrown-900 shadow-sm border border-wheat-300/70' : 'font-medium text-darkbrown-600 hover:bg-white/60 hover:text-darkbrown-900' }}">
                        <svg class="h-3.5 w-3.5 text-goldenrod-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Pendapatan
                    </a>
                    <a href="{{ route('admin.settlements') }}"
                       class="inline-flex items-center gap-2 rounded-xl px-3.5 py-1.5 text-xs transition {{ request()->routeIs('admin.settlements*') ? 'bg-white font-bold text-darkbrown-900 shadow-sm border border-wheat-300/70' : 'font-medium text-darkbrown-600 hover:bg-white/60 hover:text-darkbrown-900' }}">
                        <svg class="h-3.5 w-3.5 text-avocado-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Settlement Vendor
                    </a>
                </nav>

                <div class="flex items-center gap-2">
                    <div class="hidden text-right lg:block">
                        <p class="text-xs font-bold leading-none text-darkbrown-800">{{ auth()->user()->name ?? 'Super Administrator' }}</p>
                        <p class="mt-1 text-[10px] font-medium text-darkbrown-400">Super Administrator</p>
                    </div>

                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-avocado text-xs font-extrabold text-white shadow-sm">
                        {{ strtoupper($initials) }}
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="inline-block">
                        @csrf
                        <button type="submit" title="Keluar / Logout"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-wheat-300 bg-wheat-50 px-3 py-2 text-xs font-semibold text-darkbrown-700 transition hover:border-red-200 hover:bg-red-50 hover:text-red-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span class="hidden sm:inline">Keluar</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Mobile sub-navigation bar -->
            <div class="border-t border-wheat-200 bg-wheat-50/70 px-4 py-2 md:hidden">
                <nav class="flex items-center gap-1 overflow-x-auto">
                    <a href="{{ route('dashboard') }}"
                       class="shrink-0 rounded-xl px-3 py-1 text-xs transition {{ request()->routeIs('dashboard') ? 'bg-white font-bold text-darkbrown-900 border border-wheat-300/80 shadow-sm' : 'font-medium text-darkbrown-600' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('admin.pendapatan') }}"
                       class="shrink-0 rounded-xl px-3 py-1 text-xs transition {{ request()->routeIs('admin.pendapatan*') ? 'bg-white font-bold text-darkbrown-900 border border-wheat-300/80 shadow-sm' : 'font-medium text-darkbrown-600' }}">
                        Pendapatan
                    </a>
                    <a href="{{ route('admin.settlements') }}"
                       class="shrink-0 rounded-xl px-3 py-1 text-xs transition {{ request()->routeIs('admin.settlements*') ? 'bg-white font-bold text-darkbrown-900 border border-wheat-300/80 shadow-sm' : 'font-medium text-darkbrown-600' }}">
                        Settlement Vendor
                    </a>
                </nav>
            </div>
        </header>

        <x-toast />

        <main class="mx-auto w-full max-w-7xl flex-1 p-4 sm:p-6 lg:p-8">
            {{ $slot }}
        </main>

        <footer class="border-t border-wheat-200 bg-white/60 px-4 py-4 text-center text-[11px] text-darkbrown-400">
            Tendaku Super Admin Console &middot; {{ now()->isoFormat('MMMM Y') }}
        </footer>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
