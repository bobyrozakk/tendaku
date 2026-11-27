<x-layouts.customer :title="'Pusat Bantuan & Kebijakan - Tendaku.id'">
    {{-- Hero Banner --}}
    <div class="relative overflow-hidden rounded-3xl border border-wheat-200 bg-gradient-to-br from-wheat-100 via-[#FDFBF7] to-sunglow-100/70 px-6 py-10 sm:px-10 sm:py-14 shadow-tendaku-sm mb-8">
        <div class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-sunglow-300/30 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-24 left-1/4 h-64 w-64 rounded-full bg-avocado-200/30 blur-3xl" aria-hidden="true"></div>

        <div class="relative max-w-3xl">
            <nav class="flex items-center gap-2 text-xs font-semibold text-darkbrown-500 mb-4" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-avocado-700 transition-colors">Beranda</a>
                <span aria-hidden="true">/</span>
                <span class="text-darkbrown-800">Bantuan</span>
            </nav>

            <span class="inline-block rounded-full border border-avocado-300/80 bg-white/80 px-3.5 py-1 text-xs font-bold uppercase tracking-[.18em] text-avocado-800 backdrop-blur-sm shadow-sm">
                Pusat Bantuan &amp; Kebijakan
            </span>

            <h1 class="mt-3 font-heading text-3xl font-extrabold tracking-tight text-darkbrown-900 sm:text-4xl lg:text-5xl leading-tight">
                Ketentuan, Kebijakan &amp; Panduan Tendaku
            </h1>

            <p class="mt-3 text-base sm:text-lg leading-relaxed text-darkbrown-600 font-normal">
                Transparansi dan kenyamanan petualanganmu adalah prioritas kami. Pelajari syarat sewa, perlindungan privasi data, mekanisme jaminan KTP tanpa deposit, dan kebijakan pembatalan di sini.
            </p>
        </div>
    </div>

    {{-- Sticky Quick Nav Pills --}}
    <div class="sticky top-16 z-30 -mx-4 sm:mx-0 px-4 sm:px-0 py-3 mb-10 bg-[#FDFBF7]/95 backdrop-blur-md border-b border-wheat-200/80">
        <nav class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none" aria-label="Navigasi Bagian Bantuan">
            <a href="#syarat-ketentuan"
               class="inline-flex shrink-0 items-center px-4 py-2 rounded-full text-xs sm:text-sm font-semibold border border-wheat-300 bg-white text-darkbrown-800 hover:border-avocado-500 hover:text-avocado-800 hover:bg-avocado-50/50 transition-all shadow-sm">
                Syarat &amp; Ketentuan
            </a>
            <a href="#kebijakan-privasi"
               class="inline-flex shrink-0 items-center px-4 py-2 rounded-full text-xs sm:text-sm font-semibold border border-wheat-300 bg-white text-darkbrown-800 hover:border-avocado-500 hover:text-avocado-800 hover:bg-avocado-50/50 transition-all shadow-sm">
                Kebijakan Privasi
            </a>
            <a href="#deposit-pengembalian"
               class="inline-flex shrink-0 items-center px-4 py-2 rounded-full text-xs sm:text-sm font-semibold border border-wheat-300 bg-white text-darkbrown-800 hover:border-avocado-500 hover:text-avocado-800 hover:bg-avocado-50/50 transition-all shadow-sm">
                Deposit &amp; Pengembalian
            </a>
            <a href="#kebijakan-pembatalan"
               class="inline-flex shrink-0 items-center px-4 py-2 rounded-full text-xs sm:text-sm font-semibold border border-wheat-300 bg-white text-darkbrown-800 hover:border-avocado-500 hover:text-avocado-800 hover:bg-avocado-50/50 transition-all shadow-sm">
                Kebijakan Pembatalan
            </a>
            <a href="#panduan-penggunaan"
               class="inline-flex shrink-0 items-center px-4 py-2 rounded-full text-xs sm:text-sm font-semibold border border-wheat-300 bg-white text-darkbrown-800 hover:border-avocado-500 hover:text-avocado-800 hover:bg-avocado-50/50 transition-all shadow-sm">
                Panduan Penggunaan
            </a>
        </nav>
    </div>

    {{-- Content Sections --}}
    <div class="space-y-16 lg:space-y-24">

        {{-- 1. SYARAT & KETENTUAN --}}
        <section id="syarat-ketentuan" class="scroll-mt-36" aria-labelledby="syarat-title">
            <div class="mb-4">
                <span class="text-xs font-bold uppercase tracking-wider text-darkbrown-600">01 — Regulasi Platform</span>
                <h2 id="syarat-title" class="font-heading text-2xl sm:text-3xl font-extrabold text-darkbrown-900 mt-1">
                    Syarat &amp; Ketentuan Sewa
                </h2>
            </div>

            <p class="text-darkbrown-600 text-sm sm:text-base leading-relaxed max-w-3xl mb-8">
                Dengan melakukan pemesanan di Tendaku.id, penyewa menyatakan telah membaca, memahami, dan menyetujui seluruh ketentuan operasional berikut.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="rounded-2xl border border-wheat-200 bg-white p-6 shadow-sm">
                    <h3 class="font-heading text-lg font-bold text-darkbrown-900 mb-2">
                        1. Peran Tendaku.id &amp; Pengguna
                    </h3>
                    <p class="text-sm text-darkbrown-600 leading-relaxed">
                        Tendaku.id adalah platform perantara resmi yang mempertemukan penyewa dengan outlet vendor peralatan outdoor di Malang Raya. Alat disediakan dan diserahkan langsung oleh vendor terafiliasi dengan standar kelayakan yang diawasi.
                    </p>
                </div>

                <div class="rounded-2xl border border-wheat-200 bg-white p-6 shadow-sm">
                    <h3 class="font-heading text-lg font-bold text-darkbrown-900 mb-2">
                        2. Persyaratan &amp; Identitas Penyewa
                    </h3>
                    <p class="text-sm text-darkbrown-600 leading-relaxed">
                        Penyewa wajib berusia minimal 17 tahun dan memiliki KTP asli fisik yang masih berlaku. Nama pemesan pada akun Tendaku wajib sesuai dengan nama pada kartu identitas yang diserahkan sebagai jaminan sewa.
                    </p>
                </div>

                <div class="rounded-2xl border border-wheat-200 bg-white p-6 shadow-sm">
                    <h3 class="font-heading text-lg font-bold text-darkbrown-900 mb-2">
                        3. Perhitungan Durasi Sewa
                    </h3>
                    <p class="text-sm text-darkbrown-600 leading-relaxed">
                        Durasi sewa dihitung 24 jam per hari sewa dengan jadwal standar penyerahan dan pengembalian unit pukul 09.00 WIB. Pengembalian unit melebihi jadwal yang disepakati tanpa konfirmasi akan dikenai denda keterlambatan (overdue).
                    </p>
                </div>

                <div class="rounded-2xl border border-wheat-200 bg-white p-6 shadow-sm">
                    <h3 class="font-heading text-lg font-bold text-darkbrown-900 mb-2">
                        4. Tanggung Jawab Penggunaan Alat
                    </h3>
                    <p class="text-sm text-darkbrown-600 leading-relaxed">
                        Penyewa wajib menjaga keutuhan alat, tidak memindahtangankan barang kepada pihak ketiga tanpa izin, dan tidak menyalakan api unggun atau rokok di dalam tenda demi keselamatan bersama.
                    </p>
                </div>
            </div>
        </section>

        {{-- 2. KEBIJAKAN PRIVASI --}}
        <section id="kebijakan-privasi" class="scroll-mt-36" aria-labelledby="privasi-title">
            <div class="mb-4">
                <span class="text-xs font-bold uppercase tracking-wider text-avocado-700">02 — Perlindungan Data</span>
                <h2 id="privasi-title" class="font-heading text-2xl sm:text-3xl font-extrabold text-darkbrown-900 mt-1">
                    Kebijakan Privasi
                </h2>
            </div>

            <p class="text-darkbrown-600 text-sm sm:text-base leading-relaxed max-w-3xl mb-8">
                Kami sangat menghargai privasi Anda. Halaman ini menjelaskan bagaimana Tendaku mengumpulkan, menjaga, dan memperlakukan data pribadi pengguna secara aman.
            </p>

            <div class="space-y-4 max-w-4xl">
                <div class="p-6 rounded-2xl border border-wheat-200 bg-white">
                    <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-2">Data yang Kami Kumpulkan</h3>
                    <p class="text-sm text-darkbrown-600 leading-relaxed mb-3">
                        Kami hanya mengumpulkan data yang diperlukan untuk operasional persewaan, meliputi: nama lengkap, alamat email, nomor telepon/WhatsApp, alamat &amp; titik koordinat pengantaran, data riwayat sewa, serta foto identitas KTP sebagai arsip serah terima jaminan.
                    </p>
                    <p class="text-xs text-darkbrown-500 font-medium">
                        Jika Anda login menggunakan akun Google, kami hanya menerima informasi profil dasar (nama, email, foto profil) yang Anda izinkan.
                    </p>
                </div>

                <div class="p-6 rounded-2xl border border-wheat-200 bg-white">
                    <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-2">Keamanan Pembayaran &amp; Kerahasiaan Data</h3>
                    <p class="text-sm text-darkbrown-600 leading-relaxed mb-3">
                        Seluruh transaksi online diproses melalui payment gateway Midtrans berstandar keamanan PCI-DSS. Tendaku tidak pernah menyimpan nomor kartu kredit atau sandi perbankan pengguna pada server internal kami.
                    </p>
                    <p class="text-xs text-darkbrown-500 font-medium">
                        Tendaku berkomitmen untuk <strong>tidak pernah menjual atau menyewakan data pribadi Anda</strong> kepada pihak ketiga manapun.
                    </p>
                </div>

                <div class="p-6 rounded-2xl border border-wheat-200 bg-white">
                    <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-2">Hak Pengguna atas Data Pribadi</h3>
                    <p class="text-sm text-darkbrown-600 leading-relaxed">
                        Anda memiliki hak untuk melihat, memperbarui profil alamat, atau meminta penghapusan akun beserta riwayat identitas dengan menghubungi customer support di <a href="mailto:admintendaku@gmail.com" class="text-avocado-700 font-semibold underline">admintendaku@gmail.com</a> sepanjang tidak ada kewajiban sewa aktif.
                    </p>
                </div>
            </div>
        </section>

        {{-- 3. DEPOSIT & PENGEMBALIAN --}}
        <section id="deposit-pengembalian" class="scroll-mt-36" aria-labelledby="deposit-title">
            <div class="mb-4">
                <span class="text-xs font-bold uppercase tracking-wider text-sunglow-800">03 — Tanpa Deposit Uang</span>
                <h2 id="deposit-title" class="font-heading text-2xl sm:text-3xl font-extrabold text-darkbrown-900 mt-1">
                    Jaminan KTP &amp; Prosedur Pengembalian
                </h2>
            </div>

            <p class="text-darkbrown-600 text-sm sm:text-base leading-relaxed max-w-3xl mb-8">
                Tendaku tidak mewajibkan deposit uang tunai. Kami menggunakan sistem jaminan KTP asli fisik untuk mempermudah siapa saja menikmati keindahan alam outdoor.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="p-6 rounded-2xl border border-wheat-200 bg-white">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-avocado-100 text-avocado-800 font-bold text-sm mb-3">1</span>
                    <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-1.5">Penyerahan KTP Fisik</h3>
                    <p class="text-xs text-darkbrown-600 leading-relaxed">
                        KTP asli fisik diserahkan saat pengambilan alat di outlet vendor. Foto KTP dicatat pada sistem internal sebagai bukti sah serah-terima unit.
                    </p>
                </div>

                <div class="p-6 rounded-2xl border border-wheat-200 bg-white">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-sunglow-100 text-sunglow-800 font-bold text-sm mb-3">2</span>
                    <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-1.5">Penyimpanan Aman</h3>
                    <p class="text-xs text-darkbrown-600 leading-relaxed">
                        Dokumen identitas Anda disimpan secara aman di brankas outlet vendor selama masa sewa dan tidak akan digunakan untuk keperluan lain di luar jaminan.
                    </p>
                </div>

                <div class="p-6 rounded-2xl border border-wheat-200 bg-white">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-wheat-200 text-darkbrown-800 font-bold text-sm mb-3">3</span>
                    <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-1.5">KTP Kembali Utuh</h3>
                    <p class="text-xs text-darkbrown-600 leading-relaxed">
                        Saat alat dikembalikan dan inspeksi kondisi unit selesai, KTP fisik Anda diserahkan kembali secara utuh tanpa ada potongan biaya tambahan.
                    </p>
                </div>
            </div>

            <div class="p-6 rounded-2xl border border-wheat-200 bg-white max-w-4xl">
                <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-2">Penanganan Kerusakan atau Kehilangan Komponen</h3>
                <p class="text-sm text-darkbrown-600 leading-relaxed">
                    Apabila terdapat komponen yang rusak atau hilang (seperti pasak bengkok parah, frame patah, atau tas pembungkus hilang), kasir vendor akan menetapkan biaya ganti rugi wajar sesuai daftar harga suku cadang resmi vendor. Pembayaran dapat dilakukan tunai atau QRIS sebelum proses pengembalian dinyatakan selesai.
                </p>
            </div>
        </section>

        {{-- 4. KEBIJAKAN PEMBATALAN --}}
        <section id="kebijakan-pembatalan" class="scroll-mt-36" aria-labelledby="batal-title">
            <div class="mb-4">
                <span class="text-xs font-bold uppercase tracking-wider text-darkbrown-600">04 — Pengembalian &amp; Reschedule</span>
                <h2 id="batal-title" class="font-heading text-2xl sm:text-3xl font-extrabold text-darkbrown-900 mt-1">
                    Kebijakan Pembatalan &amp; Force Majeure
                </h2>
            </div>

            <p class="text-darkbrown-600 text-sm sm:text-base leading-relaxed max-w-3xl mb-8">
                Ketentuan pengajuan pembatalan sewa, perubahan tanggal (reschedule), dan kebijakan khusus bencana alam di kawasan Malang Raya.
            </p>

            <div class="space-y-4 max-w-4xl mb-8">
                <div class="p-6 rounded-2xl border border-wheat-200 bg-white">
                    <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-3">Batas Waktu Pembatalan oleh Penyewa</h3>
                    <ul class="text-sm text-darkbrown-600 space-y-2 list-disc list-inside">
                        <li><strong>Lebih dari 48 jam sebelum jadwal ambil:</strong> Pengembalian dana penuh (100%) dikurangi biaya pemrosesan gateway transaksi.</li>
                        <li><strong>24 – 48 jam sebelum jadwal ambil:</strong> Pengembalian dana 50% atau opsi ubah tanggal sewa (reschedule) gratis 1 kali.</li>
                        <li><strong>Kurang dari 24 jam / No-Show:</strong> Dana sewa tidak dapat dikembalikan karena unit telah di-hold dan diblokir dari calon penyewa lain.</li>
                    </ul>
                </div>

                <div class="p-6 rounded-2xl border border-wheat-200 bg-white">
                    <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-2">Klausul Khusus Force Majeure (Bencana Alam &amp; Cuaca Ekstrem)</h3>
                    <p class="text-sm text-darkbrown-600 leading-relaxed">
                        Jika trip Anda dibatalkan akibat force majeure resmi — seperti status erupsi gunung api (Gunung Semeru, Bromo), penutupan jalur pendakian oleh balai TNBTS/Perhutani, atau peringatan cuaca ekstrem BMKG Jawa Timur — Anda berhak mendapatkan <strong>100% full reschedule tanpa denda</strong> atau opsi refund sesuai ketentuan operasional.
                    </p>
                </div>
            </div>
        </section>

        {{-- 5. PANDUAN PENGGUNAAN --}}
        <section id="panduan-penggunaan" class="scroll-mt-36" aria-labelledby="panduan-guna-title">
            <div class="mb-4">
                <span class="text-xs font-bold uppercase tracking-wider text-avocado-700">05 — Tips &amp; Edukasi Lapangan</span>
                <h2 id="panduan-guna-title" class="font-heading text-2xl sm:text-3xl font-extrabold text-darkbrown-900 mt-1">
                    Panduan Penggunaan &amp; Etika Outdoor
                </h2>
            </div>

            <p class="text-darkbrown-600 text-sm sm:text-base leading-relaxed max-w-3xl mb-8">
                Panduan praktis merawat peralatan sewa dan etika penting saat berada di alam bebas agar petualangan tetap aman dan lestari.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl">
                <div class="p-6 rounded-2xl border border-wheat-200 bg-white">
                    <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-3">Cara Memasang Tenda yang Benar</h3>
                    <ul class="text-xs text-darkbrown-600 space-y-2 leading-relaxed list-disc list-inside">
                        <li>Pasang pasak pada sudut 45° berlawanan arah tarikan tenda agar cengkeraman tanah maksimal.</li>
                        <li>Kencangkan tali pancang (guyline) agar flysheet tidak menempel langsung pada inner saat hujan lebat.</li>
                        <li>Bersihkan area dari batu runcing dan ranting sebelum membentangkan footprint/matras.</li>
                    </ul>
                </div>

                <div class="p-6 rounded-2xl border border-wheat-200 bg-white">
                    <h3 class="font-heading text-base font-bold text-darkbrown-900 mb-3">Prinsip Leave No Trace</h3>
                    <ul class="text-xs text-darkbrown-600 space-y-2 leading-relaxed list-disc list-inside">
                        <li>Bawa turun semua sampah yang Anda bawa naik, termasuk puntung rokok dan bungkus makanan kecil.</li>
                        <li>Gunakan sabun ramah lingkungan atau cuci peralatan masak minimal 50 meter dari sumber air alami.</li>
                        <li>Hormati satwa liar dan pengunjung lain dengan tidak menyalakan musik speaker terlalu keras di camp.</li>
                    </ul>
                </div>
            </div>
        </section>

    </div>
</x-layouts.customer>
