<?php

// kata2 taro disini
return [

    'image' => 'images/tendaku-footer-forest.jpg',
    'logo' => 'images/Logo.svg',

    'brand' => [
        'name' => 'Tendaku.id',
        'slogan' => 'Teman perjalanan untuk setiap petualanganmu.',
        'description' => 'Sewa perlengkapan camping dengan mudah, praktis, dan terjangkau. '
                       .'Kami siap menemani perjalanan outdoor-mu dengan perlengkapan berkualitas '
                       .'agar pengalaman berkemah semakin nyaman dan menyenangkan.',
    ],

    'social' => [
        ['icon' => 'instagram', 'label' => 'Instagram', 'url' => 'https://instagram.com'],
        ['icon' => 'tiktok',    'label' => 'TikTok',    'url' => 'https://tiktok.com'],
        ['icon' => 'whatsapp',  'label' => 'WhatsApp',  'url' => 'https://wa.me/6281234567890'],
    ],

    'columns' => [
        [
            'title' => 'Jelajahi',
            'links' => [
                ['label' => 'Beranda',           'url' => '/'],
                ['label' => 'Katalog Peralatan', 'url' => '/katalog'],
                ['label' => 'Lacak Pesanan',     'url' => '/status-sewa'],
                ['label' => 'Cara Penyewaan',    'modal' => 'how-to-rent'],
            ],
        ],
        [
            'title' => 'Informasi & Bantuan',
            'links' => [
                ['label' => 'Sewa Tenda',                  'url' => '/katalog?search=tenda'],
                ['label' => 'Perlengkapan Camping',        'url' => '/katalog'],
                ['label' => 'Jaminan KTP & Pengembalian',  'modal' => 'collateral'],
                ['label' => 'Kebijakan Pembatalan',        'modal' => 'cancellation'],
                ['label' => 'FAQ',                         'modal' => 'faq'],
            ],
        ],
    ],

    'contact' => [
        'title' => 'Hubungi Kami',
        'items' => [
            ['icon' => 'pin',   'text' => 'Jawa Timur, Indonesia'],
            ['icon' => 'phone', 'text' => '081234567890',      'url' => 'tel:+6281234567890'],
            ['icon' => 'mail',  'text' => 'gotendaku@tendaku.com',  'url' => 'mailto:gotendaku@gmail.com'],
            ['icon' => 'clock', 'text' => 'Jam Operasional',     'sub' => 'Setiap hari, 08.00 – 20.00 WIB'],
        ],
    ],

    'legal' => [
        ['label' => 'Syarat & Ketentuan', 'modal' => 'terms'],
        ['label' => 'Kebijakan Privasi',  'modal' => 'privacy'],
    ],

    'documents' => [

        'how-to-rent' => [
            'title' => 'Cara Penyewaan',
            'intro' => 'Menyewa perlengkapan di Tendaku.id hanya butuh lima langkah.',
            'sections' => [
                ['heading' => '1. Pilih alat dan tanggal', 'body' => ['Buka Katalog Peralatan, pilih alat, lalu tentukan tanggal sewa. Stok yang tampil sudah disesuaikan dengan tanggal yang kamu pilih.']],
                ['heading' => '2. Isi data pemesanan', 'body' => ['Masukkan alat ke keranjang, lengkapi data pemesanan, lalu pilih pengambilan di outlet vendor atau pengantaran bila vendor menyediakannya.']],
                ['heading' => '3. Bayar online', 'body' => ['Bayar DP 50% atau lunas melalui virtual account bank atau QRIS. Pembayaran harus selesai dalam 60 menit, jika tidak pesanan kedaluwarsa otomatis.']],
                ['heading' => '4. Ambil alat', 'body' => ['Datang ke outlet vendor pada jadwal pengambilan (bawaan pukul 09.00) dan serahkan KTP asli sebagai jaminan. Sisa tagihan, jika ada, dilunasi saat itu.']],
                ['heading' => '5. Kembalikan alat', 'body' => ['Kembalikan alat sesuai jadwal (bawaan pukul 09.00). Petugas memeriksa kondisi alat, lalu KTP dikembalikan dan pesanan berstatus Selesai.']],
                ['heading' => 'Pantau pesananmu', 'body' => ['Gunakan menu Lacak Pesanan dan kode pesanan untuk melihat perkembangan pesanan kapan saja.']],
            ],
        ],

        'faq' => [
            'title' => 'Pertanyaan Umum (FAQ)',
            'intro' => 'Jawaban singkat untuk pertanyaan yang paling sering diajukan.',
            'sections' => [
                ['heading' => 'Apakah harus membayar deposit uang?', 'body' => ['Tidak. Tendaku memakai jaminan KTP asli sebagai pengganti deposit uang.']],
                ['heading' => 'Metode pembayaran apa yang tersedia?', 'body' => ['Virtual account BCA, BNI, BRI, Permata, CIMB, dan QRIS.']],
                ['heading' => 'Berapa lama batas waktu pembayaran?', 'body' => ['60 menit sejak pesanan dibuat. Setelah itu pesanan berstatus Kedaluwarsa.']],
                ['heading' => 'Bagaimana cara melihat status pesanan?', 'body' => ['Buka menu Lacak Pesanan, lalu masukkan kode pesanan dari struk atau halaman pemesanan.']],
                ['heading' => 'Apakah pesanan bisa dibatalkan?', 'body' => ['Bisa, selama statusnya Menunggu Pembayaran atau Dikonfirmasi. Rinciannya ada di Kebijakan Pembatalan.']],
                ['heading' => 'Bagaimana jika alat rusak atau hilang?', 'body' => ['Kondisi alat diperiksa petugas vendor saat pengembalian. Denda untuk alat rusak atau hilang ditetapkan saat pemeriksaan dan dilunasi sebelum pengembalian selesai.']],
                ['heading' => 'Bagaimana menghubungi kami?', 'body' => ['Lewat WhatsApp, telepon, atau email yang tercantum di bagian Hubungi Kami pada footer.']],
            ],
        ],

        'terms' => [
            'title' => 'Syarat & Ketentuan',
            'intro' => 'Dengan menggunakan Tendaku.id, kamu menyetujui ketentuan berikut.',
            'sections' => [
                ['heading' => '1. Peran Tendaku.id', 'body' => ['Tendaku.id adalah platform yang mempertemukan penyewa dengan vendor penyedia perlengkapan camping. Alat disediakan dan diserahkan oleh vendor.']],
                ['heading' => '2. Akun dan data', 'body' => ['Data yang kamu berikan harus benar dan dapat dihubungi. Kamu bertanggung jawab atas keamanan akun dan seluruh aktivitas di dalamnya.']],
                ['heading' => '3. Pemesanan dan pembayaran', 'body' => ['Pesanan sah setelah pembayaran diterima. Pembayaran dilakukan melalui virtual account atau QRIS dalam 60 menit, dan pesanan yang tidak dibayar akan kedaluwarsa.']],
                ['heading' => '4. Jaminan KTP', 'body' => ['Penyewa menyerahkan KTP asli sebagai jaminan saat pengambilan alat. Ketentuan lengkapnya ada di Jaminan KTP & Pengembalian.']],
                ['heading' => '5. Penggunaan alat', 'body' => ['Alat dipakai sewajarnya dan dikembalikan sesuai jadwal dalam kondisi yang sama seperti saat diterima.']],
                ['heading' => '6. Kerusakan, kehilangan, dan denda', 'body' => ['Alat yang rusak atau hilang, serta keterlambatan pengembalian, dapat dikenai denda yang ditetapkan saat pemeriksaan oleh petugas vendor.']],
                ['heading' => '7. Pembatalan', 'body' => ['Pembatalan mengikuti Kebijakan Pembatalan yang berlaku.']],
                ['heading' => '8. Perubahan ketentuan', 'body' => ['Ketentuan dapat diperbarui sewaktu-waktu. Versi terbaru selalu tersedia di footer situs ini.']],
                ['heading' => '9. Pertanyaan', 'body' => ['Hubungi gotendaku@gmail.com untuk pertanyaan tentang ketentuan ini.']],
            ],
        ],

        'privacy' => [
            'title' => 'Kebijakan Privasi',
            'intro' => 'Kami menjaga data pribadimu dan hanya memakainya untuk menjalankan layanan sewa.',
            'sections' => [
                ['heading' => 'Data yang kami kumpulkan', 'body' => ['Nama, email, nomor telepon, alamat dan titik lokasi pengantaran, data pesanan dan pembayaran, serta foto KTP jaminan saat pengambilan alat. Jika masuk dengan Google, kami menerima data dasar akun Google yang kamu izinkan.']],
                ['heading' => 'Cara kami menggunakan data', 'body' => ['Untuk memproses pesanan, pembayaran, pengantaran, pengingat pengembalian, serta menanggapi pertanyaan dan keluhan.']],
                ['heading' => 'Berbagi data', 'body' => ['Data pesanan dibagikan kepada vendor yang menyediakan alat dan kepada penyedia pembayaran (Midtrans) sebatas yang dibutuhkan untuk transaksi. Kami tidak menjual data pribadimu.']],
                ['heading' => 'Keamanan', 'body' => ['Foto KTP jaminan hanya dapat dilihat oleh pihak yang berhak atas pesanan tersebut.']],
                ['heading' => 'Hak kamu', 'body' => ['Kamu dapat meminta akses, perbaikan, atau penghapusan data melalui gotendaku@gmail.com.']],
            ],
        ],

        'collateral' => [
            'title' => 'Jaminan KTP & Pengembalian',
            'intro' => 'Tendaku tidak meminta deposit uang. Sebagai gantinya, penyewa menyerahkan KTP asli.',
            'sections' => [
                ['heading' => 'Penyerahan', 'body' => ['KTP asli diserahkan saat pengambilan alat di outlet vendor. Foto KTP dapat dicatat sebagai bukti serah terima.']],
                ['heading' => 'Penyimpanan', 'body' => ['KTP disimpan oleh vendor selama masa sewa dan tidak boleh dipakai untuk keperluan lain.']],
                ['heading' => 'Pengembalian KTP', 'body' => ['KTP dikembalikan saat alat dikembalikan dan pemeriksaan kondisi selesai.']],
                ['heading' => 'Jika ada denda', 'body' => ['Apabila alat rusak atau hilang, denda dibayar tunai atau QRIS saat pengembalian sebelum proses pengembalian dinyatakan selesai.']],
            ],
        ],

        'cancellation' => [
            'title' => 'Kebijakan Pembatalan',
            'intro' => 'Ketentuan pembatalan pesanan di Tendaku.id.',
            'sections' => [
                ['heading' => 'Kapan bisa dibatalkan', 'body' => ['Pesanan dapat dibatalkan selama berstatus Menunggu Pembayaran atau Dikonfirmasi. Pesanan yang sudah Sedang Disewa, Dikembalikan, atau Selesai tidak dapat dibatalkan.']],
                ['heading' => 'Cara membatalkan', 'body' => ['Buka halaman Lacak Pesanan, pilih alasan pembatalan, lalu centang persetujuan.']],
                ['heading' => 'Tagihan belum dibayar', 'body' => ['Tagihan yang masih menunggu pembayaran otomatis dibatalkan bersama pesanannya.']],
                ['heading' => 'Pesanan kedaluwarsa', 'body' => ['Pesanan yang tidak dibayar dalam 60 menit kedaluwarsa otomatis.']],
                ['heading' => 'Pembayaran yang sudah masuk', 'body' => ['Pembayaran yang sudah diterima tidak dikembalikan otomatis saat pembatalan. Hubungi kami dengan kode pesanan untuk proses pengembalian dana.']],
            ],
        ],
    ],
];
