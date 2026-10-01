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

# Panduan Style CSS Tendaku

## Palet warna

| Warna | Hex | Variabel CSS | Class warna dasar |
| --- | --- | --- | --- |
| Wheat | `#F9E3B6` | `--color-wheat` | `bg-wheat-300` |
| Sunglow | `#FBCE6B` | `--color-sunglow` | `bg-sunglow-300` |
| Goldenrod | `#D5A007` | `--color-goldenrod` | `bg-goldenrod-500` |
| Avocado | `#6C8B08` | `--color-avocado` | `bg-avocado-500` |
| Dark brown | `#2B2202` | `--color-dark-brown` | `bg-darkbrown-800` |

Setiap warna memiliki shade `50` sampai `900`; `darkbrown` juga memiliki shade `950`. Gunakan prefix `bg-`, `text-`, atau `border-` sesuai kebutuhan, misalnya `text-avocado-800` dan `border-wheat-200`.

Alias semantik CSS tersedia sebagai `--brand-primary`, `--brand-accent`, `--brand-highlight`, `--brand-surface`, dan `--brand-dark`. Alias Tailwind yang setara tersedia melalui `bg-brand-primary`, `text-brand-accent`, dan `bg-brand-surface`. Warna dasar juga tersedia sebagai `bg-tendaku-avocado`, `bg-tendaku-wheat`, dan alias palet lainnya di konfigurasi.

Nilai warna di `app.css` dan `tailwind.config.js` didefinisikan terpisah. Saat mengubah palet brand, periksa keduanya agar tetap konsisten.

## Tipografi dan style dasar

- Teks utama memakai **Plus Jakarta Sans**; heading `h1`–`h6` memakai **Outfit**.
- Font dimuat melalui Google Fonts di `app.css`, dengan font fallback jika font eksternal tidak tersedia.
- Warna teks dasar adalah `#2B2202` dan latar halaman `#FDFBF7`.
- Seleksi teks memakai latar Sunglow dan teks Dark brown.

## Class komponen

### Tombol

| Class | Tampilan |
| --- | --- |
| `btn-tendaku-primary` | Hijau Avocado dengan teks putih. |
| `btn-tendaku-accent` | Sunglow dengan teks gelap. |
| `btn-tendaku-gold` | Goldenrod dengan teks putih. |
| `btn-tendaku-secondary` | Wheat dengan border lembut. |
| `btn-tendaku-dark` | Dark brown dengan teks Wheat. |
| `btn-tendaku-outline` | Border dan teks Avocado. |

Semua class tombol menyediakan state hover, active, dan focus sesuai definisinya di `app.css`.

```html
<button type="button" class="btn-tendaku-primary">Sewa sekarang</button>
<button type="button" class="btn-tendaku-outline">Lihat detail</button>
```

### Kartu

| Class | Tampilan |
| --- | --- |
| `card-tendaku` | Latar putih, border Wheat, dan shadow yang berubah saat hover. |
| `card-tendaku-warm` | Latar hangat `#FDFBF7` dengan border Wheat. |
| `card-tendaku-dark` | Latar Dark brown dan teks Wheat. |

Class kartu tidak menambahkan padding. Tambahkan utility seperti `p-6` sesuai isi kartu. Heading memiliki warna gelap bawaan; berikan warna terang secara eksplisit saat memakai kartu gelap.

```html
<article class="card-tendaku p-6">
    <h2 class="text-xl font-bold">Tenda camping</h2>
    <p class="mt-2 text-sm text-darkbrown-500">Kapasitas 4 orang.</p>
</article>
```

### Badge dan input

Badge tersedia melalui `badge-avocado`, `badge-goldenrod`, `badge-sunglow`, `badge-wheat`, dan `badge-dark`.

```html
<span class="badge-avocado">Tersedia</span>
<span class="badge-goldenrod">Pilihan populer</span>

<label for="destination" class="block text-sm font-medium">Tujuan camping</label>
<input id="destination" name="destination" type="text"
       class="input-tendaku mt-2" placeholder="Masukkan lokasi">
```

`input-tendaku` menyediakan lebar penuh, border Wheat, sudut membulat, serta border dan ring Avocado saat focus.

## Gradien, pola, dan shadow

| Class | Efek |
| --- | --- |
| `bg-gradient-tendaku` | Gradien hangat Wheat, Sunglow, dan emas. |
| `bg-gradient-outdoor` | Gradien Dark brown menuju hijau gelap. |
| `bg-gradient-avocado` | Gradien Avocado menuju hijau gelap. |
| `bg-gradient-gold` | Gradien Sunglow menuju Goldenrod. |
| `bg-camping-pattern` | Pola titik Wheat pada latar hangat. |

Shadow khusus: `shadow-tendaku-sm`, `shadow-tendaku`, `shadow-tendaku-lg`, `shadow-tendaku-glow`, dan `shadow-tendaku-avocado-glow`.

```html
<section class="bg-gradient-outdoor rounded-2xl p-6 text-wheat-100 shadow-tendaku-lg">
    <h2 class="text-2xl font-bold text-wheat-100">Siap untuk petualangan?</h2>
</section>
```

## Gunakan komponen Blade yang sudah tersedia

Untuk halaman aplikasi, gunakan komponen yang sudah ada agar style dan atribut konsisten:

```blade
<x-card title="Perlengkapan camping" variant="warm">
    <div class="flex flex-wrap items-center gap-3">
        <x-badge variant="avocado" :dot="true">Tersedia</x-badge>
        <x-primary-button type="button" variant="avocado" size="md">
            Sewa sekarang
        </x-primary-button>
    </div>
</x-card>

<x-input-label for="location" value="Lokasi" />
<x-text-input id="location" name="location" class="mt-2 w-full" />
```

- `x-primary-button`: variant `avocado`, `goldenrod`, `sunglow`, dan `dark`; ukuran `sm`, `md`, dan `lg`. Tipe tombol default adalah `submit`.
- `x-card`: variant `default`, `warm`, `dark`, dan `glass`; mendukung judul, subtitle, padding, serta slot `header`, `action`, dan `footer`.
- `x-badge`: variant `avocado`, `goldenrod`, `sunglow`, `wheat`, `dark`, dan `danger`; ukuran `sm`, `md`, dan `lg`; opsi titik melalui `:dot="true"`.
- `x-text-input`: mendukung atribut input dan opsi `:disabled="true"`.

Komponen Blade memiliki definisi utility sendiri; tampilannya tidak selalu persis sama dengan class CSS yang namanya serupa.

## Menjalankan aset

Dari direktori utama proyek, setelah dependency JavaScript terpasang:

```bash
npm run dev
```

Untuk membuat bundle produksi:

```bash
npm run build
```

Layout yang ada memuat aset melalui:

```blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

Jika perubahan style belum terlihat, pastikan server Vite berjalan atau jalankan build ulang.
