# 🏕️ TENDAKU - Platform Penyewaan Alat Camping & Outdoor Multi-Vendor

[![Laravel](https://img.shields.io/badge/Laravel-11%2F12-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Livewire](https://img.shields.io/badge/Livewire-3.x-4E5BA6?style=flat-square&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)

**Tendaku** adalah platform web penyewaan perlengkapan *outdoor* & camping berbasis **Multi-Tenant (Multi-Vendor)**. Platform ini memungkinkan penyedia jasa sewa perlengkapan outdoor (Vendor) untuk mengelola katalog produk, melacak unit fisik alat secara terperinci (serialized unit tracking), memproses reservasi sewa (pembayaran lunas), mengelola alur serah-terima dengan **Jaminan KTP Fisik & Verifikasi Foto Wajah**, serta menerima pembayaran terintegrasi via **Midtrans**.

---

## 📌 Fitur Utama Platform (Update Revisi)

### 🏢 **1. Multi-Tenancy (Isolasi Data Vendor)**
- Isolasi data otomatis per toko sewa berbasis `vendor_id` (`BelongsToVendor` Trait).
- Setiap vendor memiliki dashboard, katalog, unit barang, dan pengaturan rekening/gateway pembayaran secara mandiri.

### ⛺ **2. Manajemen Katalog & Unit Fisik (Serialized Tracking)**
- **Master Item:** Pengaturan tarif sewa per hari dan denda keterlambatan per hari *(Tanpa deposit uang tunai)*.
- **Item Unit:** Pengelolaan unit fisik barang individual (menggunakan kode unit / barcode), pencatatan harga pembelian, serta riwayat status unit (`available`, `rented`, `maintenance`, `lost`).

### 🪪 **3. Jaminan Fisik KTP & Foto Verifikasi (Tanpa Deposit Uang)**
- **Bayar Lunas di Awal:** Seluruh transaksi sewa dibayar lunas melalui Midtrans atau tunai.
- **Penahanan KTP Fisik saat Pickup:** Penyewa menyerahkan KTP fisik asli sebagai jaminan sewa saat serah-terima barang.
- **Foto Verifikasi KTP + Wajah:** Admin toko wajib mengambil foto penyewa memegang KTP fisiknya langsung melalui sistem untuk memastikan kesesuaian identitas.
- **Bukti Digital Tanda Terima Jaminan:** Foto dan bukti penahanan KTP tersimpan di sistem, sehingga penyewa & toko memiliki bukti sah digital penahanan jaminan KTP.
- **Pengembalian KTP saat Return:** KTP fisik diserahkan kembali saat barang dikembalikan dan status jaminan di-update menjadi `returned`.

### 🔄 **4. Alur Penyewaan & Inspeksi Barang (Pickup & Return Flow)**
- **Inspeksi Serah Terima:** Pencatatan kondisi barang sebelum sewa (`condition_before`) dan sesudah sewa (`condition_after`).
- Perhitungan otomatis denda keterlambatan (*late fee*) dan denda kerusakan (*damage fee*).

### ☀️ **5. Kalender Prediksi Cuaca (Weather Calendar Integration)**
- Fitur bagi pelanggan untuk mengecek prakiraan cuaca di lokasi camping tujuan pada tanggal reservasi yang dipilih.

---

## 📁 Struktur Direktori & Arsitektur Utama

```
tendaku/
├── app/
│   ├── Http/Controllers/
│   │   ├── Customer/             # Controller katalog & checkout pelanggan
│   │   ├── Vendor/               # Controller produk & manajemen rental vendor
│   │   └── Webhook/              # Webhook handler Midtrans
│   ├── Livewire/                 # Komponen interaktif Livewire 3
│   │   ├── Customer/             # Booking & Weather Calendar
│   │   └── Vendor/               # Item Unit Manager & Pickup/Return Flow (Upload Foto KTP)
│   ├── Models/                   # Eloquent Models (User, Vendor, MasterItem, ItemUnit, Rental, dll.)
│   ├── Services/                 # Layer Logika Bisnis
│   │   ├── AvailabilityService.php   # Cek ketersediaan unit barang per rentang tanggal
│   │   ├── RentalService.php         # Kalkulasi pelunasan, foto KTP jaminan, denda
│   │   ├── MidtransService.php       # Integrasi Snap Payment & Webhook
│   │   └── WeatherService.php        # Integrasi OpenWeather API
│   └── Traits/
│       └── BelongsToVendor.php       # Global scope isolasi multi-tenant vendor
├── database/migrations/          # Skema database berurutan
├── PRD.md                        # Product Requirement Document & Panduan Tim Lengkap
└── resources/views/              # Template Blade (Customer & Vendor UI)
```

---

## 📄 Dokumen Kebutuhan Produk (PRD)

Untuk informasi terperinci mengenai **Spesifikasi Produk, Skema Database, Peran Pengguna (Role Matrix), Lifecycle Transaksi, serta Standar Pengkodean Tim**, silakan baca dokumen resmi kami:
👉 **[PRD.md](file:///PRD.md)**

---

## 🛠️ Panduan Memulai Development (Quick Start)

### Prerequisites
- PHP 8.3+
- Composer 2.x
- Node.js 18+ & NPM
- Database MySQL / PostgreSQL

### Langkah Instalasi

1. **Clone Repository & Masuk ke Direktori Project:**
   ```bash
   git clone https://github.com/bobyrozakk/tendaku.git
   cd tendaku
   ```

2. **Install Dependency PHP & JavaScript:**
   ```bash
   composer install
   npm install
   ```

3. **Salin File Environment & Generate Application Key:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi Database di File `.env`:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=tendaku
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Jalankan Migrasi Database & Seeder:**
   ```bash
   php artisan migrate --seed
   ```

6. **Jalankan Server Development:**
   ```bash
   php artisan serve
   ```
   *Di terminal terpisah, jalankan bundling aset:*
   ```bash
   npm run dev
   ```

---

## 🧪 Pengujian & Code Formatting

- **Menjalankan Test Suite:**
  ```bash
  php artisan test
  ```

- **Format Kode PHP (Laravel Pint):**
  ```bash
  vendor/bin/pint --format agent
  ```

---

## 👥 Tim & Kontribusi

Proyek ini dikembangkan untuk penyewaan peralatan outdoor modern. Pastikan seluruh perubahan kode mengacu pada acuan arsitektur di [PRD.md](file:///PRD.md).
