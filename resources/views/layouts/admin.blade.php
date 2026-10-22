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

                <div class="flex items-center gap-2">
                    <div class="hidden text-right md:block">
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
        </header>

        <div class="mx-auto w-full max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
            @if (session()->has('success'))
                <x-alert type="success" :title="'Berhasil!'">
                    {{ session('success') }}
                </x-alert>
            @endif

            @if (session()->has('error'))
                <x-alert type="danger" :title="'Perhatian'" class="mt-3">
                    {{ session('error') }}
                </x-alert>
            @endif

            @if (session()->has('warning'))
                <x-alert type="warning" :title="'Info Penting'" class="mt-3">
                    {{ session('warning') }}
                </x-alert>
            @endif
        </div>

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
