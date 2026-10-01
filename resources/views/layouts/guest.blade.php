<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Tendaku') }} - Akses Akun</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-darkbrown-800 antialiased bg-camping-pattern">
        <div class="min-h-screen flex flex-col sm:justify-center items-center p-6 bg-gradient-to-b from-[#FDFBF7] via-[#FAF6ED] to-wheat-100/60">
            <div class="mb-6 flex flex-col items-center">
                <a href="/" wire:navigate class="transition-transform duration-200 hover:scale-105">
                    <x-application-logo size="xl" />
                </a>
            </div>

            <div class="w-full sm:max-w-md bg-white border border-wheat-200/90 shadow-tendaku-lg rounded-2xl px-8 py-8 relative overflow-hidden">
                <!-- Warm top accent strip (Avocado + Goldenrod + Sunglow) -->
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-avocado-500 via-sunglow-300 to-goldenrod-500"></div>

                {{ $slot }}
            </div>

            <div class="mt-8 text-center text-xs text-darkbrown-500">
                <a href="{{ url('/') }}" class="hover:text-avocado-600 transition font-medium">← Kembali ke Halaman Utama Tendaku</a>
            </div>
        </div>
    </body>
</html>
