<x-layouts.customer>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-avocado-100 text-avocado-800 border border-avocado-300">
                        Design System v1.0
                    </span>
                    <span class="text-xs text-darkbrown-400">Outdoor & Nature Camping Theme</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-darkbrown-800 tracking-tight">
                    Tendaku Master Design Template
                </h1>
                <p class="text-sm text-darkbrown-500 mt-1">
                    Pedoman palet warna, tipografi, dan komponen siap pakai untuk diimplementasikan di seluruh fitur Tendaku (Customer & Vendor).
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="#palette" class="btn-tendaku-secondary !py-2 !px-3 text-xs">Palet Warna</a>
                <a href="#components" class="btn-tendaku-secondary !py-2 !px-3 text-xs">Komponen</a>
                <a href="#templates" class="btn-tendaku-primary !py-2 !px-3 text-xs">Contoh Fitur</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-12 py-4">

        <!-- SECTION 1: PALET WARNA RESMI -->
        <section id="palette" class="space-y-6">
            <div class="flex items-center justify-between border-b border-wheat-200 pb-3">
                <div>
                    <h2 class="text-xl font-bold text-darkbrown-800 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-avocado-500"></span>
                        1. Palet Warna Utama (Tendaku Brand Palette)
                    </h2>
                    <p class="text-xs sm:text-sm text-darkbrown-500">
                        Diambil dari lanskap alam outdoor: kanvas tenda, cahaya matahari pagi, tanaman pinus, dan tanah bumi alami.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
                <!-- Color 1: WHEAT -->
                <div class="card-tendaku overflow-hidden group hover:scale-[1.02] transition-all">
                    <div class="h-28 bg-[#F9E3B6] flex items-end p-4 justify-between relative shadow-inner">
                        <span class="text-xs font-mono font-bold text-darkbrown-900 bg-white/70 px-2 py-0.5 rounded backdrop-blur">#F9E3B6</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-800">Warm Sand</span>
                    </div>
                    <div class="p-4 space-y-2">
                        <div class="flex items-baseline justify-between">
                            <h3 class="font-extrabold text-base text-darkbrown-800">WHEAT</h3>
                            <span class="text-[11px] font-semibold text-darkbrown-400">Surface / Canvas</span>
                        </div>
                        <p class="text-xs text-darkbrown-600 leading-relaxed">
                            Warna dasar lembut untuk background hangat, kartu sekunder, garis border halus, dan hover netral.
                        </p>
                        <div class="pt-2 border-t border-wheat-200/80 text-[11px] font-mono text-darkbrown-500 space-y-0.5">
                            <div>RGB: 249, 227, 182</div>
                            <div>Class: <code class="text-avocado-700 font-bold">bg-tendaku-wheat</code></div>
                        </div>
                    </div>
                </div>

                <!-- Color 2: SUNGLOW -->
                <div class="card-tendaku overflow-hidden group hover:scale-[1.02] transition-all">
                    <div class="h-28 bg-[#FBCE6B] flex items-end p-4 justify-between relative shadow-inner">
                        <span class="text-xs font-mono font-bold text-darkbrown-900 bg-white/70 px-2 py-0.5 rounded backdrop-blur">#FBCE6B</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-darkbrown-800">Morning Sun</span>
                    </div>
                    <div class="p-4 space-y-2">
                        <div class="flex items-baseline justify-between">
                            <h3 class="font-extrabold text-base text-darkbrown-800">SUNGLOW</h3>
                            <span class="text-[11px] font-semibold text-goldenrod-700">Highlight / Alert</span>
                        </div>
                        <p class="text-xs text-darkbrown-600 leading-relaxed">
                            Aksen cerah penuh energi untuk badge status "Menunggu", bintang rating, promo banner, dan efek glow.
                        </p>
                        <div class="pt-2 border-t border-wheat-200/80 text-[11px] font-mono text-darkbrown-500 space-y-0.5">
                            <div>RGB: 251, 206, 107</div>
                            <div>Class: <code class="text-goldenrod-700 font-bold">bg-tendaku-sunglow</code></div>
                        </div>
                    </div>
                </div>

                <!-- Color 3: GOLDENROD -->
                <div class="card-tendaku overflow-hidden group hover:scale-[1.02] transition-all">
                    <div class="h-28 bg-[#D5A007] flex items-end p-4 justify-between relative shadow-inner">
                        <span class="text-xs font-mono font-bold text-white bg-black/30 px-2 py-0.5 rounded backdrop-blur">#D5A007</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-white">Warm Amber</span>
                    </div>
                    <div class="p-4 space-y-2">
                        <div class="flex items-baseline justify-between">
                            <h3 class="font-extrabold text-base text-darkbrown-800">GOLDENROD</h3>
                            <span class="text-[11px] font-semibold text-goldenrod-800">Secondary CTA</span>
                        </div>
                        <p class="text-xs text-darkbrown-600 leading-relaxed">
                            Warna aksen emas hangat untuk tombol Checkout, lencana status "Disewa", border penting, dan tag premium.
                        </p>
                        <div class="pt-2 border-t border-wheat-200/80 text-[11px] font-mono text-darkbrown-500 space-y-0.5">
                            <div>RGB: 213, 160, 7</div>
                            <div>Class: <code class="text-goldenrod-700 font-bold">bg-tendaku-goldenrod</code></div>
                        </div>
                    </div>
                </div>

                <!-- Color 4: AVOCADO -->
                <div class="card-tendaku overflow-hidden group hover:scale-[1.02] transition-all ring-2 ring-avocado-500/20">
                    <div class="h-28 bg-[#6C8B08] flex items-end p-4 justify-between relative shadow-inner">
                        <span class="text-xs font-mono font-bold text-white bg-black/30 px-2 py-0.5 rounded backdrop-blur">#6C8B08</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-white">Forest Green</span>
                    </div>
                    <div class="p-4 space-y-2">
                        <div class="flex items-baseline justify-between">
                            <h3 class="font-extrabold text-base text-darkbrown-800">AVOCADO</h3>
                            <span class="text-[11px] font-semibold text-avocado-700">Primary Brand</span>
                        </div>
                        <p class="text-xs text-darkbrown-600 leading-relaxed">
                            Warna utama Tendaku yang melambangkan alam, petualangan camping, tombol aksi utama, dan ketersediaan stok.
                        </p>
                        <div class="pt-2 border-t border-wheat-200/80 text-[11px] font-mono text-darkbrown-500 space-y-0.5">
                            <div>RGB: 108, 139, 8</div>
                            <div>Class: <code class="text-avocado-700 font-bold">bg-tendaku-avocado</code></div>
                        </div>
                    </div>
                </div>

                <!-- Color 5: DRAB DARK BROWN -->
                <div class="card-tendaku overflow-hidden group hover:scale-[1.02] transition-all">
                    <div class="h-28 bg-[#2B2202] flex items-end p-4 justify-between relative shadow-inner">
                        <span class="text-xs font-mono font-bold text-wheat-200 bg-black/40 px-2 py-0.5 rounded backdrop-blur">#2B2202</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-wheat-300">Deep Earth</span>
                    </div>
                    <div class="p-4 space-y-2">
                        <div class="flex items-baseline justify-between">
                            <h3 class="font-extrabold text-base text-darkbrown-800">DARK BROWN</h3>
                            <span class="text-[11px] font-semibold text-darkbrown-600">Contrast / Dark Mode</span>
                        </div>
                        <p class="text-xs text-darkbrown-600 leading-relaxed">
                            Warna tanah gelap untuk teks berkontras tinggi, sidebar vendor, footer website, dan elemen struktural.
                        </p>
                        <div class="pt-2 border-t border-wheat-200/80 text-[11px] font-mono text-darkbrown-500 space-y-0.5">
                            <div>RGB: 43, 34, 2</div>
                            <div>Class: <code class="text-darkbrown-700 font-bold">bg-tendaku-brown</code></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tonal Scale Demonstration -->
            <div class="card-tendaku p-6 space-y-4">
                <h3 class="text-sm font-bold text-darkbrown-800 uppercase tracking-wider">Tonal Shades Scale (Otomatis Tersedia di Tailwind)</h3>
                <div class="space-y-3">
                    <!-- Avocado Shades -->
                    <div>
                        <div class="flex items-center justify-between text-xs font-semibold text-darkbrown-700 mb-1">
                            <span>Avocado Scale (Primary Brand)</span>
                            <span class="font-mono text-darkbrown-400">avocado-50 s/d avocado-900</span>
                        </div>
                        <div class="grid grid-cols-10 h-7 rounded-xl overflow-hidden border border-wheat-300">
                            <div class="bg-avocado-50 flex items-center justify-center text-[9px] text-darkbrown-700">50</div>
                            <div class="bg-avocado-100 flex items-center justify-center text-[9px] text-darkbrown-700">100</div>
                            <div class="bg-avocado-200 flex items-center justify-center text-[9px] text-darkbrown-700">200</div>
                            <div class="bg-avocado-300 flex items-center justify-center text-[9px] text-darkbrown-800">300</div>
                            <div class="bg-avocado-400 flex items-center justify-center text-[9px] text-white">400</div>
                            <div class="bg-avocado-500 flex items-center justify-center text-[9px] font-bold text-white ring-2 ring-white">500*</div>
                            <div class="bg-avocado-600 flex items-center justify-center text-[9px] text-white">600</div>
                            <div class="bg-avocado-700 flex items-center justify-center text-[9px] text-white">700</div>
                            <div class="bg-avocado-800 flex items-center justify-center text-[9px] text-white">800</div>
                            <div class="bg-avocado-900 flex items-center justify-center text-[9px] text-white">900</div>
                        </div>
                    </div>

                    <!-- Goldenrod & Sunglow Scale -->
                    <div>
                        <div class="flex items-center justify-between text-xs font-semibold text-darkbrown-700 mb-1">
                            <span>Goldenrod & Sunglow Scale (Accent & Highlights)</span>
                            <span class="font-mono text-darkbrown-400">goldenrod-50 s/d goldenrod-900</span>
                        </div>
                        <div class="grid grid-cols-10 h-7 rounded-xl overflow-hidden border border-wheat-300">
                            <div class="bg-goldenrod-50 flex items-center justify-center text-[9px] text-darkbrown-700">50</div>
                            <div class="bg-goldenrod-100 flex items-center justify-center text-[9px] text-darkbrown-700">100</div>
                            <div class="bg-goldenrod-200 flex items-center justify-center text-[9px] text-darkbrown-700">200</div>
                            <div class="bg-goldenrod-300 flex items-center justify-center text-[9px] text-darkbrown-800">300</div>
                            <div class="bg-goldenrod-400 flex items-center justify-center text-[9px] text-darkbrown-900">400</div>
                            <div class="bg-goldenrod-500 flex items-center justify-center text-[9px] font-bold text-white ring-2 ring-white">500*</div>
                            <div class="bg-goldenrod-600 flex items-center justify-center text-[9px] text-white">600</div>
                            <div class="bg-goldenrod-700 flex items-center justify-center text-[9px] text-white">700</div>
                            <div class="bg-goldenrod-800 flex items-center justify-center text-[9px] text-white">800</div>
                            <div class="bg-goldenrod-900 flex items-center justify-center text-[9px] text-white">900</div>
                        </div>
                    </div>

                    <!-- Wheat & Dark Brown Scale -->
                    <div>
                        <div class="flex items-center justify-between text-xs font-semibold text-darkbrown-700 mb-1">
                            <span>Wheat & Dark Brown (Surface & High Contrast Ground)</span>
                            <span class="font-mono text-darkbrown-400">wheat-300 & darkbrown-800</span>
                        </div>
                        <div class="grid grid-cols-10 h-7 rounded-xl overflow-hidden border border-wheat-300">
                            <div class="bg-wheat-50 flex items-center justify-center text-[9px] text-darkbrown-700">50</div>
                            <div class="bg-wheat-100 flex items-center justify-center text-[9px] text-darkbrown-700">100</div>
                            <div class="bg-wheat-200 flex items-center justify-center text-[9px] text-darkbrown-700">200</div>
                            <div class="bg-wheat-300 flex items-center justify-center text-[9px] font-bold text-darkbrown-900 ring-2 ring-white">Wheat*</div>
                            <div class="bg-wheat-400 flex items-center justify-center text-[9px] text-darkbrown-900">400</div>
                            <div class="bg-darkbrown-400 flex items-center justify-center text-[9px] text-white">400</div>
                            <div class="bg-darkbrown-600 flex items-center justify-center text-[9px] text-white">600</div>
                            <div class="bg-darkbrown-700 flex items-center justify-center text-[9px] text-white">700</div>
                            <div class="bg-darkbrown-800 flex items-center justify-center text-[9px] font-bold text-wheat-200 ring-2 ring-white">Brown*</div>
                            <div class="bg-darkbrown-900 flex items-center justify-center text-[9px] text-wheat-300">900</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 2: KOMPONEN UI SIAP PAKAI -->
        <section id="components" class="space-y-8">
            <div class="border-b border-wheat-200 pb-3">
                <h2 class="text-xl font-bold text-darkbrown-800 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-goldenrod-500"></span>
                    2. Katalog Komponen Siap Pakai
                </h2>
                <p class="text-xs sm:text-sm text-darkbrown-500">
                    Gunakan tag komponen Blade di bawah ini langsung di file view fitur Anda.
                </p>
            </div>

            <!-- BUTTONS SUITE -->
            <x-card title="Buttons & Interaksi (Tombol Aksi)">
                <div class="space-y-6">
                    <div>
                        <p class="text-xs font-semibold text-darkbrown-400 uppercase tracking-wider mb-3">Varian Tombol</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <x-primary-button variant="avocado">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Primary (Avocado)
                            </x-primary-button>

                            <x-primary-button variant="goldenrod">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                Accent (Goldenrod)
                            </x-primary-button>

                            <x-primary-button variant="sunglow">
                                Highlight (Sunglow)
                            </x-primary-button>

                            <x-secondary-button>
                                Secondary (Wheat)
                            </x-secondary-button>

                            <x-primary-button variant="dark">
                                Dark (Earth Brown)
                            </x-primary-button>

                            <button class="btn-tendaku-outline">
                                Outline Action
                            </button>

                            <x-primary-button variant="avocado" disabled>
                                Disabled
                            </x-primary-button>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-darkbrown-400 uppercase tracking-wider mb-3">Ukuran Tombol</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <x-primary-button size="sm">Small (sm)</x-primary-button>
                            <x-primary-button size="md">Regular (md)</x-primary-button>
                            <x-primary-button size="lg">Large Hero CTA (lg)</x-primary-button>
                        </div>
                    </div>

                    <div class="p-3 bg-wheat-50 rounded-xl border border-wheat-200 text-xs font-mono text-darkbrown-700">
                        &lt;x-primary-button variant="avocado"&gt;Sewa Tenda&lt;/x-primary-button&gt;<br>
                        &lt;x-secondary-button&gt;Batal&lt;/x-secondary-button&gt;
                    </div>
                </div>
            </x-card>

            <!-- BADGES & STATUS INDICATORS -->
            <x-card title="Badges & Status Persewaan (Sewa & Unit)">
                <div class="space-y-4">
                    <p class="text-xs text-darkbrown-500">
                        Menstandarisasi status alat camping di katalog, keranjang, serah terima, dan pembayaran.
                    </p>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-badge variant="avocado" :dot="true">Tersedia (Ready)</x-badge>
                        <x-badge variant="goldenrod" :dot="true">Disewa (Active)</x-badge>
                        <x-badge variant="sunglow" :dot="true">Booking Menunggu DP</x-badge>
                        <x-badge variant="wheat" :dot="true">Dalam Pengecekan</x-badge>
                        <x-badge variant="dark" :dot="true">Selesai / Dikembalikan</x-badge>
                        <x-badge variant="danger" :dot="true">Terlambat / Denda</x-badge>
                    </div>

                    <div class="p-3 bg-wheat-50 rounded-xl border border-wheat-200 text-xs font-mono text-darkbrown-700">
                        &lt;x-badge variant="avocado" :dot="true"&gt;Tersedia&lt;/x-badge&gt;
                    </div>
                </div>
            </x-card>

            <!-- STAT CARDS (METRICS & KPI) -->
            <div>
                <h3 class="text-base font-bold text-darkbrown-800 mb-3">Stat Cards (Metrik & Ringkasan Fitur)</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-stat-card 
                        title="Pendapatan Hari Ini"
                        value="Rp 1.450.000"
                        trend="+18% vs kmrn"
                        :trendUp="true"
                        description="Dari 6 transaksi rental"
                        color="avocado"
                    >
                        <x-slot name="icon">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </x-slot>
                    </x-stat-card>

                    <x-stat-card 
                        title="Tenda Disewa Saat Ini"
                        value="24 Unit"
                        trend="+4 unit"
                        :trendUp="true"
                        description="8 booking siap ambil"
                        color="goldenrod"
                    >
                        <x-slot name="icon">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </x-slot>
                    </x-stat-card>

                    <x-stat-card 
                        title="Unit Tersedia"
                        value="82 Unit"
                        description="Siap disewakan sekarang"
                        color="sunglow"
                    >
                        <x-slot name="icon">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </x-slot>
                    </x-stat-card>

                    <x-stat-card 
                        title="Jatuh Tempo Hari Ini"
                        value="5 Pengembalian"
                        trend="Perlu cek kondisi"
                        :trendUp="false"
                        description="Serah terima sore ini"
                        color="dark"
                    >
                        <x-slot name="icon">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </x-slot>
                    </x-stat-card>
                </div>
            </div>

            <!-- FORM CONTROLS -->
            <x-card title="Form Controls & Input Fields">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-darkbrown-700 uppercase tracking-wider mb-1.5">
                            Cari Alat Camping
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-darkbrown-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <x-text-input class="pl-9" placeholder="Contoh: Tenda Arpenaz 4.1, Matras..." />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-darkbrown-700 uppercase tracking-wider mb-1.5">
                            Kategori Perlengkapan
                        </label>
                        <select class="input-tendaku">
                            <option>Semua Kategori</option>
                            <option>Tenda & Dome</option>
                            <option>Sleeping Bag & Matras</option>
                            <option>Peralatan Masak Outdoor</option>
                            <option>Carrier & Ransel Gunung</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-darkbrown-700 uppercase tracking-wider mb-1.5">
                            Durasi Sewa (Hari)
                        </label>
                        <div class="flex items-center gap-2">
                            <x-text-input type="number" value="3" class="text-center font-bold" />
                            <span class="text-xs font-semibold text-darkbrown-500">Malam</span>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- ALERTS -->
            <div class="space-y-3">
                <x-alert type="success" title="Pembayaran Terverifikasi">
                    Booking #TDK-2026 telah diverifikasi otomatis oleh Midtrans. Unit tenda telah disiapkan oleh vendor.
                </x-alert>
                <x-alert type="warning" title="Perhatian Cuaca & Kondisi Alat">
                    Harap keringkan tenda sebelum dimasukkan ke dalam cover penyimpanan untuk mencegah jamur.
                </x-alert>
            </div>
        </section>

        <!-- SECTION 3: TEMPLATE CONTOH FITUR LENGKAP -->
        <section id="templates" class="space-y-8 pt-4">
            <div class="border-b border-wheat-200 pb-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-darkbrown-800 flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-sunglow-400"></span>
                            3. Template Desain Fitur Nyata (Real-World Features)
                        </h2>
                        <p class="text-xs sm:text-sm text-darkbrown-500">
                            Pola desain yang dapat langsung Anda terapkan untuk fitur Pelanggan & Vendor di bawah ini.
                        </p>
                    </div>
                </div>
            </div>

            <!-- FEATURE 1: CUSTOMER CATALOG & PRODUCT CARD TEMPLATE -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-extrabold text-lg text-darkbrown-800">
                        Template Fitur 1: Kartu Produk & Booking Katalog (Customer)
                    </h3>
                    <span class="text-xs font-bold text-avocado-700 bg-avocado-100 px-3 py-1 rounded-full">
                        Customer Portal
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Sample Product Card 1 -->
                    <div class="card-tendaku overflow-hidden flex flex-col group hover:-translate-y-1 transition-all duration-300">
                        <div class="relative h-48 bg-wheat-100 overflow-hidden">
                            <!-- Warm Outdoor Graphic Card Background -->
                            <div class="absolute inset-0 bg-gradient-to-t from-darkbrown-900/70 via-transparent to-transparent z-10"></div>
                            
                            <!-- Tent Visual Canvas -->
                            <div class="w-full h-full flex items-center justify-center bg-[#FAF6ED] p-6 group-hover:scale-105 transition-transform duration-500">
                                <svg class="w-32 h-32 text-avocado-600" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M50 15L15 75H85L50 15Z" fill="#F9E3B6" stroke="#2B2202" stroke-width="3"/>
                                    <path d="M50 15L85 75H65L50 15Z" fill="#D5A007"/>
                                    <path d="M44 48L35 75H55L44 48Z" fill="#2B2202"/>
                                    <line x1="50" y1="15" x2="50" y2="75" stroke="#2B2202" stroke-width="2"/>
                                </svg>
                            </div>

                            <!-- Badges on Image -->
                            <div class="absolute top-3 left-3 z-20">
                                <x-badge variant="avocado" :dot="true">Tersedia (Ready 4)</x-badge>
                            </div>
                            <div class="absolute top-3 right-3 z-20">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-darkbrown-800/80 text-sunglow-300 backdrop-blur">
                                    ★ 4.9 (42 Ulasan)
                                </span>
                            </div>

                            <div class="absolute bottom-3 left-3 z-20 text-white">
                                <span class="text-xs uppercase tracking-wider font-semibold text-wheat-300">Kapasitas 4-5 Orang</span>
                            </div>
                        </div>

                        <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                            <div>
                                <h4 class="font-extrabold text-base text-darkbrown-800 group-hover:text-avocado-600 transition">
                                    Tenda Quechua Arpenaz 4.1 Fresh & Black
                                </h4>
                                <p class="text-xs text-darkbrown-500 mt-1 line-clamp-2">
                                    Tenda keluarga dengan 1 kamar tidur luas dan ruang depan yang lapang. Teknologi Fresh & Black tetap sejuk di siang hari.
                                </p>
                            </div>

                            <div class="pt-3 border-t border-wheat-200/80 flex items-center justify-between">
                                <div>
                                    <span class="text-[11px] text-darkbrown-400 block font-medium">Tarif Sewa</span>
                                    <span class="text-lg font-extrabold text-darkbrown-900">
                                        Rp 75.000<span class="text-xs font-medium text-darkbrown-500">/hari</span>
                                    </span>
                                </div>
                                <x-primary-button variant="avocado" size="sm">
                                    + Sewa Sekarang
                                </x-primary-button>
                            </div>
                        </div>
                    </div>

                    <!-- Sample Product Card 2 -->
                    <div class="card-tendaku overflow-hidden flex flex-col group hover:-translate-y-1 transition-all duration-300">
                        <div class="relative h-48 bg-wheat-100 overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-t from-darkbrown-900/70 via-transparent to-transparent z-10"></div>
                            
                            <div class="w-full h-full flex items-center justify-center bg-[#FAF6ED] p-6 group-hover:scale-105 transition-transform duration-500">
                                <svg class="w-28 h-28 text-goldenrod-600" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="30" y="20" width="40" height="60" rx="10" fill="#FBCE6B" stroke="#2B2202" stroke-width="3"/>
                                    <path d="M35 30H65M35 40H65M35 50H65" stroke="#D5A007" stroke-width="3" stroke-linecap="round"/>
                                    <circle cx="50" cy="65" r="5" fill="#6C8B08"/>
                                </svg>
                            </div>

                            <div class="absolute top-3 left-3 z-20">
                                <x-badge variant="goldenrod" :dot="true">Sisa 1 Unit</x-badge>
                            </div>
                            <div class="absolute top-3 right-3 z-20">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-darkbrown-800/80 text-sunglow-300 backdrop-blur">
                                    ★ 4.8 (89)
                                </span>
                            </div>

                            <div class="absolute bottom-3 left-3 z-20 text-white">
                                <span class="text-xs uppercase tracking-wider font-semibold text-wheat-300">Comfort -5°C</span>
                            </div>
                        </div>

                        <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                            <div>
                                <h4 class="font-extrabold text-base text-darkbrown-800 group-hover:text-avocado-600 transition">
                                    Sleeping Bag Bulu Angsa Extrem Warm
                                </h4>
                                <p class="text-xs text-darkbrown-500 mt-1 line-clamp-2">
                                    Kantong tidur hangat super tebal dan ringan, cocok untuk pendakian gunung tinggi seperti Semeru & Rinjani.
                                </p>
                            </div>

                            <div class="pt-3 border-t border-wheat-200/80 flex items-center justify-between">
                                <div>
                                    <span class="text-[11px] text-darkbrown-400 block font-medium">Tarif Sewa</span>
                                    <span class="text-lg font-extrabold text-darkbrown-900">
                                        Rp 25.000<span class="text-xs font-medium text-darkbrown-500">/hari</span>
                                    </span>
                                </div>
                                <x-primary-button variant="avocado" size="sm">
                                    + Sewa Sekarang
                                </x-primary-button>
                            </div>
                        </div>
                    </div>

                    <!-- Sample Product Card 3 -->
                    <div class="card-tendaku overflow-hidden flex flex-col group hover:-translate-y-1 transition-all duration-300">
                        <div class="relative h-48 bg-wheat-100 overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-t from-darkbrown-900/70 via-transparent to-transparent z-10"></div>
                            
                            <div class="w-full h-full flex items-center justify-center bg-[#FAF6ED] p-6 group-hover:scale-105 transition-transform duration-500">
                                <svg class="w-28 h-28 text-avocado-600" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x="25" y="45" width="50" height="25" rx="5" fill="#6C8B08" stroke="#2B2202" stroke-width="3"/>
                                    <circle cx="50" cy="40" r="12" fill="#D5A007"/>
                                    <path d="M45 28L50 20L55 28" stroke="#FBCE6B" stroke-width="3" stroke-linecap="round"/>
                                    <rect x="35" y="70" width="8" height="12" fill="#2B2202"/>
                                    <rect x="57" y="70" width="8" height="12" fill="#2B2202"/>
                                </svg>
                            </div>

                            <div class="absolute top-3 left-3 z-20">
                                <x-badge variant="avocado" :dot="true">Tersedia (Ready 10)</x-badge>
                            </div>
                            <div class="absolute top-3 right-3 z-20">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-darkbrown-800/80 text-sunglow-300 backdrop-blur">
                                    ★ 5.0 (120)
                                </span>
                            </div>

                            <div class="absolute bottom-3 left-3 z-20 text-white">
                                <span class="text-xs uppercase tracking-wider font-semibold text-wheat-300">Kompor + Nesting Set</span>
                            </div>
                        </div>

                        <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                            <div>
                                <h4 class="font-extrabold text-base text-darkbrown-800 group-hover:text-avocado-600 transition">
                                    Paket Masak Outdoor Portable Windproof
                                </h4>
                                <p class="text-xs text-darkbrown-500 mt-1 line-clamp-2">
                                    Lengkap dengan kompor pelindung angin, nesting 3 susun anti-lengket, dan pemantik piezo elektrik.
                                </p>
                            </div>

                            <div class="pt-3 border-t border-wheat-200/80 flex items-center justify-between">
                                <div>
                                    <span class="text-[11px] text-darkbrown-400 block font-medium">Tarif Sewa</span>
                                    <span class="text-lg font-extrabold text-darkbrown-900">
                                        Rp 30.000<span class="text-xs font-medium text-darkbrown-500">/hari</span>
                                    </span>
                                </div>
                                <x-primary-button variant="avocado" size="sm">
                                    + Sewa Sekarang
                                </x-primary-button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FEATURE 2: VENDOR POS & TRANSACTION LIST TEMPLATE -->
            <div class="space-y-4 pt-6">
                <div class="flex items-center justify-between">
                    <h3 class="font-extrabold text-lg text-darkbrown-800">
                        Template Fitur 2: Tabel Serah Terima & Kasir POS (Vendor)
                    </h3>
                    <span class="text-xs font-bold text-wheat-200 bg-darkbrown-800 px-3 py-1 rounded-full">
                        Vendor Portal
                    </span>
                </div>

                <div class="card-tendaku overflow-hidden">
                    <div class="p-5 border-b border-wheat-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-wheat-50/40">
                        <div>
                            <h4 class="font-bold text-darkbrown-800 text-sm">Daftar Transaksi Persewaan & Jadwal Ambil</h4>
                            <p class="text-xs text-darkbrown-500">Monitoring status pembayaran, serah terima unit, dan pengembalian</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-secondary-button size="sm">Filter Status</x-secondary-button>
                            <x-primary-button variant="goldenrod" size="sm">
                                + Catat Sewa POS
                            </x-primary-button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-wheat-100/70 text-darkbrown-600 uppercase font-bold tracking-wider border-b border-wheat-200">
                                <tr>
                                    <th class="py-3 px-4">No. Booking</th>
                                    <th class="py-3 px-4">Pelanggan</th>
                                    <th class="py-3 px-4">Item Disewa</th>
                                    <th class="py-3 px-4">Periode Sewa</th>
                                    <th class="py-3 px-4">Total & DP</th>
                                    <th class="py-3 px-4">Status Transaksi</th>
                                    <th class="py-3 px-4 text-right">Aksi Kasir</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-wheat-200/80">
                                <tr class="hover:bg-wheat-50/60 transition">
                                    <td class="py-3.5 px-4 font-mono font-bold text-darkbrown-800">#TDK-2026-081</td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-darkbrown-800">Boby Rozak</div>
                                        <div class="text-[11px] text-darkbrown-500">0812-9988-7766</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="font-semibold text-darkbrown-800">Tenda Arpenaz 4.1 (Unit #02)</span>
                                        <div class="text-[11px] text-darkbrown-500">+ 2x Sleeping Bag Bulu Angsa</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div>02 Okt - 05 Okt 2026</div>
                                        <span class="text-[11px] font-semibold text-avocado-700">3 Hari Sewa</span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-darkbrown-900">Rp 375.000</div>
                                        <span class="text-[10px] font-bold text-avocado-600 bg-avocado-100 px-1.5 py-0.5 rounded">Lunas (Midtrans)</span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <x-badge variant="sunglow" :dot="true">Siap Diambil</x-badge>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button class="btn-tendaku-primary !py-1 !px-2.5 text-xs">
                                            Serah Terima
                                        </button>
                                    </td>
                                </tr>

                                <tr class="hover:bg-wheat-50/60 transition">
                                    <td class="py-3.5 px-4 font-mono font-bold text-darkbrown-800">#TDK-2026-079</td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-darkbrown-800">Ahmad Fauzi</div>
                                        <div class="text-[11px] text-darkbrown-500">0857-1122-3344</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="font-semibold text-darkbrown-800">Paket Masak Windproof</span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div>30 Sep - 02 Okt 2026</div>
                                        <span class="text-[11px] font-bold text-goldenrod-700">Jatuh Tempo Hari Ini</span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-darkbrown-900">Rp 90.000</div>
                                        <span class="text-[10px] font-bold text-darkbrown-600 bg-wheat-200 px-1.5 py-0.5 rounded">Deposit Rp 50.000</span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <x-badge variant="goldenrod" :dot="true">Sedang Disewa</x-badge>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button class="btn-tendaku-accent !py-1 !px-2.5 text-xs">
                                            Cek Retur Unit
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- DEVELOPER QUICK REFERENCE -->
            <x-card title="Petunjuk Penggunaan Kode untuk Developer" variant="warm">
                <p class="text-xs text-darkbrown-600 mb-4 leading-relaxed">
                    Setiap kali Anda membuat atau memperbarui halaman fitur (Katalog, Checkout, Detail Produk, Inventori Vendor, Kasir POS), cukup gunakan layout dan komponen standar ini:
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs font-mono text-darkbrown-800">
                    <div class="p-4 bg-white rounded-xl border border-wheat-300">
                        <span class="font-bold text-avocado-700 font-sans block mb-2">1. Halaman Fitur Pelanggan (Customer)</span>
                        &lt;x-layouts.customer&gt;<br>
                        &nbsp;&nbsp;&lt;x-slot name="header"&gt;<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&lt;h2 class="text-xl font-bold"&gt;Judul Fitur&lt;/h2&gt;<br>
                        &nbsp;&nbsp;&lt;/x-slot&gt;<br>
                        <br>
                        &nbsp;&nbsp;&lt;x-card title="Konten"&gt;<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&lt;x-primary-button&gt;Simpan&lt;/x-primary-button&gt;<br>
                        &nbsp;&nbsp;&lt;/x-card&gt;<br>
                        &lt;/x-layouts.customer&gt;
                    </div>

                    <div class="p-4 bg-white rounded-xl border border-wheat-300">
                        <span class="font-bold text-goldenrod-700 font-sans block mb-2">2. Halaman Fitur Vendor (Portal & POS)</span>
                        &lt;x-layouts.vendor&gt;<br>
                        &nbsp;&nbsp;&lt;x-slot name="header"&gt;<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&lt;h2 class="text-xl font-bold"&gt;Kelola Stok Alat&lt;/h2&gt;<br>
                        &nbsp;&nbsp;&lt;/x-slot&gt;<br>
                        <br>
                        &nbsp;&nbsp;&lt;x-stat-card title="Unit" value="12" /&gt;<br>
                        &lt;/x-layouts.vendor&gt;
                    </div>
                </div>
            </x-card>

        </section>

    </div>
</x-layouts.customer>
