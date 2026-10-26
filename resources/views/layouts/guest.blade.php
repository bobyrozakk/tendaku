<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Masuk' }} - Tendaku</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        @media (min-width: 1024px) {
            .scene-blob {
                -webkit-clip-path: url(#scene-blob);
                clip-path: url(#scene-blob);
            }
        }

        /* Animasi api unggun: tiga lidah api bergoyang tidak serempak, bara naik, cahaya berdenyut */
        .flame { transform-box: fill-box; transform-origin: 50% 100%; animation: flick 1.4s ease-in-out infinite; }
        .flame-l { animation-duration: 1s; animation-delay: -.3s; }
        .flame-r { animation-duration: 1.15s; animation-delay: -.6s; }
        .fire-glow { transform-box: fill-box; transform-origin: 50% 50%; animation: glow 2.2s ease-in-out infinite alternate; }
        .door-glow { animation: door 1.7s ease-in-out infinite alternate; }
        .ember { opacity: 0; animation: ember 2.8s ease-out infinite; }

        @keyframes flick {
            0%   { transform: scale(1, .9)   skewX(-3deg); }
            20%  { transform: scale(.94, 1.08) skewX(2deg); }
            45%  { transform: scale(1.04, .96) skewX(-2deg); }
            70%  { transform: scale(.96, 1.1)  skewX(3deg); }
            100% { transform: scale(1, .92)  skewX(-3deg); }
        }
        @keyframes glow  { from { opacity: .7; transform: scale(.94); } to { opacity: 1; transform: scale(1.06); } }
        @keyframes door  { from { opacity: .2; } to { opacity: .55; } }
        @keyframes ember {
            0%   { transform: translate(0, 0); opacity: 0; }
            12%  { opacity: 1; }
            100% { transform: translate(var(--dx, 10px), -110px); opacity: 0; }
        }

        @media (prefers-reduced-motion: reduce) {
            .flame, .fire-glow, .door-glow { animation: none; }
            .ember { display: none; }
        }
    </style>
</head>
<body class="min-h-screen overflow-x-hidden bg-[#FAF6ED] font-sans text-tendaku-brown antialiased">
    <x-toast />

{{-- bentuk lengkung pemisah panel gambar dan form --}}
<svg width="0" height="0" class="absolute" aria-hidden="true" focusable="false">
    <defs>
        <clipPath id="scene-blob" clipPathUnits="objectBoundingBox">
            <path d="M0 0 H0.9 C0.99 0.16 0.8 0.32 0.86 0.5 C0.92 0.68 1 0.84 0.9 1 H0 Z"/>
        </clipPath>
    </defs>
</svg>

<div class="flex min-h-screen w-full flex-col lg:grid lg:grid-cols-[1.18fr_1fr] xl:grid-cols-[1.25fr_1fr]">

    {{-- Panel gambar: perkemahan tengah malam dengan api unggun --}}
    <aside class="scene-blob relative h-56 sm:h-64 w-full bg-[#0F0E3A] overflow-hidden flex-shrink-0 lg:h-auto lg:min-h-screen">
        @include('partials.camp-scene')

        {{-- Bulan di langit atas --}}
        <div class="pointer-events-none absolute -top-8 sm:-top-10 left-[62%] -translate-x-1/2 z-0" aria-hidden="true">
            <div class="relative flex items-center justify-center">
                {{-- Glow luas --}}
                <div class="absolute h-48 w-48 sm:h-56 sm:w-56 rounded-full bg-[#FFF1CF]/15 blur-2xl"></div>
                {{-- Cincin aura luar --}}
                <div class="absolute h-32 w-32 sm:h-36 sm:w-36 rounded-full bg-[#F9E3B6]/20"></div>
                {{-- Badan bulan --}}
                <div class="relative h-20 w-20 sm:h-24 sm:w-24 rounded-full bg-[#F9E3B6] shadow-[0_0_30px_rgba(249,227,182,0.5)]">
                    {{-- Kawah bulan --}}
                    <div class="absolute top-4 left-3.5 sm:top-5 sm:left-4 h-3 w-3 sm:h-3.5 sm:w-3.5 rounded-full bg-[#E3C88E]/65"></div>
                    <div class="absolute bottom-5 right-4 sm:bottom-6 sm:right-5 h-2 w-2 sm:h-2.5 sm:w-2.5 rounded-full bg-[#E3C88E]/65"></div>
                    <div class="absolute top-8 left-9 sm:top-9 sm:left-11 h-1.5 w-1.5 sm:h-2 sm:w-2 rounded-full bg-[#E3C88E]/55"></div>
                </div>
            </div>
        </div>

        {{-- Logo untuk tampilan mobile --}}
        <div class="relative z-10 flex items-center justify-between p-5 sm:p-6 lg:hidden">
            @include('partials.brand', ['dark' => true])
        </div>

        {{-- Konten panel desktop --}}
        <div class="relative z-10 hidden h-full flex-col p-10 lg:flex xl:p-14">
            @include('partials.brand', ['dark' => true])

            <div class="mt-8 lg:mt-10 xl:mt-12 max-w-lg">
                <h2 class="font-display text-5xl lg:text-6xl xl:text-7xl font-normal leading-[1.05] tracking-tight text-tendaku-wheat">
                    Sewa alat,<br>langsung<br>berangkat.
                </h2>
                <p class="mt-5 text-lg lg:text-xl xl:text-2xl leading-relaxed text-tendaku-wheat/85 max-w-md">
                    Tenda, carrier, kompor, dan perlengkapan lain siap kamu bawa.
                </p>
            </div>
        </div>
    </aside>

    {{-- Panel form --}}
    <main class="relative z-10 -mt-6 flex flex-1 flex-col justify-between rounded-t-[2rem] bg-[#FAF6ED] px-6 pt-8 pb-8 shadow-[-2px_-8px_24px_rgba(15,14,58,0.12)] sm:px-10 lg:mt-0 lg:rounded-none lg:shadow-none lg:min-h-screen lg:px-12 xl:px-20 lg:py-12 lg:overflow-y-auto">
        <div class="mx-auto w-full max-w-sm my-auto py-2">
            @isset($heading)
                <h1 class="font-display text-4xl sm:text-5xl font-normal text-tendaku-brown">
                    {{ $heading }}
                </h1>
            @endisset

            @isset($subheading)
                <p class="mt-3 text-base sm:text-lg leading-relaxed text-tendaku-brown/70">{{ $subheading }}</p>
            @endisset

            <div class="{{ (isset($heading) || isset($subheading)) ? 'mt-6 sm:mt-8' : '' }}">
                {{ $slot }}
            </div>
        </div>

        <p class="mt-8 text-center text-xs text-tendaku-brown/60 lg:mt-6 lg:text-left">
            &copy; {{ date('Y') }} Tendaku
        </p>
    </main>
</div>

</body>
</html>
