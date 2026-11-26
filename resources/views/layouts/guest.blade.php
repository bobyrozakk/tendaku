<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Masuk' }} - Tendaku</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/css/pages/auth/fog-glass.css',
        'resources/js/pages/auth/fog-glass.js',
    ])

    <style>
        [x-cloak] {
            display: none !important;
        }

        @media (min-width: 1024px) {
            .scene-blob {
                -webkit-clip-path: url(#scene-blob);
                clip-path: url(#scene-blob);
            }
        }

        /* Form auth lebih ringkas di desktop. Hanya berlaku di dalam .auth-compact */
        @media (min-width: 1024px) {
            .auth-compact input:not([type="checkbox"]) {
                padding-top: .5rem;
                padding-bottom: .5rem;
                font-size: .9375rem;
            }

            .auth-compact button[type="submit"] {
                padding-top: .65rem;
                padding-bottom: .65rem;
                font-size: 1rem;
            }

            .auth-compact .space-y-5 > *:not([hidden]) ~ *:not([hidden]) {
                margin-top: 1rem;
            }
        }
    </style>
</head>

<body class="min-h-screen overflow-x-hidden bg-[#0F0E3A] font-sans text-tendaku-brown antialiased">
    <x-toast />

    <x-desktop-mode-notice />

    {{-- Bentuk lengkung pemisah panel gambar dan form --}}
    <svg width="0"
        height="0"
        class="absolute"
        aria-hidden="true"
        focusable="false" >
        <defs>
            <clipPath id="scene-blob" clipPathUnits="objectBoundingBox">
                <path d="M0 0 H0.94 C0.99 0.16 0.8 0.32 0.86 0.5 C0.92 0.68 1 0.84 0.9 1 H0 Z" />
            </clipPath>
            <clipPath id="auth-fog-standard" clipPathUnits="objectBoundingBox">
                <path d="M0.564 0 C0.594 0.16 0.48 0.32 0.516 0.5 C0.552 0.68 0.6 0.84 0.54 1 H1 V0 Z" />
            </clipPath>
            <clipPath id="auth-fog-register" clipPathUnits="objectBoundingBox">
                <path d="M0.47 0 C0.495 0.16 0.4 0.32 0.43 0.5 C0.46 0.68 0.5 0.84 0.45 1 H1 V0 Z" />
            </clipPath>
        </defs>
    </svg>

    {{-- Foto latar --}}
    @persist('auth-bg')
        <div class="fixed inset-0 z-0" aria-hidden="true">
            <img
                src="{{ asset('images/login-page.jpg') }}"
                alt=""
                decoding="async"
                class="h-full w-full object-cover object-center"
            >
        </div>
    @endpersist

    @php($wide = request()->routeIs('register'))

    {{-- Embun di atas foto latar (desktop). Kursor menghapus embun, lalu menutup lagi. --}}
    <canvas
        class="auth-fog fixed inset-0 z-[5] h-full w-full {{ $wide ? 'auth-fog--register' : 'auth-fog--standard' }}"
        data-auth-fog
        data-src="{{ asset('images/login-page.jpg') }}"
        aria-hidden="true"
    ></canvas>

    <div
        class="relative z-10 flex min-h-screen w-full flex-col lg:grid {{ $wide ? 'lg:grid-cols-2' : 'lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]' }}"
    >
        {{-- Layer kaca cadangan. Disembunyikan saat canvas embun aktif. --}}
        <div
            class="auth-fog-fallback pointer-events-none absolute inset-0 bg-[#0F0E3A]/55 backdrop-blur-xl backdrop-saturate-150 {{ $wide ? 'auth-fog-fallback--register' : 'auth-fog-fallback--standard' }}"
            aria-hidden="true"
        ></div>

        {{-- Panel kiri --}}
        <div class="relative h-56 sm:h-64 w-full flex-shrink-0 lg:h-auto lg:min-h-screen">
            @persist('auth-aside')
                <aside class="scene-blob absolute inset-0 overflow-hidden">
                    <img
                        src="{{ asset('images/login-page.jpg') }}"
                        alt=""
                        decoding="async"
                        class="absolute inset-0 h-full w-full object-cover object-center lg:hidden"
                    >

                    <div
                        class="absolute inset-0 bg-gradient-to-t from-black/30 via-black/10 to-black/20"
                        aria-hidden="true"
                    ></div>
                </aside>
            @endpersist

            {{-- Logo untuk tampilan mobile --}}
            <div class="relative z-10 flex items-center justify-between p-5 sm:p-6 lg:hidden">
                <a
                    href="{{ url('/') }}"
                    class="inline-flex items-center rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-tendaku-wheat"
                    aria-label="Beranda Tendaku"
                >
                    <img
                        src="{{ asset('images/Logo.svg') }}"
                        alt="Tendaku"
                        class="h-10 sm:h-11 w-auto drop-shadow-lg"
                    >
                </a>
            </div>

            {{-- Konten panel desktop. mode: login | customer | vendor (register mengirim event account-type-changed) --}}
            <div
                x-data="{ mode: '{{ $wide ? 'customer' : 'login' }}' }"
                x-on:account-type-changed.window="mode = $event.detail.type"
                class="relative z-10 hidden flex-col p-10 lg:flex xl:p-14"
            >
                <a
                    href="{{ url('/') }}"
                    class="inline-flex w-fit self-start items-center rounded-xl
                           focus:outline-none focus-visible:ring-2 focus-visible:ring-tendaku-wheat"
                    aria-label="Beranda Tendaku"
                >
                    <img
                        src="{{ asset('images/Logo.svg') }}"
                        alt="Tendaku"
                        class="h-10 sm:h-11 w-auto drop-shadow-lg"
                    >
                </a>

                <div class="mt-8 lg:mt-10 xl:mt-12 max-w-lg">
                    {{-- Login & halaman auth lain --}}
                    <div
                        x-show="mode === 'login'"
                        @if($wide)
                            style="display: none"
                        @endif
                    >
                        <h2 class="font-display text-5xl lg:text-6xl xl:text-7xl font-normal leading-[1.05] tracking-tight text-tendaku-wheat drop-shadow-md">
                            Sewa alat,<br>langsung<br>berangkat.
                        </h2>

                        <p class="mt-5 text-lg lg:text-xl xl:text-2xl leading-relaxed text-tendaku-wheat/90 max-w-md drop-shadow">
                            Tenda, carrier, kompor, dan perlengkapan lain siap kamu bawa.
                        </p>
                    </div>

                    {{-- Register: User --}}
                    <div
                        x-show="mode === 'customer'"
                        @unless($wide)
                            style="display: none"
                        @endunless
                    >
                        <h2 class="font-display text-5xl lg:text-6xl xl:text-7xl font-normal leading-[1.05] tracking-tight text-tendaku-wheat drop-shadow-md">
                            Mulai petualanganmu.
                        </h2>

                        <p class="mt-5 text-lg lg:text-xl xl:text-2xl leading-relaxed text-tendaku-wheat/90 max-w-md drop-shadow">
                            Daftar sekarang, pilih alatnya, dan siap berangkat.
                        </p>
                    </div>

                    {{-- Register: Vendor --}}
                    <div
                        x-show="mode === 'vendor'"
                        style="display: none"
                    >
                        <h2 class="font-display text-5xl lg:text-6xl xl:text-7xl font-normal leading-[1.05] tracking-tight text-tendaku-wheat drop-shadow-md">
                            Kembangkan bisnis sewamu.
                        </h2>

                        <p class="mt-5 text-lg lg:text-xl xl:text-2xl leading-relaxed text-tendaku-wheat/90 max-w-md drop-shadow">
                            Gabung bersama Tendaku dan jangkau lebih banyak pelanggan.
                        </p>
                    </div>
                </div>
            </div>

            <p class="absolute bottom-8 left-6 z-10 text-xs text-white/90 drop-shadow sm:left-10 lg:bottom-10 lg:left-10 xl:left-14">
                &copy; {{ date('Y') }} Tendaku
            </p>
        </div>

        {{-- Panel form --}}
        <main
            class="relative z-10 -mt-6 flex flex-1 flex-col justify-between rounded-t-[2rem] border-t border-white/20 bg-gradient-to-br from-white/30 via-white/22 to-white/16 px-6 pt-8 pb-8 shadow-[inset_0_1px_0_rgba(255,255,255,0.7),-2px_-8px_24px_rgba(15,14,58,0.18)] backdrop-blur-md backdrop-saturate-150 sm:px-10 lg:mt-0 lg:rounded-none lg:border-0 lg:bg-none lg:shadow-none lg:backdrop-blur-none lg:backdrop-saturate-100 {{ $wide ? 'lg:h-screen' : 'lg:min-h-screen' }} lg:px-10 xl:px-14 lg:py-8 lg:overflow-y-auto"
        >
            <div class="auth-compact mx-auto w-full {{ $wide ? 'max-w-sm xl:max-w-xl' : 'max-w-sm' }} my-auto py-2">
                @isset($heading)
                    <h1 class="font-display text-3xl sm:text-4xl font-normal text-white">
                        {{ $heading }}
                    </h1>
                @endisset

                @isset($subheading)
                    <p class="mt-2 text-sm sm:text-base leading-relaxed text-white/80">
                        {{ $subheading }}
                    </p>
                @endisset

                <div class="{{ (isset($heading) || isset($subheading)) ? 'mt-6 lg:mt-5' : '' }}">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>
</body>
</html>
