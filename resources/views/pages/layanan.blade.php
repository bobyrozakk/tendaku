<x-layouts.customer :title="'Layanan Tendaku - Sewa Tenda, Matras & Perlengkapan Camping Malang'">
    {{-- Hero Banner --}}
    <div class="relative overflow-hidden rounded-3xl border border-wheat-200 bg-gradient-to-br from-wheat-100 via-[#FDFBF7] to-sunglow-100/70 px-6 py-10 sm:px-10 sm:py-14 shadow-tendaku-sm mb-8">
        <div class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-sunglow-300/30 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-24 left-1/4 h-64 w-64 rounded-full bg-avocado-200/30 blur-3xl" aria-hidden="true"></div>

        <div class="relative max-w-3xl">
            <nav class="flex items-center gap-2 text-xs font-semibold text-darkbrown-500 mb-4" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-avocado-700 transition-colors">Beranda</a>
                <span aria-hidden="true">/</span>
                <span class="text-darkbrown-800">Layanan</span>
            </nav>

            <span class="inline-block rounded-full border border-avocado-300/80 bg-white/80 px-3.5 py-1 text-xs font-bold uppercase tracking-[.18em] text-avocado-800 backdrop-blur-sm shadow-sm">
                Layanan &amp; Solusi Outdoor
            </span>

            <h1 class="mt-3 font-heading text-3xl font-extrabold tracking-tight text-darkbrown-900 sm:text-4xl lg:text-5xl leading-tight">
                Layanan Persewaan Alat Camping &amp; Petualangan
            </h1>

            <p class="mt-3 text-base sm:text-lg leading-relaxed text-darkbrown-600 font-normal">
                Solusi lengkap persewaan perlengkapan outdoor di Malang Raya &amp; Batu. Tenda tangguh, matras higienis, alat masak komplit, dan panduan sewa praktis tanpa deposit uang tunai.
            </p>
        </div>
    </div>

    {{-- Sticky Quick Nav Pills --}}
    <div class="sticky top-16 z-30 -mx-4 sm:mx-0 px-4 sm:px-0 py-3 mb-10 bg-[#FDFBF7]/95 backdrop-blur-md border-b border-wheat-200/80">
        <nav class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none" aria-label="Navigasi Bagian Layanan">
            <a href="#sewa-tenda"
               class="inline-flex shrink-0 items-center px-4 py-2 rounded-full text-xs sm:text-sm font-semibold border border-wheat-300 bg-white text-darkbrown-800 hover:border-avocado-500 hover:text-avocado-800 hover:bg-avocado-50/50 transition-all shadow-sm">
                Sewa Tenda
            </a>
            <a href="#sewa-matras"
               class="inline-flex shrink-0 items-center px-4 py-2 rounded-full text-xs sm:text-sm font-semibold border border-wheat-300 bg-white text-darkbrown-800 hover:border-avocado-500 hover:text-avocado-800 hover:bg-avocado-50/50 transition-all shadow-sm">
                Sewa Matras
            </a>
            <a href="#perlengkapan-camping"
               class="inline-flex shrink-0 items-center px-4 py-2 rounded-full text-xs sm:text-sm font-semibold border border-wheat-300 bg-white text-darkbrown-800 hover:border-avocado-500 hover:text-avocado-800 hover:bg-avocado-50/50 transition-all shadow-sm">
                Perlengkapan Camping
            </a>
            <a href="#panduan-penyewaan"
               class="inline-flex shrink-0 items-center px-4 py-2 rounded-full text-xs sm:text-sm font-semibold border border-wheat-300 bg-white text-darkbrown-800 hover:border-avocado-500 hover:text-avocado-800 hover:bg-avocado-50/50 transition-all shadow-sm">
                Panduan Penyewaan
            </a>
            <a href="#faq"
               class="inline-flex shrink-0 items-center px-4 py-2 rounded-full text-xs sm:text-sm font-semibold border border-wheat-300 bg-white text-darkbrown-800 hover:border-avocado-500 hover:text-avocado-800 hover:bg-avocado-50/50 transition-all shadow-sm">
                FAQ
            </a>
        </nav>
    </div>

    {{-- Content Sections --}}
    <div class="space-y-16 lg:space-y-24">

        {{-- 1. SEWA TENDA --}}
        <section id="sewa-tenda" class="scroll-mt-36" aria-labelledby="tenda-title">
            <div class="mb-4">
                <span class="text-xs font-bold uppercase tracking-wider text-avocado-700">01 — Pilihan Tenda Outdoor</span>
                <h2 id="tenda-title" class="font-heading text-2xl sm:text-3xl font-extrabold text-darkbrown-900 mt-1">
                    Sewa Tenda Camping &amp; Dome Berkualitas
                </h2>
            </div>

            <p class="text-darkbrown-600 text-sm sm:text-base leading-relaxed max-w-3xl mb-8">
                Tenda merupakan perlindungan utama saat berkemah di alam bebas. Tendaku menyediakan berbagai jenis tenda berstandar tinggi yang selalu diinspeksi keutuhan framenya, dicek pasaknya, dan dibersihkan higienis sebelum diserahkan.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="rounded-2xl border border-wheat-200 bg-white p-6 shadow-sm hover:border-avocado-300 transition-all">
                    <span class="inline-block text-xs font-bold uppercase tracking-wider text-avocado-700 mb-2">Kapasitas 2–4 Orang</span>
                    <h3 class="font-heading text-lg font-bold text-darkbrown-900 mb-2">Tenda Dome Standar</h3>
                    <p class="text-sm text-darkbrown-600 leading-relaxed mb-4">
                        Mudah didirikan dalam 10 menit, berdesain double-layer anti-embun dan tahan hujan ringan hingga sedang. Cocok untuk Coban Rondo, Bedengan, atau pantai Malang Selatan.
                    </p>
                    <ul class="text-xs text-darkbrown-700 space-y-1.5 font-medium border-t border-wheat-100 pt-3 list-disc list-inside">
                        <li>Kapasitas 2–4 orang dewasa</li>
                        <li>Double layer ventilasi optimal</li>
                        <li>Termasuk pasak &amp; tali pancang</li>
                    </ul>
                </div>

                <div class="rounded-2xl border border-wheat-200 bg-white p-6 shadow-sm hover:border-avocado-300 transition-all">
                    <span class="inline-block text-xs font-bold uppercase tracking-wider text-sunglow-700 mb-2">Kapasitas 6–8 Orang</span>
                    <h3 class="font-heading text-lg font-bold text-darkbrown-900 mb-2">Tenda Keluarga / Family</h3>
                    <p class="text-sm text-darkbrown-600 leading-relaxed mb-4">
                        Dirancang dengan langit-langit tinggi sehingga dapat berdiri leluasa di dalam. Dilengkapi teras depan yang luas untuk berkumpul santai dan memasak bersama rombongan.
                    </p>
                    <ul class="text-xs text-darkbrown-700 space-y-1.5 font-medium border-t border-wheat-100 pt-3 list-disc list-inside">
                        <li>Kapasitas luas hingga 8 orang</li>
                        <li>Vestibule/teras depan luas</li>
                        <li>Frame kokoh tahan terpaan angin</li>
                    </ul>
                </div>

                <div class="rounded-2xl border border-wheat-200 bg-white p-6 shadow-sm hover:border-avocado-300 transition-all">
                    <span class="inline-block text-xs font-bold uppercase tracking-wider text-avocado-800 mb-2">Pendakian Ekstrem</span>
                    <h3 class="font-heading text-lg font-bold text-darkbrown-900 mb-2">Tenda Gunung &amp; Ultralight</h3>
                    <p class="text-sm text-darkbrown-600 leading-relaxed mb-4">
                        Didesain untuk pendakian seperti Semeru, Buthak, atau Arjuno-Welirang. Frame aluminium alloy 7001 ringan berkekuatan tinggi dengan indeks tahan air 3000mm+ PU coating.
                    </p>
                    <ul class="text-xs text-darkbrown-700 space-y-1.5 font-medium border-t border-wheat-100 pt-3 list-disc list-inside">
                        <li>Alloy pole 7001 ekstra kokoh</li>
                        <li>Tahan terpaan badai elevasi tinggi</li>
                        <li>Bobot ringkas dan hemat tempat</li>
                    </ul>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 p-5 rounded-2xl bg-wheat-100/60 border border-wheat-200">
                <p class="text-sm text-darkbrown-800 font-medium">Semua tenda dicek kelengkapannya bersama kasir vendor sebelum serah terima.</p>
                <a href="{{ route('customer.catalog.index', ['search' => 'tenda']) }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-xs sm:text-sm font-bold bg-darkbrown-900 text-white hover:bg-darkbrown-800 transition-colors shadow-sm">
                    Pilih Tenda di Katalog
                    <i data-lucide="arrow-right" class="w-4 h-4" aria-hidden="true"></i>
                </a>
            </div>
        </section>

        {{-- 2. SEWA MATRAS --}}
        <section id="sewa-matras" class="scroll-mt-36" aria-labelledby="matras-title">
            <div class="mb-4">
                <span class="text-xs font-bold uppercase tracking-wider text-sunglow-800">02 — Alas &amp; Isolasi Tidur</span>
                <h2 id="matras-title" class="font-heading text-2xl sm:text-3xl font-extrabold text-darkbrown-900 mt-1">
                    Sewa Matras &amp; Perlengkapan Tidur Nyaman
                </h2>
            </div>

            <p class="text-darkbrown-600 text-sm sm:text-base leading-relaxed max-w-3xl mb-8">
                Tidur nyenyak di alam bebas sangat bergantung pada seberapa baik alas tidurmu mengisolasi hawa dingin dari tanah. Tendaku menyediakan berbagai jenis matras bersih dan sleeping bag wangi siap pakai.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="rounded-2xl border border-wheat-200 bg-white p-6 shadow-sm">
                    <h3 class="font-heading text-lg font-bold text-darkbrown-900 mb-2">Matras Spon &amp; Alumunium Foil</h3>
                    <p class="text-sm text-darkbrown-600 leading-relaxed mb-4">
                        Kombinasi klasik favorit pendaki. Matras alumunium foil memantulkan kembali panas tubuh dan menahan dingin tanah, sedangkan matras spon memberi bantalan empuk anti-robek terhadap kerikil.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-wheat-100 text-darkbrown-800">Tebal 6–8mm</span>
                        <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-wheat-100 text-darkbrown-800">Reflektor Panas</span>
                        <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-wheat-100 text-darkbrown-800">Super Ringan</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-wheat-200 bg-white p-6 shadow-sm">
                    <h3 class="font-heading text-lg font-bold text-darkbrown-900 mb-2">Self-Inflating Mat &amp; Kasur Angin</h3>
                    <p class="text-sm text-darkbrown-600 leading-relaxed mb-4">
                        Matras otomatis memuai dengan busa densitas tinggi di dalamnya. Memberikan kenyamanan tidur seperti di ranjang rumah, meredam permukaan tanah berbatu dengan sempurna.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-sunglow-100 text-sunglow-900">Busa Memuai Otomatis</span>
                        <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-sunglow-100 text-sunglow-900">Tebal 3–5 cm</span>
                        <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-sunglow-100 text-sunglow-900">Ekstra Empuk</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 p-5 rounded-2xl bg-wheat-100/60 border border-wheat-200">
                <p class="text-sm text-darkbrown-800 font-medium">Semua matras dan sleeping bag dicuci higienis dan disanitasi setelah setiap pemakaian.</p>
                <a href="{{ route('customer.catalog.index') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-xs sm:text-sm font-bold bg-darkbrown-900 text-white hover:bg-darkbrown-800 transition-colors shadow-sm">
                    Lihat Matras di Katalog
                    <i data-lucide="arrow-right" class="w-4 h-4" aria-hidden="true"></i>
                </a>
            </div>
        </section>

        {{-- 3. PERLENGKAPAN CAMPING --}}
        <section id="perlengkapan-camping" class="scroll-mt-36" aria-labelledby="alat-title">
            <div class="mb-4">
                <span class="text-xs font-bold uppercase tracking-wider text-darkbrown-600">03 — Peralatan Lengkap</span>
                <h2 id="alat-title" class="font-heading text-2xl sm:text-3xl font-extrabold text-darkbrown-900 mt-1">
                    Sewa Perlengkapan Camping Lengkap
                </h2>
            </div>

            <p class="text-darkbrown-600 text-sm sm:text-base leading-relaxed max-w-3xl mb-8">
                Lengkapi kebutuhan outdoor tanpa harus membeli perlengkapan mahal yang jarang dipakai. Dari alat masak, penerangan malam, meja kursi santai, hingga tas carrier pendakian.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
                <div class="p-5 rounded-2xl border border-wheat-200 bg-white">
                    <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-1.5">Cooking &amp; Nesting</h3>
                    <p class="text-xs text-darkbrown-600 leading-relaxed">
                        Kompor portable windproof, nesting 2–4 susun, teko camping, dan gas kaleng siap pakai.
                    </p>
                </div>

                <div class="p-5 rounded-2xl border border-wheat-200 bg-white">
                    <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-1.5">Lighting &amp; Headlamp</h3>
                    <p class="text-xs text-darkbrown-600 leading-relaxed">
                        Lampu tenda gantung LED 3 mode terang, headlamp sudut 90°, dan lentera darurat baterai awet.
                    </p>
                </div>

                <div class="p-5 rounded-2xl border border-wheat-200 bg-white">
                    <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-1.5">Kursi &amp; Meja Lipat</h3>
                    <p class="text-xs text-darkbrown-600 leading-relaxed">
                        Kursi camping santai lipat aluminium dan meja piknik portable untuk bercengkerama di alam terbuka.
                    </p>
                </div>

                <div class="p-5 rounded-2xl border border-wheat-200 bg-white">
                    <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-1.5">Carrier 45L – 70L</h3>
                    <p class="text-xs text-darkbrown-600 leading-relaxed">
                        Tas gunung dengan backsystem ergonomis, bantalan empuk, dan raincover pelindung hujan.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 p-5 rounded-2xl bg-wheat-100/60 border border-wheat-200">
                <p class="text-sm text-darkbrown-800 font-medium">Mau paket hemat? Cek Paket Camping lengkap khusus pengguna terdaftar.</p>
                <div class="flex items-center gap-3">
                    <a href="{{ route('customer.packages.index') }}"
                       class="inline-flex items-center px-4 py-2 rounded-full text-xs font-bold border border-darkbrown-300 text-darkbrown-900 hover:bg-wheat-200 transition-colors">
                        Lihat Paket Camping
                    </a>
                    <a href="{{ route('customer.catalog.index') }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-xs sm:text-sm font-bold bg-darkbrown-900 text-white hover:bg-darkbrown-800 transition-colors shadow-sm">
                        Eksplorasi Katalog Alat
                        <i data-lucide="arrow-right" class="w-4 h-4" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </section>

        {{-- 4. PANDUAN PENYEWAAN --}}
        <section id="panduan-penyewaan" class="scroll-mt-36" aria-labelledby="panduan-title">
            <div class="mb-4">
                <span class="text-xs font-bold uppercase tracking-wider text-avocado-700">04 — Langkah Mudah</span>
                <h2 id="panduan-title" class="font-heading text-2xl sm:text-3xl font-extrabold text-darkbrown-900 mt-1">
                    Panduan Langkah Penyewaan di Tendaku
                </h2>
            </div>

            <p class="text-darkbrown-600 text-sm sm:text-base leading-relaxed max-w-3xl mb-8">
                Menyewa perlengkapan camping di platform Tendaku sangat sederhana. Cukup ikuti 5 tahapan mudah berikut:
            </p>

            <div class="space-y-4 max-w-4xl">
                <div class="flex items-start gap-4 p-5 rounded-2xl border border-wheat-200 bg-white">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-avocado-600 text-white font-bold text-sm">1</span>
                    <div>
                        <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-1">Pilih Alat &amp; Tentukan Tanggal Sewa</h3>
                        <p class="text-sm text-darkbrown-600 leading-relaxed">
                            Buka menu Katalog Peralatan, pilih barang yang kamu perlukan, lalu tentukan tanggal mulai sewa dan selesai sewa. Sistem kami secara otomatis mengkalkulasi ketersediaan stok fisik unit secara real-time.
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-5 rounded-2xl border border-wheat-200 bg-white">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-avocado-600 text-white font-bold text-sm">2</span>
                    <div>
                        <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-1">Lengkapi Data Pemesanan &amp; Opsi Pengambilan</h3>
                        <p class="text-sm text-darkbrown-600 leading-relaxed">
                            Masukkan ke keranjang, lengkapi identitas dan nomor kontak aktif. Pilih apakah kamu akan mengambil langsung di outlet toko vendor terdekat atau menggunakan opsi pengantaran kurir jika tersedia.
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-5 rounded-2xl border border-wheat-200 bg-white">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-avocado-600 text-white font-bold text-sm">3</span>
                    <div>
                        <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-1">Bayar Online Cepat &amp; Aman (Batas Waktu 60 Menit)</h3>
                        <p class="text-sm text-darkbrown-600 leading-relaxed">
                            Lakukan pembayaran DP atau lunas melalui QRIS atau Virtual Account bank resmi via payment gateway Midtrans. Batas waktu pelunasan adalah 60 menit sebelum pesanan kedaluwarsa otomatis.
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-5 rounded-2xl border border-wheat-200 bg-white">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-avocado-600 text-white font-bold text-sm">4</span>
                    <div>
                        <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-1">Serah Terima &amp; Jaminan KTP Asli Fisik</h3>
                        <p class="text-sm text-darkbrown-600 leading-relaxed">
                            Datang ke outlet vendor sesuai jadwal pengambilan (bawaan pukul 09.00 WIB). Petugas kasir memeriksa kelengkapan unit bersamamu, dan kamu menyerahkan KTP fisik sebagai jaminan tanpa uang deposit.
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-5 rounded-2xl border border-wheat-200 bg-white">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-avocado-600 text-white font-bold text-sm">5</span>
                    <div>
                        <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-1">Kembalikan Alat &amp; KTP Fisik Dikembalikan</h3>
                        <p class="text-sm text-darkbrown-600 leading-relaxed">
                            Kembalikan alat sesuai tanggal dan jam pengembalian. Petugas memeriksa kondisi unit, KTP aslimu diserahkan kembali secara utuh, dan status pesanan otomatis berganti menjadi Selesai.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- 5. FAQ --}}
        <section id="faq" class="scroll-mt-36" aria-labelledby="faq-title" x-data="{ openItem: null }">
            <div class="mb-4">
                <span class="text-xs font-bold uppercase tracking-wider text-sunglow-800">05 — Pertanyaan Populer</span>
                <h2 id="faq-title" class="font-heading text-2xl sm:text-3xl font-extrabold text-darkbrown-900 mt-1">
                    Pertanyaan yang Sering Diajukan (FAQ)
                </h2>
            </div>

            <p class="text-darkbrown-600 text-sm sm:text-base leading-relaxed max-w-3xl mb-8">
                Jawaban ringkas dan jelas untuk pertanyaan yang paling sering diajukan pelanggan Tendaku.
            </p>

            <div class="space-y-3 max-w-4xl">
                @php
                    $faqs = [
                        [
                            'q' => 'Apakah harus membayar deposit uang tunai?',
                            'a' => 'Tidak. Tendaku menerapkan sistem jaminan KTP asli fisik sebagai pengganti deposit uang tunai, sehingga kamu tidak perlu mengeluarkan dana jaminan tambahan.',
                        ],
                        [
                            'q' => 'Metode pembayaran apa saja yang tersedia?',
                            'a' => 'Kami mendukung pembayaran QRIS (semua e-wallet & mobile banking) serta Virtual Account bank resmi (BCA, BNI, BRI, Permata, Mandiri) yang terverifikasi otomatis dalam hitungan detik.',
                        ],
                        [
                            'q' => 'Berapa lama batas waktu pembayaran pesanan?',
                            'a' => 'Batas waktu pembayaran adalah 60 menit sejak pesanan dibuat. Jika pembayaran tidak diselesaikan dalam 60 menit, sistem akan membatalkan pesanan secara otomatis agar stok dapat disewa pengguna lain.',
                        ],
                        [
                            'q' => 'Bagaimana jika alat basah atau kotor setelah camping hujan?',
                            'a' => 'Wajar jika alat camping basah atau terkena percikan lumpur sehabis hujan. Cukup lipat dengan rapi dan kembalikan ke outlet vendor. Tim kami memiliki standar pencucian dan pengeringan profesional.',
                        ],
                        [
                            'q' => 'Bagaimana jika ada bagian alat yang rusak atau hilang?',
                            'a' => 'Kondisi alat akan diperiksa bersama petugas saat pengembalian. Apabila terdapat kerusakan atau komponen hilang (misal pasak atau frame patah), biaya penggantian akan ditentukan sesuai daftar harga suku cadang resmi sebelum proses selesai.',
                        ],
                        [
                            'q' => 'Bagaimana cara menghubungi customer support Tendaku?',
                            'a' => 'Kamu dapat menghubungi kami via WhatsApp di 0812-3456-7890 atau email resmi admintendaku@gmail.com pada jam operasional setiap hari pukul 08.00 – 20.00 WIB.',
                        ],
                    ];
                @endphp

                @foreach($faqs as $i => $faq)
                    <div class="rounded-2xl border border-wheat-200 bg-white overflow-hidden transition-colors">
                        <button type="button"
                                class="w-full flex items-center justify-between p-5 text-left font-heading font-bold text-darkbrown-900 text-base hover:text-avocado-800 transition-colors"
                                @click="openItem = openItem === {{ $i }} ? null : {{ $i }}"
                                :aria-expanded="openItem === {{ $i }}">
                            <span>{{ $faq['q'] }}</span>
                            <i data-lucide="chevron-down"
                               class="w-5 h-5 text-darkbrown-500 shrink-0 transition-transform duration-200"
                               :class="{ 'rotate-180 text-avocado-600': openItem === {{ $i }} }"
                               aria-hidden="true"></i>
                        </button>
                        <div x-show="openItem === {{ $i }}"
                             x-collapse
                             class="px-5 pb-5 text-sm text-darkbrown-600 leading-relaxed border-t border-wheat-100 pt-3">
                            {{ $faq['a'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

    </div>
</x-layouts.customer>
