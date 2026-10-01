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
