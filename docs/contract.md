# 📜 KONTRAK INTEGRASI & ANTAR ANGGOTA — TENDAKU

**Proyek:** TENDAKU — Sistem Informasi Persewaan Tenda & Perlengkapan Acara (Multi-Tenant)  
**Penjaga Dokumen:** Boby (PM · Super Admin · QA Lead)  
**Versi:** 1.4.0 (Fase 2 Notifikasi WhatsApp + Fase 3 WeatherService + UI Weather Calendar + Checkout capture nomor WA)  
**Terakhir Diperbarui:** 2026-10-21  

> Dokumen ini adalah acuan resmi integrasi antar modul dan pembagian tanggung jawab teknis antar anggota tim PBL SIB-3E. Perubahan hanya diperbolehkan melalui koordinasi dan kesepakatan tim.

---

## 1. TABEL KONTRAK MODUL & SERVICE

Sebagian besar "API" antar anggota berupa method di class Service PHP / Livewire component, bukan endpoint HTTP publik. Hanya terdapat 2 API berbasis HTTP: Webhook Midtrans dan Endpoint Pelunasan Denda QRIS.

| Kontrak / Service | Pemilik | Pemakai | Input / Parameter | Output / Return | Keterangan |
|---|---|---|---|---|---|
| `AvailabilityService::checkAvailability()` | Byan | Andini (Checkout), Kasir | `int $masterItemId, string $startDate, string $endDate` | `int` (jumlah unit tersedia) | Menghitung unit fisik yang tersedia (bukan maintenance, bukan rusak, belum terbooking). |
| `RentalService::createBooking()` | Byan | Andini (Checkout) | `array $data` (customer/guest, dates, items) | `Rental` (instance model) | Validasi stok, snapshot tarif harian, buat data booking dengan status awal `pending`. |
| Livewire `Kasir\Pickup` | Bunga | UI Kasir | `string $bookingCode`, assignment barcode unit, upload foto KTP | UI / `ktp_collateral_status = held` | Serah terima fisik barang sewa ke pelanggan di outlet toko vendor. |
| Livewire `Kasir\ReturnFlow` | Bunga | UI Kasir, Byan (info denda) | `string $bookingCode`, kondisi unit sesudah, kalkulasi denda | Struk pelunasan denda, status `completed` | Pengecekan kondisi fisik unit, denda keterlambatan/kerusakan, serah terima kembali jaminan KTP (`returned`). |
| `MidtransService::createSnapToken()` | Andini | Andini (Checkout) | `Rental $rental` | `string` (Snap Token) | Generate token Snap gateway Midtrans untuk transaksi pembayaran. |
| Webhook Midtrans | Andini | Gateway Midtrans | HTTP POST JSON payload Midtrans | HTTP 200 OK | Tangani settlement, expiry, failure pembayaran secara idempoten. |
| Endpoint Pelunasan Denda | Andini | Bunga (Modal QRIS) | `Rental $rental, float $amount` | Snap token / URL QRIS | Pembayaran denda keterlambatan/kerusakan langsung di kasir via QRIS. |
| Role & Guard (`HasRoles`, `EnsureUserHasRole`) | Andini | Semua | Role user: `super_admin`, `vendor_owner`, `vendor_admin`, `customer` | Boolean / Middleware check | RBAC & otorisasi hak akses route. |
| Trait `BelongsToVendor` | **Boby** | Semua | Kolom `vendor_id` pada model | Query scope otomatis + auto vendor_id creation | Scoping multi-tenant isolasi data antar vendor, bypass otomatis untuk Super Admin & `CurrentVendor::withoutScoping()`. |
| `CurrentVendor` Service & Middleware | **Boby** | Boby, Queue Job, Test | `int $vendorId`, callable callback | Active vendor context / bypass | Singleton context management vendor aktif untuk web request, background job, dan PHPUnit tests. |
| `WeatherService::forecast()` | **Boby** | Andini (UI WeatherCalendar) | `string $spot, Carbon $start, Carbon $end` | `array` (JSON terstruktur, lihat §7.B) | Forecast cuaca spot camping Open-Meteo & deteksi cuaca ekstrem. Tidak pernah melempar exception — gagal → `error = true` + `days = []`. |
| `WeatherService::availableSpots()` | **Boby** | Andini (UI WeatherCalendar) | — | `array<string, array{label, latitude, longitude}>` | Daftar spot yang tersedia beserta koordinatnya (dari `config/weather.php`). |
| `WeatherService::forget()` | **Boby** | Andini (tombol "Perbarui" pada UI) | `string $spot, Carbon $start, Carbon $end` | `void` | Membuang cache forecast untuk spot + rentang tanggal tertentu (dipakai tombol refresh agar tidak menunggu TTL 30 menit). |
| Livewire `Customer\WeatherCalendar` | **Andini** *(diimplementasikan oleh Boby — label `cross-module`)* | Halaman `/cuaca` | `string $spot`, `string $days` (URL: `?spot=`, `?periode=`) | UI kalender cuaca + banner ekstrem | Menggunakan `WeatherService::forecast()`; gagal → banner error, halaman tetap jalan. |
| `WeatherFormatter::label()` | **Boby** | Andini (badge cuaca) | `int $weatherCode` (WMO) | `string` (Bahasa Indonesia) | Contoh: `63` → `"Hujan sedang"`, `95` → `"Badai petir"`. |
| `WhatsAppService::send()` | **Boby** | Job Reminder (Boby) | `string $phone, string $message` | `bool` | Kirim notifikasi WA (Fonnte) secara aman, normalisasi nomor (+62/08), tidak melempar exception fatal. |
| `PhoneNumberNormalizer::normalize()` | **Boby** | Fonnte (Boby), Tim | `?string $phone` | `?string` (`62xxx` / `null` bila tidak valid) | Ubah `08xxx` / `+62xxx` / `8xxx` → `62xxx`. Panjang valid 10–15 digit. |
| `ReturnReminderService::pendingReminders()` | **Boby** | Command Reminder (Boby) | `?Carbon $now, bool $includeNotified` | `Collection` item `{rental_id, vendor_id, type, return_date}` | Daftar pengingat H-1 & hari-H untuk rental `picked_up`. Lintas vendor → wajib `withoutGlobalScope('vendor')`. |
| `ReturnReminderService::sendReminderForRental()` | **Boby** | Job `SendReturnReminder` (Boby) | `int $rentalId, string $type, bool $force` | `bool` (terkirim) | Idempoten: satu rental + satu tipe pengingat = satu kiriman. Dicatat di tabel `notifications`. Nomor tujuan: profil pelanggan bila login, `rentals.guest_phone` bila guest (lihat §4.F). |
| Job `SendReturnReminder` | **Boby** | Scheduler harian (Boby) | `int $rentalId, int $vendorId, string $type, bool $force` | — | Job queue **wajib membawa `vendor_id`** dan dibungkus `CurrentVendor::runAs()` (jebakan queue di Fase 0). |
| Command `rentals:send-return-reminders` | **Boby** | Scheduler harian (Boby) | `--force`, `--dry-run` | exit code 0 | Dipanggil scheduler harian pada `09:00 Asia/Jakarta` (`REMINDER_HOUR`). `--dry-run` tidak mengirim apa pun. |
| Status & Kolom Pembayaran | Bunga | Semua | Schema `rentals`, `payments` | Matriks status & log transaksi | Konsistensi status pembayaran dan pelunasan. |
| `CheckoutController::store()` | Andini *(diimplementasikan oleh Boby — label `cross-module`)* | Alur checkout customer | `guest_name`, `guest_phone`, `guest_id_number` (guest), `pickup_method`, `payment_channel` | `Rental` (dibuat lewat `RentalService::createBooking()`) | Checkout kini benar-benar membuat record `rental` dari keranjang session `booking_cart` dan mengarahkan ke halaman status booking. Nomor WA guest disimpan di `rentals.guest_phone` untuk pengingat (§4.F). |

---

## 2. PANDUAN PENGGUNAAN TRAIT `BelongsToVendor` & `CurrentVendor`

### A. Cara Memasang Trait di Model Baru
Setiap model Eloquent yang memiliki kolom `vendor_id` **wajib** menggunakan trait `App\Traits\BelongsToVendor`:

```php
namespace App\Models;

use App\Traits\BelongsToVendor;
use Illuminate\Database\Eloquent\Model;

class MasterItem extends Model
{
    use BelongsToVendor;

    protected $fillable = [
        'vendor_id',
        'name',
        // ...
    ];
}
```

### B. Daftar Model yang Wajib Memakai `BelongsToVendor`
1. `App\Models\ItemCategory`
2. `App\Models\MasterItem`
3. `App\Models\ItemUnit`
4. `App\Models\Rental`
5. `App\Models\Payment`
6. `App\Models\NotificationLog`

*(Catatan: `RentalDetail` tidak memakai trait ini karena tidak memiliki kolom `vendor_id`; relasi diakses melalui model `Rental`).*

### C. Perilaku Otomatis Trait
1. **Query Scoping:** Setiap query model otomatis ditambahkan klausul `where vendor_id = <vendor_aktif>`.
2. **Auto Fill saat Create:** Saat memanggil `Model::create([...])` tanpa menyertakan `vendor_id`, kolom `vendor_id` otomatis diisi dari vendor yang sedang aktif.
3. **Bypass Super Admin:** Jika pengguna yang login adalah `super_admin`, global scope otomatis dilewati (Super Admin dapat melihat data seluruh vendor).
4. **Isolasi 404 (Bukan 403):** Jika Vendor A mencoba mengakses data milik Vendor B melalui route model binding (misalnya `/vendor/products/{id}`), sistem akan langsung menghasilkan response **404 Not Found** (tidak membocorkan keberadaan ID milik vendor lain).

### D. Helper Query
Jika Anda membutuhkan data lintas vendor atau data vendor tertentu:
```php
// 1. Lewati scope untuk vendor tertentu (khusus Super Admin / internal service)
$items = MasterItem::forVendor($vendorId)->get();

// 2. Lewati scope sepenuhnya (melihat seluruh vendor)
$allItems = MasterItem::allVendors()->get();
// Atau gunakan builder bawaan:
$allItems = MasterItem::withoutGlobalScope('vendor')->get();
```

### E. Penanganan Titik Buta (Blind Spots)
1. **Background Job / Queue:**
   Worker queue tidak memiliki sesi login pengguna (`Auth::user()` bernilai `null`). Jika job melakukan operasi spesifik vendor, bawa `vendor_id` di payload dan gunakan `CurrentVendor::runAs()`:
   ```php
   CurrentVendor::runAs($this->vendorId, function () {
       // Semua query di sini otomatis ter-scope ke $this->vendorId
       $rentals = Rental::all();
   });
   ```
2. **Database Seeder:**
   Seeder yang mengisi data antar vendor secara massal dapat menggunakan `withoutGlobalScope('vendor')` atau dibungkus dalam:
   ```php
   CurrentVendor::withoutScoping(function () {
       // Jalankan seeder lintas vendor tanpa terblokir scope
   });
   ```
3. **Pengujian (Feature Tests):**
   Gunakan helper bawaan `TestCase`:
   ```php
   $this->actingAsVendor($vendorOwner);
   // atau
   $this->actingAsVendor($vendorInstance, 'vendor_admin');
   ```

---

## 3. PADANAN STATUS RENTAL & ATURAN GMV

### A. Padanan Status Transaksi
Berdasarkan standardisasi ERD dan kode modul:
- `pending_payment` = `pending` (menunggu pembayaran / verifikasi)
- `paid` = `confirmed` (pembayaran telah berhasil, pesanan terkonfirmasi, menunggu jadwal pickup)
- `picked_up` = barang telah diserahterimakan ke penyewa, KTP ditahan di outlet
- `completed` = barang telah dikembalikan, denda (bila ada) lunas, KTP diserahkan kembali
- `cancelled` = dibatalkan sebelum pickup
- `expired` = pesanan kadaluarsa karena tidak dibayar hingga batas waktu `expires_at`

### B. Rumus & Aturan GMV (Gross Merchandise Value)
Daftar status transaksi yang dihitung ke dalam GMV Super Admin dan Vendor didefinisikan secara seragam:
- **Status yang Dihitung:** `confirmed`, `picked_up`, `completed`.
- **Status yang DIKECUALIKAN:** `pending`, `cancelled`, `expired`.
- **Nilai GMV:** Diambil dari nilai transaksi `final_total` (atau `grand_total` + `total_late_fee` + `total_damage_fee`).

---

## 4. KONTRAK NOTIFIKASI WHATSAPP (FASE 2)

### A. Kanal Notifikasi
Kanal aktif diatur lewat **config** (`config/notifications.php` → `NOTIFICATION_CHANNEL`), **bukan** dengan menghapus kode WhatsApp.

| Kanal | Perilaku | Keterangan |
|---|---|---|
| `whatsapp` (default) | Pesan dikirim ke Fonnte API (`POST https://api.fonnte.com/send`). | Butuh `FONNTE_TOKEN` pada `.env`. |
| `in_app` | Pesan hanya dicatat di tabel `notifications` (`channel = in_app`, `status = sent`) untuk ditampilkan di aplikasi. | Tanpa panggilan API eksternal. |
| `email` | Pesan dikirim via mailer Laravel (`Mail::raw()`), lalu dicatat di tabel `notifications` (`channel = email`). | Butuh `MAIL_MAILER` yang benar (bagian Andini). |

> kolom `channel` pada tabel `notifications` bertipe enum `('whatsapp', 'in_app', 'email')`. Nilai di luar itu **tidak boleh** dipakai.

### B. Tipe Notifikasi & Idempotensi
| Tipe | Kapan dikirim | Makna |
|---|---|---|
| `return_reminder_h1` | 1 hari sebelum `rentals.return_date` | Pengingat H-1 ("harus dikembalikan BESOK"). |
| `return_reminder_due` | Pada `rentals.return_date` | Pengingat hari-H ("harus dikembalikan HARI INI"). |

Idempotensi: satu kombinasi **rental + tipe** hanya boleh menghasilkan **satu** kiriman. Tomcat created `notifications` berstatus `pending` **sebelum** pesan dikirim, sehingga dua proses paralel tidak bisa mengirim dua kali. Pengiriman ulang hanya bisa dilakukan eksplisit lewat `rentals:send-return-reminders --force`.

### C. Format Pesan (Bahasa Indonesia)
Pesan memuat: nama pelanggan, kode booking, batas waktu kembali, daftar barang yang disewa, total biaya, dan informasi denda keterlambatan per barang (`rental_details.late_fee_per_day_snapshot`).

### D. Hasil Cek Paket Gratis Fonnte (wajib dicatat — PRD §Fase 2)
Checked langsung ke situs & dokumentasi resmi Fonnte pada **2026-10-21**:

| Aspek | Hasil |
|---|---|
| Harga paket gratis | **Rp 0** — Fonnte menyediakan versi gratis "untuk proses development tanpa batas waktu". |
| Kuota | **Per device** (bukan per akun). Dokumentasi resmi menyebut paket gratis dengan sisa kuota **900**. |
| Batasan | **Text-only** (tanpa lampiran/attachment). |
| Device | Satu paket berlaku untuk satu device; paket tidak bisa dipakai bersama device lain. |
| API | `POST https://api.fonnte.com/send`, header `Authorization: <token>` (**tanpa** prefiks `Bearer`), body `target` / `message` / `countryCode` (`62`). Respons sukses `{"status": true}`, gagal `{"status": false, "reason": "..."}`. |

**Kesimpulan:** syarat "100% gratis" pada PRD **terpenuhi** untuk tahap development. Kanal `in_app` & `email` tetap diimplementasikan sebagai fallback dan cukup diaktifkan lewat config.

### D.1 Hasil Verifikasi Langsung ke API Fonnte (2026-10-21)

Pengujian sungguhan (di luar test suite, dengan izin PM) memakai token Fonnte asli dari `.env` lokal:

| Skenario | Respons Fonnte | Kesimpulan |
|---|---|---|
| Token palsu (kontrol) | `{"status": false, "reason": "invalid token"}` | Autentikasi ditolak di tahap token |
| **Token Fonnte TENDAKU** | `{"status": false, "reason": "request invalid on disconnected device"}` | ✅ **Token valid** — permintaan lolos autentikasi, ditolak di tahap device |
| Token TENDAKU, device sudah di-*connect* | `{"status": true}` | ✅ **Pesan uji berhasil terkirim** ke `6289602168049` |

Temuan penting untuk tim:

1. **Device WA harus berstatus *connected*.** Token yang valid tetap ditolak dengan `request invalid on disconnected device` bila device belum di-pairing / sesinya sudah putus di dashboard Fonnte.
2. **Pesan yang gagal tidak membakar kuota** — request ditolak sebelum masuk ke device.
3. Token yang sudah dibuat tidak perlu di-regenerate selama device masih terhubung; token baru hanya perlu dibuat bila device di-*unlink* atau token bocor.

> ⚠️ Token Fonnte **tidak boleh** disimpan di `.env.example` (file yang di-commit ke repo publik) — hanya di `.env` lokal yang di-gitignore. Panduan lengkap di §4.E.

### E. Cara Mendapatkan Fonnte Token (panduan untuk admin/operator)

**Fonnte token** = string rahasia (panjang ±40 karakter) yang dibuat otomatis oleh dashboard Fonnte untuk device Anda. Token inilah yang **mengotorisasi** aplikasi Tendaku agar boleh mengirim WhatsApp lewat device Fonnte Anda — jadi sifatnya seperti password, bukan API key gratis dari layanan lain.

Langkah memperolehnya:

1. Buka **https://fonnte.com** → **Login / Daftar** (akun Fonnte, terpisah dari akun Tendaku).
2. Masuk ke menu **Device** → **Add Device** → hubungkan nomor WhatsApp yang dipakai untuk notifikasi (format internasional, mis. `6281234567890`).
3. Setelah device terverifikasi (scan QR / pairing), buka tab **Token** pada device tersebut → klik **Generate / Regenerate Token**.
4. Salin token itu ke file `.env` lokal proyek:

   ```dotenv
   FONNTE_TOKEN=token-dari-dashboard-fonnte
   ```

Aturan main:

- Token **tidak boleh** di-commit ke repository. Yang dikomit hanya `.env.example` (tanpa nilai) — konfigurasi lengkapnya ada di `config/notifications.php` (`FONNTE_TOKEN`).
- Header yang dipakai API adalah `Authorization: <token>` **tanpa** prefiks `Bearer` (lihat §4.D).
- Token bisa di-regenerate di dashboard bila bocor; regenerate **mematikan** token lama.
- Tanpa token, `FonnteWhatsAppService` mengembalikan `false` (tidak melempar exception) dan pengingat tetap tercatat di tabel `notifications` dengan status `failed` — lihat §4.B.

### F. Sumber Nomor WhatsApp untuk Pengingat Pengembalian

Nomor tujuan pengingat ditentukan **otomatis** oleh sistem, bukan dipilih manual oleh pemesan. Aturan ini berlaku untuk semua rental (login maupun guest):

| Skenario | Sumber Nomor | Kolom Penyimpanan | Keterangan |
|---|---|---|---|
| Pelanggan **terdaftar** (login) | `users.phone` (profil pelanggan) | `rentals.user_id` → `users.phone` | Nomor diisi saat registrasi (`register.blade.php`, field `phone` **wajib**). Pengingat memakai **nilai profil terbaru**, jadi bila pelanggan memperbarui nomornya, pengingat berikutnya ikut memakai nomor baru. |
| Pelanggan **tanpa akun** (guest) | Nomor WhatsApp yang **diinput saat checkout** | `rentals.guest_phone` | Field "No. WhatsApp Aktif" pada form checkout guest (bersama nama & NIK). Nomor disimpan **apa adanya** (format yang diketik pengguna, mis. `0812-9999-9999`) dan baru dinormalisasi ke `62xxx` saat pengiriman. |
| Pelanggan login **tanpa** nomor di profil | — | — | Pengingat **dilewati** (dicatat di log sebagai warning, tidak ada baris di `notifications`). Halaman checkout menampilkan peringatan agar pelanggan melengkapinya di profil. |

Kontrak teknis:

```php
// app/Services/ReturnReminderService.php
$phone = $rental->customer?->phone ?? $rental->guest_phone;   // login → profil, guest → input checkout
$recipient = PhoneNumberNormalizer::normalize($phone);          // 08xxx / +62xxx → 62xxx
```

- Pemilihannya **berurutan**: profil pelanggan (bila rental punya `user_id`) lebih dulu, baru `guest_phone`.
- `RentalService::createBooking()` (Byan) **mewajibkan** `guest_name`, `guest_phone`, dan `guest_id_number` untuk booking tanpa akun — sehingga nomor pengingat selalu ada sejak menit pertama.
- Verifikasi akses halaman status booking oleh guest (`BookingController::resolveAndAuthorizeRental`) membandingkan nomor dalam bentuk **ternormalisasi**, jadi tamu boleh mengetik `08xxx` maupun `62xxx`.
- Tanpa perubahan skema: kolom `rentals.guest_phone` sudah ada sebelumnya.

---

## 5. LOG PERUBAHAN SKEMA (SCHEMA CHANGE LOG)

Setiap migrasi baru atau perubahan struktur tabel wajib dicatat di sini agar Bunga dapat menyelaraskan diagram ERD:

| Tanggal | File Migrasi | Perubahan Kolom / Tabel | Alasan / Keterangan |
|---|---|---|---|
| 2026-10-18 | *(Fase 0 Dasar)* | Tidak ada migrasi baru; kolom `vendor_id` pada tabel inti sudah selaras. | Fondasi middleware `vendor.active` dan service `CurrentVendor`. |
| 2026-10-21 | *(Fase 2 Notifikasi WA)* | **Tidak ada migrasi baru.** | Tabel `notifications` (dibuat migrasi `2026_10_01_000011_create_notification_logs_table.php`) sudah memuat kolom `vendor_id`, `user_id`, `rental_id`, `recipient_phone`, `channel`, `type`, `message`, `status`, `sent_at` — cukup untuk log & idempotensi pengingat. |
| 2026-10-21 | *(Fase 3 WeatherService)* | **Tidak ada migrasi baru.** | Praktikiraan cuaca diambil dari API Open-Meteo (tanpa API key) dan di-cache di cache store aplikasi — tidak butuh tabel baru. |
| 2026-10-21 | *(UI Weather Calendar — `cross-module`)* | **Tidak ada migrasi baru.** | Halaman `/cuaca` hanya membaca data dari API cuaca; seluruh state (spot, periode) disimpan di URL, bukan di database. |
| 2026-10-21 | *(Checkout — capture nomor WA guest, `cross-module`)* | **Tidak ada migrasi baru.** | Kolom `rentals.guest_phone` (serta `guest_name`, `guest_id_number`) sudah ada di ERD. Checkout kini mengisi kolom tersebut; tidak ada kolom baru yang ditambahkan. |

> **Catatan penamaan untuk Bunga:** nama **tabel** adalah `notifications`, sedangkan nama **model/class** adalah `App\Models\NotificationLog`. Keduanya menunjuk entitas yang sama (bukan tabel terpisah `notification_logs`).

---

## 6. LOG KEPUTUSAN TIM (DECISIONS LOG)

| # | Topik | Keputusan / Konsensus | Status |
|---|---|---|---|
| 1 | Middleware & Scope Multi-Tenant | Menggunakan service `CurrentVendor` + trait `BelongsToVendor` + middleware alias `vendor.active`. Akses data vendor lain wajib mengembalikan HTTP 404. | ✅ Disetujui & Diimplementasikan |
| 2 | Status GMV | GMV hanya menghitung transaksi `confirmed` hingga `completed`. Transaksi `pending`, `cancelled`, dan `expired` tidak dihitung. | ✅ Disetujui |
| 3 | Integrasi Cuaca (Weather) | Backend service, caching, dan contract JSON ditangani oleh Boby (`WeatherService`), sedangkan tampilan frontend (`WeatherCalendar` + Banner) ditangani oleh Andini. | ✅ Disetujui & Diimplementasikan (2026-10-21) |
| 4 | Notifikasi WhatsApp | Menggunakan provider Fonnte dengan **paket gratis (Rp 0, kuota per device ±900, text-only)** — hasil verifikasi langsung ke dokumentasi resmi Fonnte (lihat §4.D). Bila kuota habis, fallback ke kanal `in_app`/`email` **diaktifkan lewat config**, tanpa menghapus kode WA. | ✅ Disetujui & Diimplementasikan (2026-10-21) |
| 5 | Kanal notifikasi | Pengingat pengembalian memakai kanal `whatsapp` sebagai default. Pergantian kanal dilakukan lewat `NOTIFICATION_CHANNEL` (`whatsapp` \| `in_app` \| `email`). | ✅ Disetujui & Diimplementasikan (2026-10-21) |
| 6 | Jam pengingat | Pengingat H-1 & hari-H dijadwalkan harian **09:00 Asia/Jakarta** (`REMINDER_HOUR`, `REMINDER_TIMEZONE`). | ✅ Disetujui & Diimplementasikan (2026-10-21) |
| 7 | Ambang cuaca ekstrem | Kode WMO petir `[95, 96, 97, 99]`, curah hujan **≥ 50 mm/hari**, angin **≥ 40 km/h** — semuanya di `config/weather.php` sehingga dapat diubah tanpa menyentuh kode. | ✅ Disetujui & Diimplementasikan (2026-10-21) |
| 8 | Serah terima (handover) ke Andini | Bagian backend `WeatherService` + kontrak JSON (§7.B) merupakan **kontrak resmi** yang dipakai Andini. Rincian serah terima, daftar file yang boleh diubah, dan batasan UI ada di §7.F. | ✅ Disetujui & Diimplementasikan (2026-10-21) |
| 9 | Sumber nomor WhatsApp pengingat | Nomor pengingat **tidak** dipilih manual: (1) pelanggan login → `users.phone` dari profil; (2) guest → nomor WA yang diinput saat checkout, disimpan di `rentals.guest_phone`. Tanpa perubahan skema. Detail di §4.F. | ✅ Disetujui & Diimplementasikan (2026-10-21) |

---

## 7. KONTRAK WEATHER (FASE 3)

### A. Service & Endpoint

| Aspek | Nilai |
|---|---|
| Service | `App\Services\WeatherService` (formatter: `App\Services\WeatherFormatter`) |
| Endpoint | `GET https://api.open-meteo.com/v1/forecast` — Open-Meteo **gratis, tanpa API key** |
| Parameter harian | `weather_code`, `temperature_2m_max`, `temperature_2m_min`, `precipitation_sum`, `precipitation_probability_max`, `wind_speed_10m_max` |
| Parameter lain | `latitude`, `longitude`, `timezone=Asia/Jakarta`, `start_date`, `end_date` |
| Cache | **30 menit** per spot + rentang tanggal (`weather.cache_ttl_minutes`) |
| Retry & timeout | `WEATHER_RETRY_TIMES` (default 2), `WEATHER_TIMEOUT` (default 10 detik) |
| Kontrak | `forecast(string $spot, Carbon $start, Carbon $end): array` — **tidak pernah melempar exception** |
| Kegagalan | `error = true`, `error_message` terisi, `days = []` → UI tetap jalan |

### B. Kontrak JSON (diteruskan ke Andini)

Keluaran `WeatherService::forecast()`:

```json
{
  "spot": "bromo",
  "label": "Gunung Bromo",
  "generated_at": "2026-10-21T09:00:00+07:00",
  "error": false,
  "error_message": null,
  "days": [
    {
      "date": "2026-10-22",
      "temp_min": 11.8,
      "temp_max": 20.2,
      "precipitation_mm": 0.0,
      "precipitation_probability": 2,
      "wind_kmh": 12.1,
      "weather_code": 2,
      "label": "Berawan sebagian",
      "is_extreme": false
    }
  ]
}
```

Bila layanan cuaca gagal (HTTP error / timeout / spot tidak dikenal):

```json
{
  "spot": "bromo",
  "label": "Gunung Bromo",
  "generated_at": "2026-10-21T09:00:00+07:00",
  "error": true,
  "error_message": "Layanan cuaca merespons error.",
  "days": []
}
```

> **Catatan untuk Andini:** contoh JSON pada PRD belum memuat flag error, padahal PRD juga meminta "struktur kosong yang aman + flag error". Karena itu `error` (bool) dan `error_message` (string|null) **ditambahkan**. Field `label` (nama spot) juga ditambahkan untuk kebutuhan banner. Field lainnya (`date`, `temp_min`, `temp_max`, `precipitation_mm`, `precipitation_probability`, `wind_kmh`, `weather_code`, `label`, `is_extreme`) mengikuti contoh PRD apa adanya. Tipe `precipitation_probability` & `weather_code` adalah integer, sisanya float.

Kunci spot yang valid: **`bromo`**, **`ranu_kumbolo`**, **`batu`** (daftar lengkap: `WeatherService::availableSpots()`). Nama spot dinormalisasi otomatis (lowercase, spasi → `_`).

### C. Verifikasi Koordinat Spot (wajib — PRD "verifikasi koordinat sebelum dipakai")

| Spot | Latitude | Longitude | Elevasi | Sumber Verifikasi | Catatan |
|---|---|---|---|---|---|
| `bromo` (Gunung Bromo) | -7.9431 | 112.9538 | 2329 m | Open-Meteo Geocoding API (`name=Gunung Bromo`, `feature_code=MT`) | Cocok dengan perkiraan PRD (-7.94 / 112.95) |
| `ranu_kumbolo` (Ranu Kumbolo) | -8.05 | 112.917 | ~2400 m | Wikipedia/Wikidata (8°03′S 112°55′E) | ⚠️ **Tidak ada** di database Open-Meteo Geocoding → koordinat diambil dari Wikipedia. Perkiraan PRD (-8.01 / 112.92) bergeser ~4,5 km ke utara. |
| `batu` (Kota Batu) | -7.87 | 112.5283 | 881 m | Open-Meteo Geocoding API (`name=Batu`, `countryCode=ID`) | Cocok dengan perkiraan PRD (-7.87 / 112.52) |

> Selisih ±5 km tidak signifikan untuk prakiraan cuaca (grid Open-Meteo ±11 km). API juga meng-*grid* koordinat ke titik terdekat — contohnya titik Bromo dipetakan ke (-7.9789, 112.9803, elev 2138 m).

### D. Ambang Cuaca Ekstrem (✅ disetujui Boby, 2026-10-21)

| Ambang | Nilai awal | Rujukan |
|---|---|---|
| Kode WMO petir | `95`, `96`, `97`, `99` | Kode WMO badai petir |
| Curah hujan harian | **≥ 50 mm/hari** | Kategori BMKG "hujan lebat" (≥ 50 mm / 24 jam) |
| Kecepatan angin maks | **≥ 40 km/h** | Kategori BMKG "angin kencang" (40–50 km/h) |

Seluruh ambang berada di `config/weather.php` (`weather.extreme`) — **bukan** di dalam kode, sehingga dapat diubah tanpa menyentuh service. Angka di atas **sudah disetujui Boby** pada 2026-10-21 (PRD §6 — keputusan manusia tidak boleh diambilsepihak; agent hanya menyiapkan opsi + nilai awal).

### E. Perubahan API dari Stub Awal

Method stub `WeatherService::getForecast(string $location, string $date)` **dihapus** dan digantikan `WeatherService::forecast(string $spot, Carbon $start, Carbon $end)` sesuai tabel kontrak §1. Stub tersebut belum dipakai siapa pun (sudah diverifikasi pada 2026-10-21).

### F. Serah Terima ke Andini — UI WeatherCalendar & Banner (✅ 2026-10-21)

Maksud "dilempar ke Andini" = Boby menyiapkan **bagian backend + kontrak JSON**, lalu handing over **bagian tampilan** kepada Andini supaya dia cukup mengerjakan UI tanpa perlu tahu detail HTTP Open-Meteo.

**Paket serah terima (yang diterima Andini):**

| Aspek | Isi |
|---|---|
| Service | `WeatherService::forecast()` + `WeatherService::availableSpots()` (backend Boby) |
| Kontrak JSON | Persis seperti pada §7.B — **tidak boleh diubah** oleh sisi UI |
| Ambang ekstrem | `config/weather.php` → `weather.extreme` (§7.D) |
| Error handling | `error = true` + `error_message` → UI wajib menampilkan banner, **tidak boleh** melempar exception ke user |
| File milik Andini | `app/Livewire/Customer/WeatherCalendar.php`, `resources/views/livewire/customer/weather-calendar.blade.php`, `resources/views/components/weather-badge.blade.php` |

**Status implementasi (2026-10-21):** UI tersebut **sudah diimplementasikan atas nama Andini** pada branch `feat/weather-calendar-ui` dengan label **`cross-module`** (PRD §0 — tidak boleh mengubah modul anggota lain tanpa label). Rinciannya:

| File | Perubahan | Label |
|---|---|---|
| `app/Livewire/Customer/WeatherCalendar.php` | Komponen Livewire: pilih spot + periode (state di URL `?spot=` & `?periode=`), tombol "Perbarui" (memakai `WeatherService::forget()`), enrichment presentasi (ikon, nama hari, penanda "Hari Ini"). | `cross-module` |
| `resources/views/livewire/customer/weather-calendar.blade.php` | Panel kontrol, banner error (graceful), banner peringatan cuaca ekstrem, grid kartu harian. | `cross-module` |
| `resources/views/components/weather-badge.blade.php` | Badge cuaca: props `condition`, `icon`, `temp`, `extreme` (varian merah untuk cuaca ekstrem). | `cross-module` |
| `resources/views/customer/weather/index.blade.php` + `routes/web.php` | Halaman publik `GET /cuaca` (route `customer.weather.index`) + link navbar customer. | `cross-module` |
| `app/Services/WeatherService.php` | Tambahan method `forget()` untuk membuang cache (dipakai tombol refresh). Kontrak `forecast()` **tidak berubah**. | `cross-module` |
| `tests/Feature/Customer/WeatherCalendarTest.php` | 8 test feature: render kartu harian, banner ekstrem, banner error, ganti spot, ubah periode, refresh cache, parameter URL tidak valid. | `cross-module` |

> Kontrak JSON pada §7.B **tidak berubah** — atribut tambahan (ikon, nama hari, penanda hari ini) hanya dihitung di layer tampilan dan tidak dikirim keluar dari service.
