<?php
// kata2 taro disini
return [

    'image' => 'images/tendaku-footer-forest.jpg',
    'logo' => 'images/Logo.svg',

    'brand' => [
        'name'        => 'Tendaku.id',
        'slogan'      => 'Teman perjalanan untuk setiap petualanganmu.',
        'description' => 'Sewa perlengkapan camping dengan mudah, praktis, dan terjangkau. '
                       . 'Kami siap menemani perjalanan outdoor-mu dengan perlengkapan berkualitas '
                       . 'agar pengalaman berkemah semakin nyaman dan menyenangkan.',
    ],

    'social' => [
        ['icon' => 'instagram', 'label' => 'Instagram', 'url' => '#'],
        ['icon' => 'tiktok',    'label' => 'TikTok',    'url' => '#'],
        ['icon' => 'whatsapp',  'label' => 'WhatsApp',  'url' => 'https://wa.me/6281234567890'],
    ],

    'columns' => [
        [
            'title' => 'Jelajahi',
            'links' => [
                ['label' => 'Beranda',           'url' => '/'],
                ['label' => 'Katalog Peralatan', 'url' => '/katalog'],
                ['label' => 'Cara Penyewaan',    'url' => '#'],
                ['label' => 'Tentang Kami',      'url' => '#'],
                ['label' => 'Hubungi Kami',      'url' => '#'],
            ],
        ],
        [
            'title' => 'Layanan',
            'links' => [
                ['label' => 'Sewa Tenda',            'url' => '/katalog'],
                ['label' => 'Sewa Matras',           'url' => '/katalog'],
                ['label' => 'Perlengkapan Camping',  'url' => '/katalog'],
                ['label' => 'Panduan Penyewaan',     'url' => '#'],
                ['label' => 'FAQ',                   'url' => '#'],
            ],
        ],
        [
            'title' => 'Bantuan',
            'links' => [
                ['label' => 'Syarat & Ketentuan',     'url' => '#'],
                ['label' => 'Kebijakan Privasi',      'url' => '#'],
                ['label' => 'Deposit & Pengembalian', 'url' => '#'],
                ['label' => 'Kebijakan Pembatalan',   'url' => '#'],
                ['label' => 'Panduan Penggunaan',     'url' => '#'],
            ],
        ],
    ],

    'contact' => [
        'title' => 'Hubungi Kami',
        'items' => [
            ['icon' => 'pin',   'text' => 'Jawa Tengah, Indonesia'],
            ['icon' => 'phone', 'text' => '0812-3456-7890',      'url' => 'tel:+6281234567890'],
            ['icon' => 'mail',  'text' => 'support@tendaku.id',  'url' => 'mailto:support@tendaku.id'],
            ['icon' => 'clock', 'text' => 'Jam Operasional',     'sub' => 'Setiap hari, 08.00 – 20.00 WIB'],
        ],
    ],

    'legal' => [
        ['label' => 'Syarat Penggunaan', 'url' => '#'],
        ['label' => 'Kebijakan Privasi', 'url' => '#'],
        ['label' => 'Syarat & Ketentuan', 'url' => '#'],
    ],
];
