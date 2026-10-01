<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Tendaku') . ' - Rental Alat Camping & Outdoor' }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full flex flex-col font-sans antialiased bg-[#FDFBF7] text-darkbrown-800 selection:bg-sunglow-300 selection:text-darkbrown-900">
    <!-- Navbar Component -->
    <x-navbar role="customer" />

    <!-- Optional Page Header Banner -->
    @if (isset($header))
        <header class="bg-white border-b border-wheat-200/80 shadow-[0_1px_3px_rgba(43,34,2,0.03)]">
            <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
    @endif

    <!-- Global Flash Notification Messages -->
    <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 pt-4">
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
        @if (session()->has('warning'))
            <x-alert type="warning" :title="'Info Penting'">
                {{ session('warning') }}
            </x-alert>
        @endif
    </div>

    <!-- Main Content Slot -->
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8">
        {{ $slot }}
    </main>

    <!-- Tendaku Brand Footer in Drab Dark Brown (#2B2202) -->
    <footer class="bg-darkbrown-800 text-wheat-200 border-t border-darkbrown-700 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Brand Info -->
                <div class="space-y-4 md:col-span-2">
                    <div class="flex items-center gap-3">
                        <x-application-logo size="lg" :withText="false" />
                        <div>
                            <span class="text-xl font-extrabold text-white tracking-tight">TENDA<span class="text-avocado-400">KU</span></span>
                            <p class="text-xs text-sunglow-300 font-semibold tracking-wide">Solusi Lengkap Sewa Perlengkapan Camping</p>
                        </div>
                    </div>
                    <p class="text-sm text-wheat-400 max-w-md leading-relaxed">
                        Sewa tenda, sleeping bag, carrier, kompor portabel, dan seluruh perlengkapan outdoor berkualitas prima dari vendor terdekat. Praktis, terawat, dan siap menemani petualangan alammu!
                    </p>
                    <div class="flex items-center gap-2 pt-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-avocado-500/20 text-avocado-300 border border-avocado-500/30">
                            ✓ Peralatan Higienis & Terverifikasi
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-goldenrod-500/20 text-sunglow-300 border border-goldenrod-500/30">
                            ✓ POS Kasir Toko
                        </span>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div>
                    <h4 class="text-sm font-bold uppercase tracking-wider text-wheat-200 mb-4">Navigasi Cepat</h4>
                    <ul class="space-y-2.5 text-sm text-wheat-400">
                        <li><a href="{{ url('/') }}" class="hover:text-sunglow-300 transition">Beranda</a></li>
                        <li><a href="{{ url('/katalog') }}" class="hover:text-sunglow-300 transition">Katalog Tenda & Matras</a></li>
                        <li><a href="{{ url('/design-system') }}" class="hover:text-sunglow-300 transition font-semibold text-sunglow-300">Design System Guide</a></li>
                        <li><a href="{{ route('dashboard') }}" class="hover:text-sunglow-300 transition">Portal Vendor & POS</a></li>
                    </ul>
                </div>

                <!-- Contact & Support -->
                <div>
                    <h4 class="text-sm font-bold uppercase tracking-wider text-wheat-200 mb-4">Pusat Bantuan</h4>
                    <p class="text-sm text-wheat-400 leading-relaxed mb-3">
                        Ada pertanyaan seputar penyewaan, deposit unit, atau ingin gabung jadi vendor rental?
                    </p>
                    <div class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-darkbrown-900 border border-darkbrown-700 text-xs text-sunglow-300 font-semibold">
                        <span>💬 CS Tendaku: 0812-3456-7890</span>
                    </div>
                </div>
            </div>

            <div class="border-t border-darkbrown-700/80 mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-wheat-500">
                <p>&copy; {{ date('Y') }} Tendaku Outdoor Equipment Rental. Seluruh Hak Cipta Dilindungi.</p>
                <div class="flex items-center gap-4">
                    <span class="text-wheat-600">Palette: Wheat • Sunglow • Goldenrod • Avocado • Dark Brown</span>
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
