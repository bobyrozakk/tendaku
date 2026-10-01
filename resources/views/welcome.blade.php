<x-layouts.customer>
    <x-slot name="title">
        Tendaku - Solusi Rental Alat Camping & Petualangan Outdoor
    </x-slot>

    <!-- HERO SECTION -->
    <section class="relative rounded-3xl overflow-hidden bg-gradient-to-br from-darkbrown-800 via-darkbrown-900 to-[#1A1401] text-wheat-100 p-8 sm:p-12 lg:p-16 border border-darkbrown-700 shadow-tendaku-lg my-4">
        <!-- Background Ambient Elements -->
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-avocado-500/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-goldenrod-500/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 max-w-3xl space-y-6">
            <!-- Brand Tagline Badge -->
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-wheat-200/10 border border-wheat-200/20 backdrop-blur-sm">
                <span class="w-2 h-2 rounded-full bg-sunglow-400 animate-pulse"></span>
                <span class="text-xs font-bold uppercase tracking-wider text-sunglow-300">Platform Persewaan Camping #1</span>
            </div>

            <!-- Main Title -->
            <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold text-white tracking-tight leading-[1.15]">
                Petualangan Seru Dimulai dari <span class="text-sunglow-300 underline decoration-avocado-500 underline-offset-8">Sini</span>.
            </h1>

            <p class="text-base sm:text-lg text-wheat-300 max-w-2xl font-normal leading-relaxed">
                Sewa tenda dome berkualitas, sleeping bag hangat, kompor portabel, dan seluruh perlengkapan camping tanpa ribet. Bersih, terawat, dan siap untuk ekspedisi alam terbaik Anda.
            </p>

            <!-- Quick Action Buttons -->
            <div class="flex flex-wrap items-center gap-4 pt-2">
                <a href="{{ url('/design-system') }}" class="btn-tendaku-accent !py-3.5 !px-6 text-sm font-bold shadow-tendaku-glow">
                    <span>🎨 Lihat Template Desain</span>
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>

                <a href="#katalog-populer" class="btn-tendaku-outline !text-wheat-200 !border-wheat-400/30 hover:!bg-white/10 !py-3.5 !px-6 text-sm font-semibold">
                    Jelajahi Alat Populer
                </a>
            </div>

            <!-- Value Highlights -->
            <div class="pt-8 border-t border-darkbrown-700/80 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="font-extrabold text-base text-sunglow-300 block">100+</span>
                    <span class="text-wheat-400">Unit Siap Sewa</span>
                </div>
                <div>
                    <span class="font-extrabold text-base text-sunglow-300 block">100%</span>
                    <span class="text-wheat-400">Steril & Bersih</span>
                </div>
                <div>
                    <span class="font-extrabold text-base text-sunglow-300 block">Instant</span>
                    <span class="text-wheat-400">Booking & POS Kasir</span>
                </div>
                <div>
                    <span class="font-extrabold text-base text-sunglow-300 block">4.9 ★</span>
                    <span class="text-wheat-400">Kepuasan Camper</span>
                </div>
            </div>
        </div>
    </section>

    <!-- PALETTE BANNER & INTRO TO NEW TEMPLATE -->
    <div class="bg-white rounded-2xl border border-wheat-200/90 p-6 shadow-tendaku-sm my-8 flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="space-y-1">
            <span class="text-xs font-bold uppercase tracking-wider text-avocado-600">Terintegrasi Dengan Palet Desain Baru</span>
            <h3 class="text-lg font-bold text-darkbrown-800">
                Warna Brand: Wheat • Sunglow • Goldenrod • Avocado • Dark Brown
            </h3>
            <p class="text-xs sm:text-sm text-darkbrown-500 max-w-xl">
                Seluruh komponen, tombol, badge, kartu, formulir, serta layout telah disesuaikan agar serasi dan konsisten di setiap fitur aplikasi.
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <!-- Palette Color Dots -->
            <div class="flex items-center -space-x-2 mr-2">
                <span class="w-8 h-8 rounded-full border-2 border-white shadow-sm bg-[#F9E3B6]" title="Wheat (#F9E3B6)"></span>
                <span class="w-8 h-8 rounded-full border-2 border-white shadow-sm bg-[#FBCE6B]" title="Sunglow (#FBCE6B)"></span>
                <span class="w-8 h-8 rounded-full border-2 border-white shadow-sm bg-[#D5A007]" title="Goldenrod (#D5A007)"></span>
                <span class="w-8 h-8 rounded-full border-2 border-white shadow-sm bg-[#6C8B08]" title="Avocado (#6C8B08)"></span>
                <span class="w-8 h-8 rounded-full border-2 border-white shadow-sm bg-[#2B2202]" title="Drab Dark Brown (#2B2202)"></span>
            </div>
            <a href="{{ url('/design-system') }}" class="btn-tendaku-primary !py-2.5 !px-4 text-xs font-bold">
                Buka Panduan Desain →
            </a>
        </div>
    </div>

    <!-- CATEGORY PILLS -->
    <section class="my-10 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-extrabold text-darkbrown-800 tracking-tight">Kategori Perlengkapan</h2>
                <p class="text-xs text-darkbrown-500">Pilih kebutuhan camping Anda berdasarkan kategori perlengkapan</p>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
            <a href="#" class="p-4 rounded-2xl bg-white border border-wheat-200 hover:border-avocado-400 hover:bg-avocado-50/40 shadow-tendaku-sm transition text-center group">
                <div class="w-12 h-12 rounded-xl bg-wheat-100 group-hover:bg-avocado-500 group-hover:text-white text-avocado-700 flex items-center justify-center mx-auto mb-2 transition">
                    ⛺
                </div>
                <span class="text-xs font-bold text-darkbrown-800 group-hover:text-avocado-700">Tenda & Flysheet</span>
            </a>

            <a href="#" class="p-4 rounded-2xl bg-white border border-wheat-200 hover:border-avocado-400 hover:bg-avocado-50/40 shadow-tendaku-sm transition text-center group">
                <div class="w-12 h-12 rounded-xl bg-wheat-100 group-hover:bg-avocado-500 group-hover:text-white text-avocado-700 flex items-center justify-center mx-auto mb-2 transition">
                    🛏️
                </div>
                <span class="text-xs font-bold text-darkbrown-800 group-hover:text-avocado-700">Sleeping Bag</span>
            </a>

            <a href="#" class="p-4 rounded-2xl bg-white border border-wheat-200 hover:border-avocado-400 hover:bg-avocado-50/40 shadow-tendaku-sm transition text-center group">
                <div class="w-12 h-12 rounded-xl bg-wheat-100 group-hover:bg-avocado-500 group-hover:text-white text-avocado-700 flex items-center justify-center mx-auto mb-2 transition">
                    🍳
                </div>
                <span class="text-xs font-bold text-darkbrown-800 group-hover:text-avocado-700">Masak & Nesting</span>
            </a>

            <a href="#" class="p-4 rounded-2xl bg-white border border-wheat-200 hover:border-avocado-400 hover:bg-avocado-50/40 shadow-tendaku-sm transition text-center group">
                <div class="w-12 h-12 rounded-xl bg-wheat-100 group-hover:bg-avocado-500 group-hover:text-white text-avocado-700 flex items-center justify-center mx-auto mb-2 transition">
                    🎒
                </div>
                <span class="text-xs font-bold text-darkbrown-800 group-hover:text-avocado-700">Carrier & Ransel</span>
            </a>

            <a href="#" class="p-4 rounded-2xl bg-white border border-wheat-200 hover:border-avocado-400 hover:bg-avocado-50/40 shadow-tendaku-sm transition text-center group">
                <div class="w-12 h-12 rounded-xl bg-wheat-100 group-hover:bg-avocado-500 group-hover:text-white text-avocado-700 flex items-center justify-center mx-auto mb-2 transition">
                    💡
                </div>
                <span class="text-xs font-bold text-darkbrown-800 group-hover:text-avocado-700">Lampu & Headlamp</span>
            </a>

            <a href="#" class="p-4 rounded-2xl bg-white border border-wheat-200 hover:border-avocado-400 hover:bg-avocado-50/40 shadow-tendaku-sm transition text-center group">
                <div class="w-12 h-12 rounded-xl bg-wheat-100 group-hover:bg-avocado-500 group-hover:text-white text-avocado-700 flex items-center justify-center mx-auto mb-2 transition">
                    🪑
                </div>
                <span class="text-xs font-bold text-darkbrown-800 group-hover:text-avocado-700">Meja & Kursi Lipat</span>
            </a>
        </div>
    </section>

    <!-- POPULAR RENTAL ITEMS -->
    <section id="katalog-populer" class="my-12 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-avocado-600">Paling Sering Disewa</span>
                <h2 class="text-2xl font-extrabold text-darkbrown-800 tracking-tight">Peralatan Populer Minggu Ini</h2>
            </div>
            <a href="{{ url('/design-system#templates') }}" class="text-xs font-bold text-avocado-700 hover:text-avocado-800 flex items-center gap-1">
                Lihat Semua Koleksi Alat →
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Product 1 -->
            <div class="card-tendaku overflow-hidden flex flex-col group hover:-translate-y-1 transition duration-200">
                <div class="relative h-48 bg-[#FAF6ED] flex items-center justify-center overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-t from-darkbrown-900/60 via-transparent to-transparent z-10"></div>
                    <svg class="w-28 h-28 text-avocado-600 group-hover:scale-105 transition duration-300" viewBox="0 0 100 100" fill="none">
                        <path d="M50 18L18 78H82L50 18Z" fill="#F9E3B6" stroke="#2B2202" stroke-width="2.5"/>
                        <path d="M50 18L82 78H62L50 18Z" fill="#D5A007"/>
                        <path d="M45 52L38 78H54L45 52Z" fill="#2B2202"/>
                    </svg>
                    <div class="absolute top-3 left-3 z-20">
                        <x-badge variant="avocado" :dot="true">Tersedia</x-badge>
                    </div>
                    <div class="absolute bottom-3 left-3 z-20 text-white font-bold text-xs">
                        Kapasitas 4 Orang
                    </div>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
                    <div>
                        <h4 class="font-extrabold text-base text-darkbrown-800">Tenda Dome Arpenaz 4.1 Fresh & Black</h4>
                        <p class="text-xs text-darkbrown-500 mt-1">Sangat sejuk saat terik matahari dan tahan terhadap terpaan angin badai.</p>
                    </div>
                    <div class="pt-3 border-t border-wheat-200 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-darkbrown-400 block font-bold uppercase">Harga Sewa</span>
                            <span class="text-base font-extrabold text-darkbrown-900">Rp 75.000<span class="text-xs font-normal text-darkbrown-500">/hari</span></span>
                        </div>
                        <x-primary-button variant="avocado" size="sm">+ Sewa</x-primary-button>
                    </div>
                </div>
            </div>

            <!-- Product 2 -->
            <div class="card-tendaku overflow-hidden flex flex-col group hover:-translate-y-1 transition duration-200">
                <div class="relative h-48 bg-[#FAF6ED] flex items-center justify-center overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-t from-darkbrown-900/60 via-transparent to-transparent z-10"></div>
                    <svg class="w-24 h-24 text-goldenrod-600 group-hover:scale-105 transition duration-300" viewBox="0 0 100 100" fill="none">
                        <rect x="32" y="20" width="36" height="60" rx="8" fill="#FBCE6B" stroke="#2B2202" stroke-width="2.5"/>
                        <line x1="38" y1="32" x2="62" y2="32" stroke="#D5A007" stroke-width="3" stroke-linecap="round"/>
                        <line x1="38" y1="44" x2="62" y2="44" stroke="#D5A007" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                    <div class="absolute top-3 left-3 z-20">
                        <x-badge variant="goldenrod" :dot="true">Sisa 2 Unit</x-badge>
                    </div>
                    <div class="absolute bottom-3 left-3 z-20 text-white font-bold text-xs">
                        Limit Suhu 0°C
                    </div>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
                    <div>
                        <h4 class="font-extrabold text-base text-darkbrown-800">Sleeping Bag Bulu Angsa Ultralight</h4>
                        <p class="text-xs text-darkbrown-500 mt-1">Super hangat dengan packing kecil, cocok untuk pendakian suhu dingin.</p>
                    </div>
                    <div class="pt-3 border-t border-wheat-200 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-darkbrown-400 block font-bold uppercase">Harga Sewa</span>
                            <span class="text-base font-extrabold text-darkbrown-900">Rp 25.000<span class="text-xs font-normal text-darkbrown-500">/hari</span></span>
                        </div>
                        <x-primary-button variant="avocado" size="sm">+ Sewa</x-primary-button>
                    </div>
                </div>
            </div>

            <!-- Product 3 -->
            <div class="card-tendaku overflow-hidden flex flex-col group hover:-translate-y-1 transition duration-200">
                <div class="relative h-48 bg-[#FAF6ED] flex items-center justify-center overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-t from-darkbrown-900/60 via-transparent to-transparent z-10"></div>
                    <svg class="w-24 h-24 text-avocado-600 group-hover:scale-105 transition duration-300" viewBox="0 0 100 100" fill="none">
                        <rect x="25" y="45" width="50" height="25" rx="5" fill="#6C8B08" stroke="#2B2202" stroke-width="2.5"/>
                        <circle cx="50" cy="40" r="10" fill="#D5A007"/>
                        <path d="M45 28L50 20L55 28" stroke="#FBCE6B" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                    <div class="absolute top-3 left-3 z-20">
                        <x-badge variant="avocado" :dot="true">Tersedia</x-badge>
                    </div>
                    <div class="absolute bottom-3 left-3 z-20 text-white font-bold text-xs">
                        Set Lengkap
                    </div>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
                    <div>
                        <h4 class="font-extrabold text-base text-darkbrown-800">Paket Cooking Set & Kompor Windproof</h4>
                        <p class="text-xs text-darkbrown-500 mt-1">Kompor gas portabel tahan angin + nesting 3 susun siap pakai.</p>
                    </div>
                    <div class="pt-3 border-t border-wheat-200 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-darkbrown-400 block font-bold uppercase">Harga Sewa</span>
                            <span class="text-base font-extrabold text-darkbrown-900">Rp 30.000<span class="text-xs font-normal text-darkbrown-500">/hari</span></span>
                        </div>
                        <x-primary-button variant="avocado" size="sm">+ Sewa</x-primary-button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- VENDOR CALL TO ACTION -->
    <section class="my-16 p-8 sm:p-10 rounded-3xl bg-gradient-to-r from-wheat-200 via-sunglow-200 to-goldenrod-200 border border-wheat-300 shadow-tendaku flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="space-y-2 max-w-xl">
            <span class="text-xs font-extrabold uppercase tracking-wider text-darkbrown-700 bg-white/70 px-2.5 py-1 rounded-md">Untuk Pemilik Rental Alat Camping</span>
            <h3 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight">
                Kelola Toko & Kasir POS Rental dengan Tendaku Vendor Portal
            </h3>
            <p class="text-sm text-darkbrown-700 leading-relaxed">
                Kelola stok unit tenda, booking pelanggan, pembayaran otomatis, dan serah terima unit secara efisien dalam satu dashboard.
            </p>
        </div>

        <div class="shrink-0 flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="btn-tendaku-dark !py-3 !px-5 text-sm font-bold shadow-tendaku">
                Masuk Portal Vendor & POS →
            </a>
        </div>
    </section>
</x-layouts.customer>
