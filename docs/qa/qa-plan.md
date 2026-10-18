# 🛡️ QA PLAN (RENCANA PENGUJIAN KUALITAS) — TENDAKU

**Proyek:** TENDAKU — Sistem Informasi Persewaan Tenda & Perlengkapan Acara (Multi-Tenant)  
**QA Lead:** Boby (PM · Super Admin · QA Lead)  
**Versi Dokumen:** 1.0.0  
**Tanggal Rilis:** 2026-10-18  

---

## 1. RUANG LINGKUP PENGUJIAN (SCOPE)

Pengujian kualitas mencakup seluruh modul utama yang dikembangkan oleh tim:
1. **Multi-Tenant Foundation & Security:** Isolasi data antar vendor (`BelongsToVendor`, `CurrentVendor`, middleware `vendor.active`). Akses lintas vendor wajib mengembalikan 404.
2. **Autentikasi & RBAC:** Role `super_admin`, `vendor_owner`, `vendor_admin`, `customer`. Verifikasi email, Google OAuth, password reset.
3. **Katalog & Booking Customer:** Pencarian, filter kategori, kalkulasi ketersediaan unit (`AvailabilityService`), estimasi biaya, alur checkout.
4. **Pembayaran Gateway (Midtrans):** Pembuatan Snap Token, callback webhook idempoten, penanganan status `settlement`, `pending`, `expire`, `cancel`.
5. **Operasional Kasir (Pickup & Return):**
   - **Pickup:** Pencarian booking, assignment barcode unit, unggah foto KTP, perubahan status `held`.
   - **Return:** Inspeksi kondisi unit, kalkulasi denda telat/rusak, pelunasan denda (tunai/QRIS), pengembalian KTP (`returned`), status `completed`.
6. **Panel Super Admin:** Approval vendor baru, daftar vendor, perhitungan GMV (Gross Merchandise Value) lintas vendor.
7. **Notifikasi WhatsApp:** Pengingat pengembalian barang via Fonnte (H-1 & Hari-H), pencegahan duplikasi pesan, fallback error aman.
8. **Weather Service:** Prakiraan cuaca spot camping Open-Meteo, deteksi cuaca ekstrem, caching respons, fallback bila API eksternal offline.

---

## 2. STRATEGI PENGUJIAN (TEST STRATEGY)

### A. Level Pengujian
1. **Unit Testing:**
   - Menguji method mandiri pada layer Service (misalnya rumus keterlambatan, normalisasi nomor HP, parsing koordinat cuaca).
   - Berjalan super cepat tanpa ketergantungan database atau request HTTP.
2. **Feature / Integration Testing:**
   - Menguji route, controller, middleware, Livewire component, dan event database.
   - Verifikasi isolasi multi-tenant, autorisasi role, dan transaksi database.
   - Seluruh integrasi API eksternal (Midtrans, Fonnte, Open-Meteo) **wajib di-mock** menggunakan `Http::fake()`.
3. **End-to-End & User Acceptance Testing (UAT):**
   - Pengujian manual dan terpadu menyusuri skenario riil pengguna dari awal hingga akhir (End-to-End lifecycle sewa).

### B. Lingkungan Pengujian (Test Environments)
- **Automated Testing (PHPUnit / CI):**
  - Database: SQLite `:memory:`
  - Queue: `sync`
  - Mail: `array`
  - Cache: `array`
- **Manual UAT & Staging:**
  - Database: PostgreSQL (Docker container)
  - Payment: Midtrans Sandbox Mode
  - WhatsApp: Fonnte Test Device / Personal Number

---

## 3. KRITERIA KELULUSAN (PASS / EXIT CRITERIA)

Sebuah rilis dinyatakan siap (Ready for Production / Demo PBL) apabila:
1. **100% Automated Tests Hijau:** Semua test unit dan feature lulus tanpa error.
2. **Zero Blocker & Major Bugs:** Tidak ada bug dengan tingkat keparahan *Blocker* atau *Major* yang masih berstatus open.
3. **Isolasi Tenant 100% Terbukti:** Vendor A tidak dapat melihat, mengubah, atau menghapus data milik Vendor B.
4. **Code Style Bersih:** Seluruh kode lolos `vendor/bin/pint --format agent` tanpa violation.
5. **UAT Sign-off:** Seluruh skenario inti UAT (Sewa -> Pickup -> Return -> Denda) telah dieksekusi dan disetujui oleh QA Lead.

---

## 4. PERAN & TANGGUNG JAWAB TIM

| Nama Anggota | Peran Tim | Tanggung Jawab QA |
|---|---|---|
| **Boby** | QA Lead / PM | Penyusunan QA plan, pengujian isolasi multi-tenant, approval PR, orkestrasi & sign-off UAT. |
| **Byan** | Developer | Verifikasi pengujian katalog, perhitungan ketersediaan unit, double booking prevention. |
| **Bunga** | Developer | Verifikasi pengujian kasir POS, alur pickup, kalkulasi denda, inspeksi pengembalian barang. |
| **Arsya** | Developer | Verifikasi CRUD produk & unit vendor, seeder database, analitik omzet. |
| **Andini** | Developer | Verifikasi otorisasi RBAC, Google OAuth, skenario transaksi Midtrans sandbox, UI form validation. |
