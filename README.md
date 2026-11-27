# 🏕️ TENDAKU - Platform Penyewaan Alat Camping & Outdoor Multi-Vendor

[![Laravel](https://img.shields.io/badge/Laravel-11%2F12-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Livewire](https://img.shields.io/badge/Livewire-3.x-4E5BA6?style=flat-square&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)

**Tendaku** adalah platform web penyewaan perlengkapan _outdoor_ & camping berbasis **Multi-Tenant (Multi-Vendor)**. Platform ini memungkinkan penyedia jasa sewa perlengkapan outdoor (Vendor) untuk mengelola katalog produk, melacak unit fisik alat secara terperinci (serialized unit tracking), memproses reservasi sewa (pembayaran lunas), mengelola alur serah-terima dengan **Jaminan KTP Fisik & Verifikasi Foto Wajah**, serta menerima pembayaran terintegrasi via **Midtrans**.

---

## 📌 Fitur Utama

### 🏢 1. Multi-Tenancy (Isolasi Data Vendor)

- Isolasi data otomatis per toko sewa berbasis `vendor_id` (`BelongsToVendor` Trait).
- Setiap vendor memiliki dashboard, katalog, unit barang, dan pengaturan rekening/gateway pembayaran secara mandiri.

### ⛺ 2. Manajemen Katalog & Unit Fisik (Serialized Tracking)

- **Master Item:** Pengaturan tarif sewa per hari dan denda keterlambatan per hari _(tanpa deposit uang tunai)_.
- **Item Unit:** Pengelolaan unit fisik barang individual (kode unit / barcode), pencatatan harga pembelian, serta riwayat status unit (`available`, `rented`, `maintenance`, `lost`).

### 🪪 3. Jaminan Fisik KTP & Foto Verifikasi (Tanpa Deposit Uang)

- **Bayar lunas di awal** melalui Midtrans atau tunai.
- **Penahanan KTP fisik saat pickup** sebagai jaminan sewa.
- **Foto verifikasi KTP + wajah** diambil admin toko langsung melalui sistem.
- **Bukti digital tanda terima jaminan** tersimpan di sistem untuk penyewa dan toko.
- **Pengembalian KTP saat return**, status jaminan di-update menjadi `returned`.

### 🔄 4. Alur Penyewaan & Inspeksi Barang (Pickup & Return Flow)

- Pencatatan kondisi barang sebelum sewa (`condition_before`) dan sesudah sewa (`condition_after`).
- Perhitungan otomatis denda keterlambatan (_late fee_) dan denda kerusakan (_damage fee_).

### ☀️ 5. Kalender Prediksi Cuaca

- Pelanggan dapat mengecek prakiraan cuaca di lokasi camping tujuan pada tanggal reservasi yang dipilih.

---

## 📁 Struktur Direktori

```
tendaku/
├── app/
│   ├── Http/Controllers/
│   │   ├── Customer/             # Katalog & checkout pelanggan
│   │   ├── Vendor/               # Produk & manajemen rental vendor
│   │   └── Webhook/              # Webhook handler Midtrans
│   ├── Livewire/                 # Komponen interaktif Livewire 3
│   │   ├── Customer/             # Booking & Weather Calendar
│   │   └── Vendor/               # Item Unit Manager & Pickup/Return Flow
│   ├── Models/                   # Eloquent Models
│   ├── Services/                 # Layer logika bisnis
│   │   ├── AvailabilityService.php   # Ketersediaan unit per rentang tanggal
│   │   ├── RentalService.php         # Pelunasan, foto KTP jaminan, denda
│   │   ├── MidtransService.php       # Snap Payment & Webhook
│   │   └── WeatherService.php        # OpenWeather API
│   └── Traits/
│       └── BelongsToVendor.php       # Global scope multi-tenant
├── database/migrations/
└── resources/views/              # Template Blade (Customer & Vendor)
```

---

## 📄 Dokumentasi Lain

- **[PRD.md](PRD.md)**: spesifikasi produk, skema database, role matrix, lifecycle transaksi, dan standar pengkodean tim.
- **[DESIGN.md](DESIGN.md)**: panduan style, palet warna, dan komponen UI.

---

## 🛠️ Quick Start

### Prerequisites

- PHP 8.3+
- Composer 2.x
- Node.js 18+ & NPM
- Database MySQL / PostgreSQL

### Instalasi

1. **Clone repository:**

    ```bash
    git clone https://github.com/bobyrozakk/tendaku.git
    cd tendaku
    ```

2. **Install dependency:**

    ```bash
    composer install
    npm install
    ```

3. **Salin `.env` & generate key:**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4. **Atur database di `.env`** (contoh MySQL):

    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=tendaku
    DB_USERNAME=root
    DB_PASSWORD=
    ```

5. **Migrasi & seeder:**

    ```bash
    php artisan migrate --seed
    ```

6. **Jalankan server:**

    ```bash
    php artisan serve
    ```

    Di terminal terpisah, jalankan aset:

    ```bash
    npm run dev
    ```

---

## 🧪 Pengujian & Code Formatting

```bash
php artisan test          # menjalankan test suite
vendor/bin/pint           # format kode PHP (Laravel Pint)
```

---

## 🚀 Catatan Production

```bash
composer install --no-dev --optimize-autoloader
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
```

Gunakan `APP_ENV=production` dan `APP_DEBUG=false`.

---

## 👥 Tim & Kontribusi

Tendaku dikembangkan bersama oleh Tim PBL Tendaku (SIB-3E), Politeknik Negeri Malang. Seluruh anggota tim adalah kontributor dan pemilik bersama proyek ini.

| Nama                     | Peran           |
| ------------------------ | --------------- |
| Boby Rozak Saputra       | Project Manager |
| Anandya Almas Febyantoro | Developer       |
| Andini Tribuana          | Developer       |
| Arsya Fikri Sabilillah   | Developer       |
| Bunga Aulia Sari         | Developer       |

Daftar kontributor lengkap beserta riwayat commit ada di halaman [Contributors](https://github.com/bobyrozakk/tendaku/graphs/contributors).

### Panduan Kontribusi

- Buat branch dari `develop`, lalu ajukan perubahan lewat Pull Request. Jangan push langsung ke `main`.
- Gunakan nama dan email Git yang sama dengan akun GitHub masing-masing agar kontribusi tercatat.
- Ikuti acuan arsitektur di [PRD.md](PRD.md) dan format kode dengan `vendor/bin/pint`.

### Kepemilikan & Lisensi

Hak cipta © 2026 Tim Tendaku. Penggunaan bagian proyek ini untuk portofolio pribadi diperbolehkan selama mencantumkan nama kontributornya. Detail lisensi ada di file [LICENSE](LICENSE).
