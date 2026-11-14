<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Super Admin Login' }} - Tendaku Platform</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }

        @media (prefers-reduced-motion: reduce) {
            *, ::before, ::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body class="min-h-full overflow-x-hidden bg-[#FAF6ED] font-sans text-tendaku-brown antialiased selection:bg-tendaku-avocado selection:text-white">
    <x-toast />

    <div class="flex min-h-screen w-full flex-col lg:grid lg:grid-cols-[1.1fr_1fr] xl:grid-cols-[1.2fr_1fr]">

        {{-- Left Panel: Sharp, Unblurred Mountain Camping Scenery Background --}}
        <aside class="relative flex min-h-[340px] sm:min-h-[400px] w-full flex-col justify-between overflow-hidden p-6 text-white sm:p-10 lg:min-h-screen lg:p-14">
            {{-- Background Photo: Crystal Clear, Unblurred --}}
            <div class="absolute inset-0 z-0">
                <img
                    src="{{ asset('images/superadmin-bg.jpg') }}"
                    alt="Tendaku Camping Mountains"
                    class="h-full w-full object-cover object-center"
                >
                {{-- Clean natural gradient overlay for contrast while keeping scenery clearly visible --}}
                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/35 to-black/50"></div>
            </div>

            {{-- Header: Clean Standalone Logo & Live Realtime WIB Clock Pill --}}
            <div class="relative z-10 flex items-center justify-between gap-4">
                <a href="{{ route('home') }}"
                   class="inline-flex items-center rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-tendaku-avocado transition-transform hover:scale-105"
                   aria-label="Beranda Tendaku">
                    <img src="{{ asset('images/Logo.svg') }}" alt="Tendaku" class="h-11 sm:h-12 w-auto brightness-0 invert drop-shadow-lg">
                </a>

                {{-- Status Pill with Realtime WIB Clock --}}
                <div class="inline-flex items-center gap-2.5 rounded-full border border-white/20 bg-black/45 px-3.5 py-1.5 shadow-lg backdrop-blur-md">
                    <span class="relative flex h-2 w-2" aria-hidden="true">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                    <span class="text-xs font-semibold text-emerald-300">Cluster Aktif</span>
                    <span class="text-white/30">&bull;</span>
                    <span id="js-live-clock-pill" class="font-mono text-xs font-semibold text-tendaku-wheat">--:--:-- WIB</span>
                </div>
            </div>

            {{-- Main Hero Text in Left Panel --}}
            <div class="relative z-10 my-auto hidden max-w-lg py-8 lg:block">
                <h1 class="font-display text-4xl sm:text-5xl lg:text-6xl font-normal leading-[1.08] tracking-tight text-white drop-shadow-md">
                    Pusat Kendali <br>
                    <span class="text-tendaku-wheat">
                        Platform Tendaku
                    </span>
                </h1>

                <p class="mt-4 text-base leading-relaxed text-white/90 drop-shadow max-w-md">
                    Portal pengawasan sistem, otorisasi transaksi, dan tata kelola operasional seluruh ekosistem sewa Tendaku.
                </p>
            </div>

            {{-- Footer Info on Left Panel --}}
            <div class="relative z-10 hidden items-center justify-end border-t border-white/15 pt-4 text-xs text-white/70 lg:flex">
                <span class="flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5 text-tendaku-avocado" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Terenkripsi TLS 1.3 &bull; AES-256
                </span>
            </div>
        </aside>

        {{-- Right Panel: Form Area (Clean, Minimalist Cream) --}}
        <main class="relative z-10 -mt-6 flex flex-1 flex-col justify-between rounded-t-[2.5rem] bg-[#FAF6ED] px-6 pt-8 pb-10 shadow-[-2px_-8px_24px_rgba(10,18,10,0.15)] sm:px-10 lg:mt-0 lg:rounded-none lg:shadow-none lg:min-h-screen lg:px-14 xl:px-20 lg:py-12 lg:overflow-y-auto">
            <div class="mx-auto w-full max-w-md my-auto py-4">
                {{ $slot }}
            </div>

            <footer class="mt-8 text-center text-xs text-tendaku-brown/70 lg:mt-6 lg:text-left">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-2">
                    <p>&copy; {{ date('Y') }} Tendaku &bull; Hak Akses Khusus Administrator</p>
                    <a href="{{ route('login') }}" class="font-medium text-tendaku-moss underline-offset-4 hover:underline">
                        Bukan super admin? Masuk portal umum
                    </a>
                </div>
            </footer>
        </main>
    </div>

    {{-- Interactive Vanilla JavaScript Engine for Realtime Clock --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const clockPillEl = document.getElementById('js-live-clock-pill');

            function updateClock() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('id-ID', { hour12: false }) + ' WIB';
                if (clockPillEl) clockPillEl.textContent = timeString;
            }
            updateClock();
            setInterval(updateClock, 1000);
        });
    </script>
</body>
</html>
