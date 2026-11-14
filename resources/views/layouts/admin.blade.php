<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard Super Admin - Tendaku' }}</title>

    <!-- Fonts sudah dimuat melalui @import di resources/css/app.css -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        @media (prefers-reduced-motion: reduce) {
            .admin-shell *,
            .admin-shell *::before,
            .admin-shell *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }

        @media print {
            .admin-shell .admin-print-hide {
                display: none !important;
            }

            .admin-shell {
                background: #fff !important;
            }

            .admin-shell main {
                max-width: none !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body class="min-h-full font-sans antialiased bg-wheat-50 text-darkbrown-800">
    @php
        $initials = collect(explode(' ', auth()->user()->name ?? 'Super Admin'))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_substr($part, 0, 1))
            ->implode('');

        $navigation = [
            [
                'label' => 'Dashboard',
                'mobile_label' => 'Dashboard',
                'route' => route('dashboard'),
                'active' => request()->routeIs('dashboard'),
                'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
            ],
            [
                'label' => 'Pendapatan',
                'mobile_label' => 'Pendapatan',
                'route' => route('admin.pendapatan'),
                'active' => request()->routeIs('admin.pendapatan*'),
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            ],
            [
                'label' => 'Settlement',
                'mobile_label' => 'Settlement',
                'route' => route('admin.settlements'),
                'active' => request()->routeIs('admin.settlements*'),
                'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
            ],
        ];
    @endphp

    <a href="#admin-main"
       class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-xl focus:bg-darkbrown-900 focus:px-4 focus:py-2.5 focus:text-sm focus:font-bold focus:text-wheat-100 focus:ring-2 focus:ring-sunglow-300">
        Lompat ke konten utama
    </a>

    <div class="admin-shell flex min-h-screen flex-col">
        <div class="admin-print-hide h-1 bg-gradient-to-r from-darkbrown-900 via-avocado-500 to-goldenrod-500" aria-hidden="true"></div>

        <header class="admin-print-hide sticky top-0 z-30 border-b border-wheat-200/80 bg-wheat-50/90 backdrop-blur-xl">
            <div class="mx-auto flex min-h-[4rem] w-full max-w-screen-2xl items-center justify-between gap-3 px-4 py-2 sm:px-6 lg:px-8">
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-2.5 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2 focus-visible:ring-offset-wheat-50 transition-opacity hover:opacity-90"
                   aria-label="Dashboard Super Admin">
                    <x-application-logo size="md" />
                    <span class="rounded-full bg-darkbrown-800 px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-widest text-sunglow-300 shadow-sm">
                        Admin
                    </span>
                </a>

                <!-- Navigasi utama admin -->
                <nav class="hidden items-center gap-1 rounded-2xl border border-wheat-200 bg-white/70 p-1 shadow-tendaku-sm md:flex"
                     aria-label="Navigasi utama super admin">
                    @foreach ($navigation as $item)
                        <a href="{{ $item['route'] }}"
                           @if ($item['active']) aria-current="page" @endif
                           class="inline-flex items-center gap-2 rounded-xl px-3.5 py-1.5 text-xs transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-wheat-100 {{ $item['active'] ? 'bg-darkbrown-800 font-bold text-wheat-100 shadow-sm focus-visible:ring-sunglow-300' : 'font-semibold text-darkbrown-600 hover:bg-wheat-100 hover:text-darkbrown-900 focus-visible:ring-avocado-500' }}">
                            <svg class="h-3.5 w-3.5 {{ $item['active'] ? 'text-sunglow-300' : 'text-avocado-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}" />
                            </svg>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-2 sm:gap-2.5">
                    <span class="hidden items-center gap-1.5 rounded-full border border-avocado-200 bg-avocado-50 px-2.5 py-1 text-[10px] font-bold text-avocado-800 lg:inline-flex"
                          title="Sistem aktif">
                        <span class="relative flex h-1.5 w-1.5" aria-hidden="true">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-avocado-400 opacity-60"></span>
                            <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-avocado-500"></span>
                        </span>
                        Live
                    </span>

                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-avocado text-xs font-extrabold text-white shadow-sm ring-1 ring-inset ring-darkbrown-900/10"
                         title="{{ auth()->user()->name ?? 'Super Administrator' }}"
                         aria-hidden="true">
                        {{ strtoupper($initials) }}
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="inline-block">
                        @csrf
                        <button type="submit" title="Keluar dari akun" aria-label="Keluar dari akun"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-wheat-300 bg-white text-darkbrown-600 transition hover:border-red-200 hover:bg-red-50 hover:text-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2 focus-visible:ring-offset-wheat-50">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Navigasi mobile -->
            <div class="border-t border-wheat-200/80 bg-white/50 px-4 py-2 md:hidden">
                <nav class="flex items-center gap-1 overflow-x-auto scrollbar-none" aria-label="Navigasi super admin mobile">
                    @foreach ($navigation as $item)
                        <a href="{{ $item['route'] }}"
                           @if ($item['active']) aria-current="page" @endif
                           class="inline-flex shrink-0 items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs transition focus:outline-none focus-visible:ring-2 focus-visible:ring-avocado-500 focus-visible:ring-offset-2 focus-visible:ring-offset-wheat-50 {{ $item['active'] ? 'bg-darkbrown-800 font-bold text-wheat-100' : 'font-semibold text-darkbrown-600 hover:bg-wheat-100' }}">
                            <svg class="h-3.5 w-3.5 {{ $item['active'] ? 'text-sunglow-300' : 'text-avocado-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}" />
                            </svg>
                            <span class="sm:hidden">{{ $item['mobile_label'] }}</span>
                            <span class="hidden sm:inline">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>
        </header>

        <x-toast class="!top-auto !bottom-5" />

        <main id="admin-main" tabindex="-1" class="mx-auto w-full max-w-screen-2xl flex-1 px-4 py-5 outline-none sm:px-6 lg:px-8 lg:py-6">
            {{ $slot }}
        </main>

        <footer class="admin-print-hide border-t border-wheat-200 bg-white/60 px-4 py-3">
            <div class="mx-auto flex w-full max-w-screen-2xl items-center justify-between gap-2 text-[11px] text-darkbrown-500">
                <p>Tendaku Admin</p>
                <p class="inline-flex items-center gap-1.5">
                    <span class="h-1.5 w-1.5 rounded-full bg-avocado-500" aria-hidden="true"></span>
                    Online
                </p>
            </div>
        </footer>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
