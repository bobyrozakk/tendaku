# 📋 TEST CASES (DAFTAR SKENARIO PENGUJIAN) — TENDAKU

**Proyek:** TENDAKU — Sistem Informasi Persewaan Tenda & Perlengkapan Acara (Multi-Tenant)  
**QA Lead:** Boby  
**Versi:** 1.0.0  

---

## DAFTAR MASTER TEST CASE

| ID | Modul | Skenario | Langkah Pengujian | Hasil yang Diharapkan | Pemilik | Status |
|---|---|---|---|---|---|---|
| **TC-TEN-001** | Multi-Tenant | Isolasi Baca Data Antar Vendor | 1. Login sebagai Vendor A.<br>2. Query daftar produk/kategori/unit/rental. | Hanya data Vendor A yang tampil. Data Vendor B tidak muncul. | Boby | ✅ Passed |
| **TC-TEN-002** | Multi-Tenant | Percobaan Modifikasi Data Vendor Lain | 1. Login sebagai Vendor A.<br>2. Kirim request PUT/DELETE ke resource Vendor B (misal ID 99). | Sistem merespons **404 Not Found** (tidak membocorkan keberadaan data). | Boby | ✅ Passed |
| **TC-TEN-003** | Multi-Tenant | Auto-fill `vendor_id` saat Create | 1. Login sebagai Vendor A.<br>2. Panggil `MasterItem::create([...])` tanpa mengisi `vendor_id`. | Kolom `vendor_id` otomatis terisi ID Vendor A di database. | Boby | ✅ Passed |
| **TC-TEN-004** | Multi-Tenant | Bypass Global Scope Super Admin | 1. Login sebagai Super Admin.<br>2. Akses query MasterItem/Rental. | Data dari seluruh vendor tampil tanpa filter vendor_id. | Boby | ✅ Passed |
| **TC-TEN-005** | Multi-Tenant | Scoping Worker Background Job | 1. Eksekusi job queue dengan payload `vendor_id`.<br>2. Jalankan `CurrentVendor::runAs()`. | Query di dalam job hanya memproses record vendor terkait. | Boby | ✅ Passed |
| **TC-AUT-001** | Auth / RBAC | Matriks Hak Akses Route Vendor | 1. Akses `/vendor/*` sebagai Customer, Guest, dan Super Admin. | Customer & Guest ditolak/redirect; Vendor Owner dapat mengakses; Super Admin dapat bypass. | Andini | ✅ Passed |
| **TC-AUT-002** | Auth / RBAC | Login Google OAuth | 1. Klik tombol Login Google.<br>2. Simulasi callback OAuth dengan email terverifikasi. | User berhasil login dan diarahkan ke dashboard sesuai role. | Andini | ✅ Passed |
| **TC-CUS-001** | Katalog | Pencarian & Filter Produk | 1. Akses halaman `/katalog`.<br>2. Filter berdasarkan nama, kategori, dan rentang harga. | Daftar produk terfilter sesuai parameter input. | Byan | ✅ Passed |
| **TC-CUS-002** | Booking | Pencegahan Double Booking | 1. Kirim 2 request sewa simultan untuk item yang hanya tersisa 1 unit di tanggal sama. | Satu transaksi berhasil terbuat; request kedua gagal dengan `InsufficientStockException`. | Byan | ✅ Passed |
| **TC-PAY-001** | Payment | Pembuatan Snap Token Midtrans | 1. Customer melakukan checkout rental.<br>2. Request Snap token ke MidtransService. | Token Snap string berhasil dihasilkan untuk modal popup Midtrans. | Andini | ✅ Passed |
| **TC-PAY-002** | Payment | Webhook Midtrans Idempoten | 1. Kirim payload webhook `settlement` untuk rental X.<br>2. Kirim payload duplikat yang sama kedua kalinya. | Status transaksi berubah `confirmed`; request kedua direspons 200 tanpa duplikasi record `payments`. | Andini | ✅ Passed |
| **TC-KAS-001** | Kasir Pickup | Serah Terima & Tahan Jaminan KTP | 1. Cari `booking_code` di kasir.<br>2. Scan barcode unit fisik.<br>3. Upload foto KTP pelanggan.<br>4. Submit pickup. | Status rental berubah `picked_up`; unit berstatus `rented`; `ktp_collateral_status = held`. | Bunga | ⏳ Ready for UAT |
| **TC-KAS-002** | Kasir Return | Pengembalian Tepat Waktu (Denda Nol) | 1. Buka alur pengembalian rental.<br>2. Konfirmasi kondisi unit utuh/baik.<br>3. Submit return. | Denda telat = 0, denda rusak = 0; KTP status `returned`; status rental `completed`. | Bunga | ⏳ Ready for UAT |
| **TC-KAS-003** | Kasir Return | Kalkulasi Denda Terlambat & Rusak | 1. Return barang terlambat 2 hari.<br>2. Tandai 1 unit rusak (misal sobek).<br>3. Hitung denda. | `total_late_fee` = 2 x tarif denda harian; `total_damage_fee` terakumulasi; unit status `maintenance`. | Bunga | ⏳ Ready for UAT |
| **TC-KAS-004** | Kasir Return | Pelunasan Denda Tunai / QRIS | 1. Lakukan pelunasan denda via tunai/QRIS kasir.<br>2. Konfirmasi pembayaran. | Pembayaran tercatat di tabel `payments`; status rental menjadi `completed`. | Bunga | ⏳ Ready for UAT |
| **TC-ADM-001** | Super Admin | Approval Vendor Baru | 1. Vendor baru mendaftar (status `pending`).<br>2. Super Admin klik Approve di panel `/admin/vendors`. | Status vendor berubah `approved`; vendor dapat login dan memulai operasional toko. | Boby | ⏳ Fase 1 |
| **TC-ADM-002** | Super Admin | Akurasi Perhitungan GMV | 1. Cek total transaksi di GMV dashboard.<br>2. Verifikasi rental `confirmed` s.d. `completed` terjumlah.<br>3. Verifikasi `pending`/`cancelled` tidak ikut dihitung. | Angka GMV harian/bulanan cocok dengan database. | Boby | ⏳ Fase 1 |
| **TC-NOT-001** | Notifikasi | Pengingat Pengembalian via WhatsApp | 1. Jalankan command scheduler reminder H-1 dan Hari-H.<br>2. Verifikasi pesan terkirim via mock HTTP. | Pesan terkirim dengan nomor terformat 628xxx; tercatat di `notification_logs`; tidak terkirim ganda. | Boby | ⏳ Fase 2 |
| **TC-WEA-001** | Weather | Prakiraan Cuaca & Deteksi Ekstrem | 1. Panggil `WeatherService::forecast('bromo', ...)`.<br>2. Uji respon kondisi badai/petir (kode WMO 95). | Format JSON valid; flag `is_extreme = true`; data dicache 30 menit. | Boby | ⏳ Fase 3 |
