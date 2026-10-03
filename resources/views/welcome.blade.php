<x-layouts.customer>
    <x-slot name="title">
        Tendaku - Solusi Rental Alat Camping & Petualangan Outdoor
    </x-slot>

    <!-- ============================================================
         HERO SECTION — CTA menuju Katalog & Jelajahi Peralatan
         ============================================================ -->
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
                <a href="{{ route('customer.catalog.index') }}" class="btn-tendaku-accent !py-3.5 !px-6 text-sm font-bold shadow-tendaku-glow">
                    <span>⛺ Jelajahi Katalog Peralatan</span>
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>

                <a href="#jaminan-ktp" class="btn-tendaku-outline !text-wheat-200 !border-wheat-400/30 hover:!bg-white/10 !py-3.5 !px-6 text-sm font-semibold">
                    🪪 Pelajari Jaminan KTP
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
                    <span class="text-wheat-400">Booking Online</span>
                </div>
                <div>
                    <span class="font-extrabold text-base text-sunglow-300 block">4.9 ★</span>
                    <span class="text-wheat-400">Kepuasan Camper</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================
         SECTION JAMINAN KTP (Tanpa Deposit Uang)
         Deliverable PRD Rab 7: Banner Penjelasan Jaminan KTP
         ============================================================ -->
    <section id="jaminan-ktp" class="my-10 bg-gradient-to-br from-avocado-50 via-wheat-50 to-sunglow-50 rounded-3xl border border-avocado-200 shadow-tendaku p-8 sm:p-10">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-8">
            <!-- Kiri: Penjelasan -->
            <div class="space-y-4 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-avocado-100 border border-avocado-300 text-xs font-extrabold text-avocado-900 uppercase tracking-wider">
                    <span>🪪</span>
                    <span>Sistem Jaminan Tendaku</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-darkbrown-900 tracking-tight leading-tight">
                    Tanpa Deposit Uang — Cukup <span class="text-avocado-700">KTP Asli</span> Saat Pickup
                </h2>
                <p class="text-sm text-darkbrown-700 leading-relaxed">
                    Tendaku menerapkan sistem <strong>jaminan fisik KTP asli</strong> sebagai pengganti uang deposit. KTP Anda akan disimpan aman oleh vendor selama masa penyewaan dan dikembalikan saat unit peralatan dikembalikan dalam kondisi baik. Ini membuat penyewaan lebih <strong>terjangkau dan mudah</strong> tanpa perlu menyiapkan dana deposit tambahan.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                    <div class="flex items-start gap-3 p-3 bg-white rounded-xl border border-wheat-200 shadow-sm">
                        <div class="w-10 h-10 rounded-lg bg-sunglow-100 text-sunglow-600 flex items-center justify-center text-lg shrink-0">📋</div>
                        <div>
                            <h4 class="text-xs font-bold text-darkbrown-900">1. Booking Online</h4>
                            <p class="text-[11px] text-darkbrown-600 mt-0.5">Pilih alat & tanggal sewa, bayar lunas via transfer/QRIS.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-white rounded-xl border border-wheat-200 shadow-sm">
                        <div class="w-10 h-10 rounded-lg bg-avocado-100 text-avocado-600 flex items-center justify-center text-lg shrink-0">🪪</div>
                        <div>
                            <h4 class="text-xs font-bold text-darkbrown-900">2. Serah Terima KTP</h4>
                            <p class="text-[11px] text-darkbrown-600 mt-0.5">Tunjukkan KTP asli saat ambil unit di toko vendor.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 p-3 bg-white rounded-xl border border-wheat-200 shadow-sm">
                        <div class="w-10 h-10 rounded-lg bg-goldenrod-100 text-goldenrod-600 flex items-center justify-center text-lg shrink-0">✅</div>
                        <div>
                            <h4 class="text-xs font-bold text-darkbrown-900">3. Kembalikan & Selesai</h4>
                            <p class="text-[11px] text-darkbrown-600 mt-0.5">Return unit, cek kondisi, KTP dikembalikan.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kanan: Badge Visual -->
            <div class="shrink-0 flex flex-col items-center gap-3">
                <div class="w-28 h-28 rounded-2xl bg-white border-2 border-avocado-300 shadow-tendaku-avocado-glow flex flex-col items-center justify-center text-center p-3">
                    <span class="text-3xl">🛡️</span>
                    <span class="text-[10px] font-extrabold uppercase text-avocado-800 mt-1 leading-tight">Deposit<br/>Rp 0</span>
                </div>
                <span class="text-[11px] font-bold text-avocado-800 text-center">Aman & Terpercaya</span>
            </div>
        </div>
    </section>

    <!-- ============================================================
         CATEGORY PILLS — Navigasi ke Halaman Katalog per Kategori
         ============================================================ -->
    <section class="my-10 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-extrabold text-darkbrown-800 tracking-tight">Kategori Perlengkapan</h2>
                <p class="text-xs text-darkbrown-500">Pilih kebutuhan camping Anda berdasarkan kategori perlengkapan</p>
            </div>
            <a href="{{ route('customer.catalog.index') }}" class="hidden sm:inline-flex text-xs font-bold text-avocado-700 hover:text-avocado-800 items-center gap-1 transition">
                Lihat Semua →
            </a>
        </div>

        @php
            $landingCategories = [
                ['icon' => '⛺', 'name' => 'Tenda & Flysheet', 'id' => 1],
                ['icon' => '🛏️', 'name' => 'Sleeping Bag', 'id' => 2],
                ['icon' => '🍳', 'name' => 'Masak & Nesting', 'id' => 3],
                ['icon' => '🎒', 'name' => 'Carrier & Ransel', 'id' => 4],
                ['icon' => '💡', 'name' => 'Lampu & Headlamp', 'id' => 5],
                ['icon' => '🪑', 'name' => 'Meja & Kursi Lipat', 'id' => 6],
            ];
        @endphp

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
            @foreach($landingCategories as $cat)
                <a href="{{ route('customer.catalog.index', ['category_id' => $cat['id']]) }}" class="p-4 rounded-2xl bg-white border border-wheat-200 hover:border-avocado-400 hover:bg-avocado-50/40 shadow-tendaku-sm transition text-center group">
                    <div class="w-12 h-12 rounded-xl bg-wheat-100 group-hover:bg-avocado-500 group-hover:text-white text-avocado-700 flex items-center justify-center mx-auto mb-2 transition text-xl">
                        {{ $cat['icon'] }}
                    </div>
                    <span class="text-xs font-bold text-darkbrown-800 group-hover:text-avocado-700">{{ $cat['name'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <!-- ============================================================
         POPULAR RENTAL ITEMS — Card Produk Populer + Link ke Detail
         ============================================================ -->
    <section id="katalog-populer" class="my-12 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-avocado-600">Paling Sering Disewa</span>
                <h2 class="text-2xl font-extrabold text-darkbrown-800 tracking-tight">Peralatan Populer Minggu Ini</h2>
            </div>
            <a href="{{ route('customer.catalog.index') }}" class="text-xs font-bold text-avocado-700 hover:text-avocado-800 flex items-center gap-1 transition">
                Lihat Semua Koleksi Alat →
            </a>
        </div>

        @php
            $popularItems = [
                [
                    'id' => 1,
                    'name' => 'Tenda Dome Arpenaz 4.1 Fresh & Black',
                    'desc' => 'Sangat sejuk saat terik matahari dan tahan terhadap terpaan angin badai.',
                    'price' => 75000,
                    'capacity' => 'Kapasitas 4 Orang',
                    'badge_text' => 'Tersedia',
                    'badge_variant' => 'avocado',
                    'image' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=800&q=80',
                ],
                [
                    'id' => 2,
                    'name' => 'Sleeping Bag Bulu Angsa Ultralight',
                    'desc' => 'Super hangat dengan packing kecil, cocok untuk pendakian suhu dingin.',
                    'price' => 25000,
                    'capacity' => 'Limit Suhu 0°C',
                    'badge_text' => 'Sisa 2 Unit',
                    'badge_variant' => 'goldenrod',
                    'image' => 'https://images.unsplash.com/photo-1510312305653-8ed496efae75?auto=format&fit=crop&w=800&q=80',
                ],
                [
                    'id' => 3,
                    'name' => 'Paket Cooking Set & Kompor Windproof',
                    'desc' => 'Kompor gas portabel tahan angin + nesting 3 susun siap pakai.',
                    'price' => 30000,
                    'capacity' => 'Set Lengkap',
                    'badge_text' => 'Tersedia',
                    'badge_variant' => 'avocado',
                    'image' => 'https://images.unsplash.com/photo-1526772662000-3f88f10405ff?auto=format&fit=crop&w=800&q=80',
                ],
            ];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($popularItems as $product)
                <a href="{{ route('customer.product.show', $product['id']) }}" class="card-tendaku overflow-hidden flex flex-col group hover:-translate-y-1 transition duration-200 cursor-pointer">
                    <div class="relative h-48 bg-wheat-100 overflow-hidden">
                        <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy"/>
                        <div class="absolute inset-0 bg-gradient-to-t from-darkbrown-900/60 via-transparent to-transparent z-10"></div>
                        <div class="absolute top-3 left-3 z-20">
                            <x-badge :variant="$product['badge_variant']" :dot="true">{{ $product['badge_text'] }}</x-badge>
                        </div>
                        <div class="absolute top-3 right-3 z-20">
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-avocado-600 text-white shadow-sm">Tanpa Deposit</span>
                        </div>
                        <div class="absolute bottom-3 left-3 z-20 text-white font-bold text-xs">
                            {{ $product['capacity'] }}
                        </div>
                    </div>
                    <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
                        <div>
                            <h4 class="font-extrabold text-base text-darkbrown-800 group-hover:text-avocado-700 transition-colors">{{ $product['name'] }}</h4>
                            <p class="text-xs text-darkbrown-500 mt-1">{{ $product['desc'] }}</p>
                        </div>
                        <div class="pt-3 border-t border-wheat-200 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-darkbrown-400 block font-bold uppercase">Harga Sewa</span>
                                <span class="text-base font-extrabold text-darkbrown-900">Rp {{ number_format($product['price'], 0, ',', '.') }}<span class="text-xs font-normal text-darkbrown-500">/hari</span></span>
                            </div>
                            <span class="btn-tendaku-primary !py-1.5 !px-3 text-xs font-bold pointer-events-none">Lihat Detail</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <!-- ============================================================
         KEUNGGULAN TENDAKU — Mengapa Pilih Kami
         ============================================================ -->
    <section class="my-12 space-y-6">
        <div class="text-center max-w-2xl mx-auto">
            <span class="text-xs font-bold uppercase tracking-wider text-goldenrod-600">Mengapa Tendaku?</span>
            <h2 class="text-2xl font-extrabold text-darkbrown-900 tracking-tight mt-1">Pengalaman Sewa Alat Camping yang Aman & Modern</h2>
            <p class="text-sm text-darkbrown-600 mt-2">Tendaku dirancang untuk memudahkan camper Indonesia mendapatkan peralatan outdoor terawat tanpa ribet.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="card-tendaku p-6 text-center space-y-3 hover:shadow-tendaku-glow transition-all duration-200">
                <div class="w-14 h-14 rounded-2xl bg-avocado-100 text-avocado-700 flex items-center justify-center mx-auto text-2xl">✨</div>
                <h4 class="font-bold text-sm text-darkbrown-900">Steril & Higienis</h4>
                <p class="text-xs text-darkbrown-600 leading-relaxed">Setiap unit dicuci, disinfeksi, dan dikemas profesional pasca penyewaan.</p>
            </div>
            <div class="card-tendaku p-6 text-center space-y-3 hover:shadow-tendaku-glow transition-all duration-200">
                <div class="w-14 h-14 rounded-2xl bg-sunglow-100 text-sunglow-700 flex items-center justify-center mx-auto text-2xl">🪪</div>
                <h4 class="font-bold text-sm text-darkbrown-900">Jaminan KTP Saja</h4>
                <p class="text-xs text-darkbrown-600 leading-relaxed">Tidak perlu deposit uang. KTP asli sebagai jaminan saat pengambilan unit.</p>
            </div>
            <div class="card-tendaku p-6 text-center space-y-3 hover:shadow-tendaku-glow transition-all duration-200">
                <div class="w-14 h-14 rounded-2xl bg-goldenrod-100 text-goldenrod-700 flex items-center justify-center mx-auto text-2xl">🌤️</div>
                <h4 class="font-bold text-sm text-darkbrown-900">Prediksi Cuaca</h4>
                <p class="text-xs text-darkbrown-600 leading-relaxed">Cek prakiraan cuaca di halaman detail produk sebelum menentukan tanggal camping.</p>
            </div>
            <div class="card-tendaku p-6 text-center space-y-3 hover:shadow-tendaku-glow transition-all duration-200">
                <div class="w-14 h-14 rounded-2xl bg-wheat-200 text-darkbrown-700 flex items-center justify-center mx-auto text-2xl">⚡</div>
                <h4 class="font-bold text-sm text-darkbrown-900">Booking Instan</h4>
                <p class="text-xs text-darkbrown-600 leading-relaxed">Pilih alat, tentukan tanggal, bayar online, dan ambil unit di vendor terdekat.</p>
            </div>
        </div>
    </section>

    <!-- ============================================================
         VENDOR CALL TO ACTION — Ajakan untuk Pemilik Rental
         ============================================================ -->
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
