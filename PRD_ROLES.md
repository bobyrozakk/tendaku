# PRD — Timeline & Pembagian Tugas Tim Per Role (1 Bulan)
## TENDAKU — Platform Penyewaan Alat Camping Outdoor Multi-Vendor

---

## 📌 Ringkasan Tim & Alokasi Tugas (5 Anggota Tim)

Proyek ini dikerjakan oleh **5 orang anggota tim** selama **1 bulan (4 minggu)**. Setiap anggota memegang tanggung jawab spesifik (*role/stakeholder*) dengan dependency yang jelas antar peran:

| Role | Penanggung Jawab | Fokus Utama |
| :--- | :--- | :--- |
| **ROLE 1** | **Anda (Team Lead / PM)** | **Database & Backend Core:** Skema DB, Eloquent Models, Trait `BelongsToVendor` (Multi-tenancy), Logic ketersediaan unit, kalkulasi sewa, denda keterlambatan/kerusakan. |
| **ROLE 2** | **Anggota 2** | **Backend API & Integrasi:** Gateway Pembayaran Midtrans (SNAP & Webhook), API Prediksi Cuaca (OpenWeather/Open-Meteo), & Integrasi WhatsApp Notifikasi (WaBlas/Fonnte). |
| **ROLE 3A**| **Anggota 3** | **Frontend Customer (Katalog & Cuaca):** Halaman Landing Page, Katalog Produk, Detail Peralatan, & Komponen UI Kalender Prediksi Cuaca. |
| **ROLE 3B**| **Anggota 4** | **Frontend Customer (Booking & Receipt):** Form Checkout (Pembayaran Lunas), Pop-up Midtrans SNAP UI, Halaman Status Booking, & **Digital Collateral Receipt (Tanda Terima KTP)**. |
| **ROLE 4** | **Anggota 5** | **Frontend Vendor Dashboard & QA:** POS Admin Vendor, Manajemen Unit Barang (Barcode/Serial), **UI Pickup (Kamera / Upload Foto Penyewa + KTP)**, UI Return & Inspeksi Barang, serta QA Testing. |

---

## 🚨 Catatan Revisi Bisnis Utama (Wajib Diikuti Semua Role)
1. **Tanpa Deposit Uang Tunai:** Tidak ada fitur deposit tunai/moneter. Semua transaksi sewa dibayar **Lunas (*Full Payment*)** di awal.
2. **Jaminan Fisik KTP & Foto Verifikasi Wajah:** Saat pengambilan barang (*pickup*), penyewa menyerahkan KTP fisik asli. Admin toko wajib mengambil foto penyewa memegang KTP fisiknya langsung melalui sistem (Role 4 & Role 1).
3. **Tanda Terima Digital Jaminan KTP:** Sistem menerbitkan bukti digital penahanan KTP yang dapat dilihat oleh penyewa di halaman transaksi (Role 3B). KTP diserahkan kembali saat pengembalian barang (*return*).

---

## 🗄️ ROLE 1 — Database & Backend Core (Team Lead / PM)

**Fokus:** Migrasi, Model Eloquent, Relasi, Multi-Tenant Scoping (`BelongsToVendor`), Logic Ketersediaan, Pickup & Return Logic.

### Minggu 1 (5–9 Okt) — Skema Database & Multi-Tenancy Dasar
- **Sen 5:** Finalisasi ERD dengan tim, setup Laravel + `BelongsToVendor` Trait foundation. (*Deliverable:* Project skeleton & global scope scoping).
- **Sel 6:** Migrasi `vendors` + `users` (cast `encrypted` untuk NIK). (*Deliverable:* 2 tabel ter-migrate).
- **Rab 7:** Migrasi `vendor_payment_settings` + `item_categories`. (*Deliverable:* Relasi vendor-setting & kategori aktif).
- **Kam 8:** Migrasi `master_items` & `item_units` (tanpa deposit_amount). (*Deliverable:* Tabel katalog & unit fisik ber-barcode).
- **Jum 9:** Demo migrasi minggu 1 ke seluruh tim. (*Deliverable:* 6 tabel siap pakai).

### Minggu 2 (12–16 Okt) — Tabel Transaksi & Model Scoping
- **Sen 12:** Migrasi `rentals` (skema jaminan KTP: `ktp_collateral_photo_url`, `ktp_collateral_status`, `ktp_received_at`, `ktp_returned_at`). (*Deliverable:* Tabel header sewa lunas & KTP).
- **Sel 13:** Migrasi `rental_details` (unit fisik assignment & snapshot harga/kondisi). (*Deliverable:* Tabel detail sewa).
- **Rab 14:** Migrasi `payments` & `notification_logs`. (*Deliverable:* 10 tabel selesai, `migrate:fresh --seed` sukses).
- **Kam 15:** Implementasi Eloquent Model lengkap + Trait `BelongsToVendor` di semua model terkait. (*Deliverable:* Scoping multi-tenant teruji).
- **Jum 16:** Review relasi & data seeder bersama Role 2, 3, 4. (*Deliverable:* Model siap pakai oleh API & Frontend).

### Minggu 3 (19–23 Okt) — Domain Services & Logic Operasional
- **Sen 19:** `AvailabilityService`: Kalkulasi ketersediaan unit barang per rentang tanggal overlap. (*Deliverable:* Function `isAvailable()` teruji).
- **Sel 20:** Logic `createBooking()` (Booking code otomatis, total lunas, timestamp expirasi). (*Deliverable:* Service booking lunas).
- **Rab 21:** Logic `processPickup()` (Unit assignment, validasi vendor, update `ktp_collateral_status = 'held'`). (*Deliverable:* Logic pickup + jaminan KTP).
- **Kam 22:** Logic `processReturn()` (Inspeksi barang, hitung denda keterlambatan/kerusakan, update `ktp_collateral_status = 'returned'`). (*Deliverable:* Logic return & pengembalian KTP).
- **Jum 23:** Unit Testing 4 service utama + dokumentasi fungsi untuk Role 2 & Role 4. (*Deliverable:* Automated tests passing).

### Minggu 4 (26–30 Okt) — Performance, Security & Deploy
- **Sen 26:** Optimasi query database (Fix N+1 query problem & penambahan DB Index). (*Deliverable:* Query response < 100ms).
- **Sel 27:** Audit keamanan enkripsi data sensitif (`users.nik` & `rentals.guest_id_number`). (*Deliverable:* Data sensitif aman terenkripsi).
- **Rab 28:** Penyiapan database production & seeder data awal. (*Deliverable:* Script seeder bersih).
- **Kam 29:** Hardening transaksi database (DB Transactions & Locking). (*Deliverable:* Race-condition free booking).
- **Jum 30:** Final deployment & demo pengerjaan bersama tim. (*Deliverable:* Sistem live & siap dipresentasikan).

---

## 💳 ROLE 2 — Backend API & Integrasi

**Fokus:** Gateway Pembayaran Midtrans (Snap & Webhook), API Prediksi Cuaca, Integrasi WhatsApp Notification.

### Minggu 1 (5–9 Okt) — Riset & API Cuaca Foundation
- **Sen 5:** Setup akun Sandbox Midtrans & riset API OpenWeather / Open-Meteo. (*Deliverable:* Kredensial sandbox aktif).
- **Sel 6:** Class `WeatherService` (Fetch forecast lokasi camping per tanggal sewa). (*Deliverable:* Integration service cuaca).
- **Rab 7:** Formatter data cuaca internal untuk komponen frontend. (*Deliverable:* Response JSON cuaca standar).
- **Kam 8:** Test API cuaca lokasi camping (Bromo, Ranu Kumbolo, Batu). (*Deliverable:* Data cuaca akurat).
- **Jum 9:** Penyiapan endpoint internal cuaca untuk Role 3A. (*Deliverable:* Endpoint cuaca siap konsumsi).

### Minggu 2 (12–16 Okt) — Integrasi Midtrans SNAP (Full Payment)
- **Sen 12:** Setup Midtrans SNAP SDK & `MidtransService`. (*Deliverable:* Class helper Midtrans).
- **Sel 13:** Endpoint `POST /rentals/{id}/pay` (Generate `snap_token` bayar lunas). (*Deliverable:* Snap token pembayaran lunas).
- **Rab 14:** Penyiapan Webhook Listener Route (`POST /webhook/midtrans`). (*Deliverable:* Route webhook terdaftar).
- **Kam 15:** Validasi Signature Key Webhook Midtrans. (*Deliverable:* Webhook secure dari request palsu).
- **Jum 16:** Handler status Webhook: `settlement` -> update `payments.status = 'settled'` & `rentals.status = 'paid'`. (*Deliverable:* Status otomatis ter-update).

### Minggu 3 (19–23 Okt) — Pelunasan Denda & WhatsApp Notification
- **Sen 19:** Endpoint pembayaran denda keterlambatan/kerusakan via Midtrans. (*Deliverable:* Payment token denda).
- **Sel 20:** Integrasi provider WhatsApp Gateway (WaBlas / Fonnte). (*Deliverable:* Helper pengiriman WA).
- **Rab 21:** WhatsApp Trigger 1: Notifikasi konfirmasi booking & instruksi pembayaran. (*Deliverable:* WA otomatis terkirim).
- **Kam 22:** WhatsApp Trigger 2: Pengingat H-1 pengembalian barang & Bukti Digital Jaminan KTP. (*Deliverable:* WA pengingat aktif).
- **Jum 23:** Testing end-to-end pembayaran Midtrans + Notifikasi WA bersama Role 1 & Role 3B. (*Deliverable:* Integrasi lancar).

### Minggu 4 (26–30 Okt) — Handling Edge Case & Kredensial Production
- **Sen 26:** Handling edge case: Webhook retry, duplikat payload, & timeout API. (*Deliverable:* Webhook idempotent).
- **Sel 27:** Fallback error `WeatherService` (Handling jika API cuaca limit/down). (*Deliverable:* Fallback ui tidak crash).
- **Rab 28:** Dokumentasi API internal untuk Role 3 & Role 4. (*Deliverable:* Swagger/Postman Collection).
- **Kam 29:** Regression testing alur pembayaran & notifikasi. (*Deliverable:* Zero payment bugs).
- **Jum 30:** Penyiapan Kredensial Production (Midtrans Live Key & WA Token). (*Deliverable:* Environment production terisi).

---

## 🎨 ROLE 3A — Frontend Customer (Katalog & Weather UI)

**Fokus:** Landing Page, Katalog Produk, Detail Peralatan, & Komponen UI Kalender Prediksi Cuaca.

### Minggu 1 (5–9 Okt) — Wireframe & Landing Page Styling
- **Sen 5:** Setup Blade layout customer (`x-layouts.customer`) & styling Tailwind CSS. (*Deliverable:* Base layout responsif).
- **Sel 6:** Pengerjaan Landing Page (`welcome.blade.php`) — Hero Section & Navbar. (*Deliverable:* Hero section menarik).
- **Rab 7:** Landing Page — Kategori Produk & Banner Penjelasan Jaminan KTP (Tanpa Deposit). (*Deliverable:* Section jaminan KTP).
- **Kam 8:** Wireframe Halaman Katalog Produk (`customer/catalog/index.blade.php`). (*Deliverable:* Layout grid katalog).
- **Jum 9:** Review desain Landing Page & Katalog bersama tim. (*Deliverable:* UI landing disetujui).

### Minggu 2 (12–16 Okt) — Katalog Interaktif & Detail Produk
- **Sen 12:** Komponen pencarian & filter kategori pada Katalog Produk. (*Deliverable:* Filter UI berfungsi).
- **Sel 13:** Card produk dengan badge ketersediaan real-time & info vendor. (*Deliverable:* Grid card produk rapi).
- **Rab 14:** Halaman Detail Produk (`customer/product/show.blade.php`). (*Deliverable:* Detail alat & tarif per hari).
- **Kam 15:** Date-picker tanggal sewa (pickup & return date) di detail produk. (*Deliverable:* Pemilih tanggal sewa).
- **Jum 16:** Integrasi data katalog asli dari backend Role 1. (*Deliverable:* Katalog menampilkan data DB asli).

### Minggu 3 (19–23 Okt) — UI Kalender Prediksi Cuaca
- **Sen 19:** Komponen UI Kalender Cuaca pada Halaman Detail Produk (`WeatherCalendar.php`). (*Deliverable:* Widget kalender cuaca).
- **Sel 20:** Integrasi data cuaca dari `WeatherService` (Role 2) ke tampilan kalender. (*Deliverable:* Badge cuaca per tanggal).
- **Rab 21:** Tampilan peringatan cuaca buruk (Hujan Deras / Angin) di tanggal sewa pilihan. (*Deliverable:* Warning badge cuaca).
- **Kam 22:** Rekomendasi alternatif tanggal camping yang cerah. (*Deliverable:* Fitur saran tanggal cerah).
- **Jum 23:** Testing responsif UI Katalog & Kalender Cuaca di layar mobile/tablet. (*Deliverable:* Mobile-friendly UI).

### Minggu 4 (26–30 Okt) — Polishing & UX Enhancements
- **Sen 26:** Polishing mikro-animasi, loading skeleton, & state error. (*Deliverable:* UX mulus & cepat).
- **Sel 27:** Perbaikan typo, bahasa Indonesia konsisten, & kontras warna UI. (*Deliverable:* Tampilan profesional).
- **Rab 28:** Fix bug tampilan dari hasil testing Role 5 (QA). (*Deliverable:* Bug UI terselesaikan).
- **Kam 29:** Re-check performa load page (Lighthouse Score > 90). (*Deliverable:* Fast loading landing page).
- **Jum 30:** Persiapan demo alur jelajah katalog & cek cuaca. (*Deliverable:* Skenario demo siap).

---

## 🛒 ROLE 3B — Frontend Customer (Booking, Payment & Digital Receipt)

**Fokus:** Form Checkout (Pembayaran Lunas), Pop-up Midtrans SNAP UI, Halaman Status Booking, & **Digital Collateral Receipt (Tanda Terima KTP)**.

### Minggu 1 (5–9 Okt) — Form Checkout & Validation
- **Sen 5:** Wireframe alur Checkout Peralatan (`customer/checkout/index.blade.php`). (*Deliverable:* Skeleton checkout).
- **Sel 6:** Form data pemesan (Jalur Customer Login & Jalur Guest Walk-in). (*Deliverable:* Input data diri).
- **Rab 7:** Form pemilih metode pengiriman (`store_pickup` atau `delivery`). (*Deliverable:* Opsi pengambilan).
- **Kam 8:** Ringkasan Biaya Sewa (Subtotal, Delivery Fee, Discount, Grand Total Lunas — Tanpa Deposit). (*Deliverable:* Ringkasan total lunas).
- **Jum 9:** Validasi form checkout (Nomor WA, NIK, tanggal sewa). (*Deliverable:* Form tervalidasi).

### Minggu 2 (12–16 Okt) — Integrasi Booking & Midtrans SNAP UI
- **Sen 12:** Hubungkan Form Checkout ke Service `createBooking()` (Role 1). (*Deliverable:* Form membuat transaksi di DB).
- **Sel 13:** Integrasi Pop-up Midtrans SNAP JS di halaman checkout. (*Deliverable:* Pop-up bayar muncul).
- **Rab 14:** Handling callback Midtrans SNAP (Success, Pending, Error redirect). (*Deliverable:* Redirect otomatis setelah bayar).
- **Kam 15:** Halaman Konfirmasi Pembayaran & Instruksi Pembayaran Manual/QRIS. (*Deliverable:* Halaman instruksi bayar).
- **Jum 16:** Testing end-to-end checkout -> bayar lunas via Midtrans Sandbox. (*Deliverable:* Pembayaran lunas terverifikasi).

### Minggu 3 (19–23 Okt) — Tanda Terima Digital KTP & Halaman Status
- **Sen 19:** Halaman Riwayat Sewa Pelanggan (`rentals/index.blade.php`). (*Deliverable:* List transaksi sewa).
- **Sel 20:** Halaman Detail Transaksi Sewa (`rentals/show.blade.php`) & Timeline Status (`pending` -> `paid` -> `picked_up` -> `returned`). (*Deliverable:* Status tracker transaksi).
- **Rab 21:** **Komponen Digital Collateral Receipt (Tanda Terima Digital KTP):** Tampilan foto verifikasi KTP + Wajah yang diambil toko saat pickup, timestamp penahanan KTP, & status KTP ditahan. (*Deliverable:* Tanda terima KTP digital pelanggan).
- **Kam 22:** Tampilan rincian denda keterlambatan/kerusakan (jika ada saat return) & tombol pelunasan denda. (*Deliverable:* UI denda jika ada).
- **Jum 23:** Testing alur checkout hingga penerbitan Digital Receipt KTP bersama Role 1 & Role 5. (*Deliverable:* Receipt KTP berfungsi).

### Minggu 4 (26–30 Okt) — Cross-device Testing & Optimization
- **Sen 26:** Testing tampilan Digital Receipt KTP di smartphone (mudah ditunjukkan ke petugas toko). (*Deliverable:* Mobile receipt responsive).
- **Sel 27:** Handling error jaringan / gagal bayar pada checkout. (*Deliverable:* Friendly error message).
- **Rab 28:** Fix bug transaksi dari hasil QA Role 5. (*Deliverable:* Checkout bug free).
- **Kam 29:** Final review teks & bahasa di seluruh flow checkout & receipt. (*Deliverable:* Teks konsisten).
- **Jum 30:** Persiapan demo alur checkout & penerbitan Tanda Terima Digital KTP. (*Deliverable:* Demo checkout lancar).

---

## 🏪 ROLE 4 — Frontend Vendor Dashboard & QA

**Fokus:** Dashboard Vendor/POS, Manajemen Stok Unit Fisik (Barcode/Serial), **UI Pickup (Foto Penyewa + KTP)**, UI Return & Inspeksi Barang, Testing & QA.

### Minggu 1 (5–9 Okt) — Structure Vendor Dashboard
- **Sen 5:** Setup Blade layout Vendor (`x-layouts.vendor`) & Sidebar menu. (*Deliverable:* Layout admin vendor).
- **Sel 6:** Halaman Ringkasan Dashboard (Statistik Total Sewa, Barang Tersewa, Omset). (*Deliverable:* Dashboard stats UI).
- **Rab 7:** UI Manajemen Kategori Barang (`vendor/categories/index.blade.php`). (*Deliverable:* Tabel kategori vendor).
- **Kam 8:** UI Manajemen Master Produk (`vendor/products/index.blade.php`) — Input tarif harian & denda (tanpa deposit). (*Deliverable:* Form master barang).
- **Jum 9:** Review UI Dashboard bersama Team Lead (Role 1). (*Deliverable:* Layout vendor disetujui).

### Minggu 2 (12–16 Okt) — CRUD Stok & Unit Fisik (Serialized Unit Tracking)
- **Sen 12:** Integration CRUD Master Product (`master_items`) dengan backend Role 1. (*Deliverable:* Master barang tersimpan DB).
- **Sel 13:** UI Manajemen Unit Fisik (`ItemUnitManager.php`) — Input kode unit, barcode, kondisi fisik (`excellent`, `good`, `fair`, `damaged`). (*Deliverable:* Serialized unit tracking).
- **Rab 14:** Feature pencarian unit barang via barcode / kode unit. (*Deliverable:* Search barcode unit).
- **Kam 15:** Halaman Listing Transaksi Sewa Masuk (`vendor/rentals/index.blade.php`) dengan filter status. (*Deliverable:* List rental masuk vendor).
- **Jum 16:** Testing CRUD Katalog & Stok Unit Fisik dengan data seeder asli. (*Deliverable:* Unit tracking teruji).

### Minggu 3 (19–23 Okt) — UI Pickup (Foto KTP) & Return Inspection Flow
- **Sen 19:** **UI Modul Pickup Barang (`PickupReturnFlow.php`):**
  - Form penetapan unit fisik (`item_unit_id`) per barang sewa.
  - **Modul Kamera / Input Foto Verifikasi KTP & Wajah Penyewa:** Admin mengambil foto penyewa memegang KTP asli.
  - Tombol simpan `ktp_collateral_status = 'held'`. (*Deliverable:* UI Pickup + Foto KTP Jaminan).
- **Sel 20:** Input pencatatan kondisi awal barang (`condition_before`) saat pickup. (*Deliverable:* Checklist kondisi awal).
- **Rab 21:** **UI Modul Return Barang:**
  - Checklist inspeksi kondisi barang pengembalian (`condition_after`).
  - Input otomatis / manual denda keterlambatan & biaya kerusakan.
  - Tombol konfirmasi pengembalian KTP fisik (`ktp_collateral_status = 'returned'`). (*Deliverable:* UI Return & Pengembalian KTP).
- **Kam 22:** Modal Rincian Pelunasan Denda & Cetak Nota Kasir/Print Receipt. (*Deliverable:* Cetak nota toko).
- **Jum 23:** Testing simulasi Pickup (Foto KTP) & Return barang bersama Role 1 & Role 3B. (*Deliverable:* Alur toko 100% jalan).

### Minggu 4 (26–30 Okt) — QA Testing & Final Sign-off
- **Sen 26:** **End-to-End Regression Testing:** Pengujian seluruh alur (Pilih barang -> Bayar Lunas -> Pickup & Foto KTP -> Penerbitan Digital Receipt -> Return & Serah KTP). (*Deliverable:* Laporan Bug QA).
- **Sel 27:** Retest bug fix dari Role 1, Role 2, Role 3A, dan Role 3B. (*Deliverable:* Re-test report).
- **Rab 28:** Testing cross-browser & mobile viewport untuk vendor POS. (*Deliverable:* Vendor POS responsive).
- **Kam 29:** Penyusunan Dokumen Test Case & UAT (User Acceptance Testing) Sign-off. (*Deliverable:* Dokumen UAT).
- **Jum 30:** QA Sign-off & Gladi Bersih Demo Aplikasi bersama seluruh tim. (*Deliverable:* Siap presentasi akhir).

---

## 🔗 Matrix Dependency Antar Role (Linked Issues / Jira Blockers)

| Blocker Task (Menghambat) | Blocked Task (Terhambat) | Titik Kritis Dependency |
| :--- | :--- | :--- |
| **Role 1** (Migrasi `rentals` & Model) | **Role 2** (Midtrans Webhook) | Role 2 butuh tabel `rentals` & `payments` siap di Minggu 2. |
| **Role 1** (Service `createBooking`) | **Role 3B** (Form Checkout) | Role 3B butuh backend booking siap di Minggu 2 untuk simpan pesanan. |
| **Role 1** (Logic Pickup & Return) | **Role 4** (UI Pickup Foto KTP & Return) | Role 4 butuh service `processPickup` & `processReturn` di Minggu 3. |
| **Role 2** (Endpoint `snap_token` Midtrans) | **Role 3B** (Pop-up Payment SNAP UI) | Role 3B membutuhkan `snap_token` dari Role 2 di Minggu 3. |
| **Role 2** (Service Cuaca) | **Role 3A** (UI Kalender Cuaca) | Role 3A membutuhkan endpoint cuaca dari Role 2 di Minggu 2. |
| **Role 4** (Foto KTP Pickup Saved) | **Role 3B** (Digital Collateral Receipt) | Role 3B menampilkan foto KTP yang diunggah oleh Role 4 saat pickup. |

---

## 📋 Petunjuk Input Task ke Jira

1. **Buat 5 Epic Utama di Jira:**
   - `EPIC 1: DB - Database, Multi-Tenancy & Core Logic` (Role 1)
   - `EPIC 2: API - Integrasi Midtrans, Weather & WhatsApp` (Role 2)
   - `EPIC 3A: FE - Customer Landing, Catalog & Weather UI` (Role 3A)
   - `EPIC 3B: FE - Customer Checkout, Payment & Digital Receipt` (Role 3B)
   - `EPIC 4: FE - Vendor POS, Pickup Foto KTP, Return & QA` (Role 4)
2. **Input Task/Story:** Setiap baris jadwal di atas diinput sebagai 1 Task/Issue di bawah Epic masing-masing.
3. **Link Dependencies:** Gunakan relasi Jira `is blocked by` / `blocks` berdasarkan **Matrix Dependency** di atas.
