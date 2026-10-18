# 📜 KONTRAK INTEGRASI & ANTAR ANGGOTA — TENDAKU

**Proyek:** TENDAKU — Sistem Informasi Persewaan Tenda & Perlengkapan Acara (Multi-Tenant)  
**Penjaga Dokumen:** Boby (PM · Super Admin · QA Lead)  
**Versi:** 1.0.0 (Fase 0 Fondasi)  
**Terakhir Diperbarui:** 2026-10-18  

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
| `WeatherService::forecast()` | **Boby** | Andini (UI WeatherCalendar) | `string $spot, Carbon $start, Carbon $end` | JSON terstruktur | Forecast cuaca spot camping Open-Meteo & deteksi cuaca ekstrem. |
| `WhatsAppService::send()` | **Boby** | Job Reminder (Boby) | `string $phone, string $message` | `bool` | Kirim notifikasi WA (Fonnte) secara aman, normalisasi nomor (+62/08), tidak melempar exception fatal. |
| Status & Kolom Pembayaran | Bunga | Semua | Schema `rentals`, `payments` | Matriks status & log transaksi | Konsistensi status pembayaran dan pelunasan. |

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

## 4. LOG PERUBAHAN SKEMA (SCHEMA CHANGE LOG)

Setiap migrasi baru atau perubahan struktur tabel wajib dicatat di sini agar Bunga dapat menyelaraskan diagram ERD:

| Tanggal | File Migrasi | Perubahan Kolom / Tabel | Alasan / Keterangan |
|---|---|---|---|
| 2026-10-18 | *(Fase 0 Dasar)* | Tidak ada migrasi baru; kolom `vendor_id` pada tabel inti sudah selaras. | Fondasi middleware `vendor.active` dan service `CurrentVendor`. |

---

## 5. LOG KEPUTUSAN TIM (DECISIONS LOG)

| # | Topik | Keputusan / Konsensus | Status |
|---|---|---|---|
| 1 | Middleware & Scope Multi-Tenant | Menggunakan service `CurrentVendor` + trait `BelongsToVendor` + middleware alias `vendor.active`. Akses data vendor lain wajib mengembalikan HTTP 404. | ✅ Disetujui & Diimplementasikan |
| 2 | Status GMV | GMV hanya menghitung transaksi `confirmed` hingga `completed`. Transaksi `pending`, `cancelled`, dan `expired` tidak dihitung. | ✅ Disetujui |
| 3 | Integrasi Cuaca (Weather) | Backend service, caching, dan contract JSON ditangani oleh Boby (`WeatherService`), sedangkan tampilan frontend (`WeatherCalendar` + Banner) ditangani oleh Andini. | ✅ Disetujui |
| 4 | Notifikasi WhatsApp | Menggunakan provider Fonnte (paket gratis). Jika kuota habis, fallback otomatis mencatat log pada tabel `notification_logs` tanpa menggagalkan flow utama. | ⏳ Menunggu verifikasi kuota |
