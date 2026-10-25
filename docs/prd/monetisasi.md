# 💰 USULAN MONETISASI PLATFORM — TENDAKU

**Status:** 🚧 Implementasi bertahap — NTN-62 ✅ (2026-10-24), NTN-63 ✅ (2026-10-25), NTN-64 ✅ (2026-10-25); NTN-65–NTN-68 menyusul<br>
**Pemilik dokumen:** Boby Rozak (PM · Super Admin · QA Lead)  
**Tanggal:** 24 Oktober 2026  
**Disetujui:** Boby Rozak (PM), 24 Oktober 2026  
**Acuan:** `PRD_BOBY.md`, `PRD.md`, `PRD_ROLES.md`, `docs/contract.md`, `excel/gantchart_tendaku.xlsx`

> Dokumen ini adalah **usulan perencanaan** yang kini dieksekusi bertahap. NTN-62 (engine komisi + skema), NTN-63 (escrow + akrual komisi di webhook), dan NTN-64 (dashboard pendapatan superadmin + filter/chart + export CSV) sudah diimplementasikan; rincian skema aktual dicatat di `docs/contract.md` §5. Tabel/kolom yang belum memiliki migrasi tetap berstatus rencana, termasuk `settlements` (NTN-67).

---

## 0. RINGKASAN EKSEKUTIF

TENDAKU sudah memiliki alur pembayaran Midtrans SNAP, halaman kasir, dashboard GMV super admin, dan analitik owner. Namun belum ada mekanisme **pendapatan platform**: belum ada komisi, escrow, pencairan ke vendor, laporan pendapatan, dan invoice.

Usulan ini menetapkan model monetisasi **lite** yang layak untuk PBL:

1. Platform memungut **komisi 5% all-in** dari setiap pembayaran **DP/pelunasan** yang sah, dengan **minimum Rp2.500**.
2. **MDR QRIS dan biaya transfer ditanggung platform** (tidak mengurangi hak vendor).
3. Dana QRIS **ditahan (escrow)** sampai rental berstatus `completed`, lalu dicairkan **batch mingguan** ke rekening vendor.
4. **Denda tidak dikenai komisi**; platform hanya menjadi kanal pembayaran, dan MDR denda dipotong dari remitansi denda.
5. **Komisi dari pembayaran tunai** dicatat sebagai piutang dan **di-net-off** dari pencairan berikutnya.
6. Super admin mendapat halaman `/admin/pendapatan`; owner mendapat `/vendor/pendapatan`; kasir **tidak** melihat komisi/escrow.
7. Ekspor fase lite memakai **CSV native + print view**. Ekspor XLSX asli ditunda sampai dependency `phpoffice/phpspreadsheet` disetujui.

**Dampak:** 7 tiket baru (NTN-62 s.d. NTN-68). Estimasi awal ±42 hari-orang (total effort tim); **Boby mengambil seluruh epic ini sebagai jobdesk** dan menargetkan **5 hari (17–21 November 2026)** dengan bantuan AI. Risiko kompresi jadwal dibahas di §9.4.

---

## 1. LATAR BELAKANG & TUJUAN

### 1.1 Kondisi saat ini

| Komponen | Kondisi | Implikasi |
|---|---|---|
| `payments` | Mencatat DP, pelunasan, deposit, denda, refund; metode `cash`, `midtrans_snap`, `transfer` | Belum ada kolom escrow, channel, atau biaya gateway |
| `MidtransService` | Memakai kredensial platform dari `.env`; webhook memverifikasi signature dan menandai `paid` | Titik integrasi utama untuk escrow/komisi |
| `GmvService` / `GmvDashboard` | Menghitung GMV dari `rentals.final_total` berdasarkan status rental | Bukan pengganti laporan pendapatan platform |
| `VendorAnalyticsService` | Analitik owner dari pembayaran/rental | Belum memisahkan komisi, escrow, dan pencairan |
| Role | `super_admin`, `vendor_owner`, `vendor_admin` (= kasir), `customer` | Tidak ada paket permission; cukup Gate/Policy + middleware role |
| Export | Belum ada ekspor laporan | Fase lite: CSV native, tanpa dependency baru |

### 1.2 Masalah yang diselesaikan

- Platform belum punya sumber pendapatan yang tercatat dan dapat diaudit.
- Dana customer langsung dianggap milik vendor, padahal ada periode escrow dan potensi refund.
- Owner tidak punya laporan pendapatan bersih setelah komisi.
- Kasir tidak punya aturan baku untuk pembayaran tunai vs QRIS.
- Tidak ada rekonsiliasi antara uang masuk, komisi, biaya gateway, dan pencairan.

### 1.3 Tujuan

1. Menetapkan **satu sumber kebenaran** untuk komisi, escrow, dan pencairan.
2. Menyediakan laporan pendapatan yang dapat dipakai super admin dan owner.
3. Menjaga hak vendor: komisi transparan, biaya gateway tidak dibebankan ganda.
4. Menjaga kontrol: kasir tidak dapat melihat/mengubah angka komisi.
5. Menyiapkan fondasi yang bisa dikembangkan ke model produksi (Iris, rekonsiliasi otomatis) tanpa mengubah kontrak inti.

### 1.4 Non-goals (fase lite)

- Integrasi disbursement otomatis Midtrans Iris.
- Akuntansi pajak (PPN/PPh), jurnal umum, atau general ledger.
- Multi-currency.
- Midtrans milik masing-masing vendor.
- Aplikasi mobile.
- Dunning/collection otomatis untuk piutang komisi.
- Ekspor XLSX native (ditunda).

---

## 2. KEPUTUSAN YANG SUDAH DISETUJUI

Disetujui Boby Rozak pada 24 Oktober 2026. Angka ini menjadi default; perubahan harus melalui `docs/contract.md` §6.

| # | Keputusan | Nilai final | Catatan |
|---|---|---|---|
| 1 | Tarif komisi | **5% all-in** dari pembayaran sah | Berlaku untuk `dp` dan `settlement` |
| 2 | Minimum komisi | **Rp2.500** per pembayaran | Mencegah komisi mikro |
| 3 | Beban MDR & transfer | **Platform** | Vendor menerima bersih setelah komisi saja |
| 4 | Waktu pencairan | Setelah rental `completed`; **batch mingguan** | Manual transfer + bukti untuk PBL |
| 5 | Denda | **Tanpa komisi** | MDR denda dipotong dari remitansi denda |
| 6 | Komisi tunai | **Piutang**, di-net-off dari pencairan berikutnya | Jika tidak ada pencairan, menjadi saldo utang vendor |
| 7 | Scope & export | **Sprint 5 hari Boby (lite), 17–21 Nov 2026**; CSV native dulu | XLSX asli menunggu persetujuan dependency |

### 2.1 Yang tidak berubah

- Akun Midtrans tetap **milik platform** (satu akun), bukan akun per vendor.
- Tidak menambah paket permission (Spatie) — cukup role + Gate/Policy.
- Tidak menambah dependency frontend/chart; grafik memakai CSS bar seperti `GmvDashboard`.
- Tidak mengubah alur checkout customer di luar penambahan pencatatan pembayaran/escrow.

---

## 3. MODEL BISNIS & ALUR UANG

### 3.1 Aktor

| Aktor | Peran dalam monetisasi |
|---|---|
| Customer | Membayar DP/pelunasan/denda melalui QRIS atau tunai di kasir |
| Midtrans | Gateway QRIS; dana masuk ke rekening platform |
| Platform (super admin) | Menahan dana, menghitung komisi, mencairkan ke vendor, menerbitkan invoice |
| Vendor owner | Melihat pendapatan bersih, escrow, invoice, dan status pencairan |
| Kasir (`vendor_admin`) | Mencatat pembayaran tunai, menampilkan QRIS, tidak melihat komisi |
| Vendor | Menerima pencairan bersih ke rekening di `vendor_payment_settings` |

### 3.2 Alur uang QRIS

```text
Customer
  │  bayar QRIS (DP / pelunasan / denda)
  ▼
Midtrans (akun platform)
  │  settlement ke rekening platform
  ▼
Platform escrow ledger
  │  komisi 5% (min Rp2.500) dipotong
  │  MDR & biaya transfer ditanggung platform
  ▼
Batch settlement mingguan (setelah rental completed)
  │  transfer manual + bukti
  ▼
Rekening vendor
```

### 3.3 Alur uang tunai

```text
Customer ── tunai ──▶ Kasir/Vendor (langsung diterima vendor)
                         │
                         └─ sistem mencatat komisi sebagai piutang platform
                                      │
                                      ▼
                         dipotong dari pencairan QRIS berikutnya
```

### 3.4 Alur denda

```text
Customer ── QRIS denda ──▶ Midtrans/platform ──▶ vendor (dikurangi MDR)
Customer ── tunai denda ─▶ Kasir/vendor (langsung)
```

- Denda **tidak** dikenai komisi 5%.
- Untuk denda QRIS, platform hanya meneruskan dana; MDR riil dipotong dari jumlah yang diretur ke vendor agar platform tidak menanggung biaya transaksi yang bukan pendapatannya.
- Untuk denda tunai, tidak ada keterlibatan platform.

---

## 4. KEBIJAKAN TARIF & RUMUS

### 4.1 Rumus komisi

Untuk pembayaran sah bertipe `dp` atau `settlement`:

```text
K = max( round(P × r), F_min )

K     = komisi platform (rupiah)
P     = nominal pembayaran (rupiah)
r     = tarif efektif vendor (default 0,05; bisa override di vendors.commission_rate)
F_min = minimum komisi (default 2.500)
round = pembulatan ke rupiah terdekat (half-up)
```

Contoh: `P = Rp500.000`, `r = 5%` → `K = Rp25.000`.

> Minimum Rp2.500 berlaku **per pembayaran**, bukan per rental. Rental yang dipecah menjadi DP dan pelunasan kecil bisa terkena minimum dua kali; pertimbangkan ini saat memilih skema DP.

### 4.2 Rumus MDR QRIS

Mengikuti kebijakan BI yang berlaku 1 Oktober 2026 untuk merchant UKE/UME/UBE:

```text
M = 0,                          jika P ≤ Rp100.000
M = round(P × 0,007),           jika P > Rp100.000
```

Catatan:
- Merchant UMi memiliki aturan berbeda (0% ≤ Rp500.000; 0,3% di atasnya). Untuk PBL, asumsikan kategori UKE/UME/UBE.
- Midtrans mencantumkan tarif QRIS 0,7%; kebijakan 0% untuk transaksi kecil mengikuti ketentuan BI. **Verifikasi ulang tarif final di dashboard Midtrans sebelum produksi.**
- Untuk channel non-QRIS (VA, e-wallet, kartu), `gateway_fee_amount` diisi sesuai harga Midtrans riil; jangan memakai rumus QRIS.

### 4.3 Biaya transfer

- Default: **Rp3.500 per batch** (asumsi transfer SKN/LLG via Iris/manual).
- Biaya transfer adalah biaya platform, **tidak dipotong** dari hak vendor.
- Jika transfer riil berbeda (mis. Rp5.000 untuk realtime), selisih dicatat di `settlements.transfer_fee` dan menjadi beban platform.

### 4.4 Rumus pencairan vendor

Untuk pembayaran QRIS standar:

```text
N_v = P - K
```

Untuk denda QRIS:

```text
N_v = P - M
```

Untuk batch:

```text
Payout = Σ(P_qris - K_qris) - Σ K_tunai - Σ M_denda + penyesuaian
```

Biaya transfer **tidak** mengurangi `Payout`; biaya itu mengurangi pendapatan platform.

### 4.5 Laba platform per transaksi

```text
Laba platform = Σ K - Σ M_standar - T - Σ M_denda_yang_ditanggung_platform
```

Karena MDR denda dipotong dari remitansi vendor, komponen `M_denda` menjadi 0 bagi platform.

### 4.6 Simulasi dan titik impas

Asumsi: satu pembayaran QRIS per batch (skenario terburuk biaya transfer), `r = 5%`, `F_min = Rp2.500`, `T = Rp3.500`.

| Nominal P | Komisi K | MDR M | Transfer T | Laba platform | Margin efektif |
|---:|---:|---:|---:|---:|---:|
| Rp50.000 | Rp2.500 | Rp0 | Rp3.500 | **-Rp1.000** | -2,00% |
| Rp70.000 | Rp3.500 | Rp0 | Rp3.500 | **Rp0** | 0,00% |
| Rp100.000 | Rp5.000 | Rp0 | Rp3.500 | **Rp1.500** | 1,50% |
| Rp150.000 | Rp7.500 | Rp1.050 | Rp3.500 | **Rp2.950** | 1,97% |
| Rp300.000 | Rp15.000 | Rp2.100 | Rp3.500 | **Rp9.400** | 3,13% |
| Rp500.000 | Rp25.000 | Rp3.500 | Rp3.500 | **Rp18.000** | 3,60% |
| Rp1.000.000 | Rp50.000 | Rp7.000 | Rp3.500 | **Rp39.500** | 3,95% |
| Rp1.200.000 | Rp60.000 | Rp8.400 | Rp3.500 | **Rp48.100** | 4,01% |

**Interpretasi:**
- Titik impas operasional sekitar **Rp70.000** jika satu transaksi ditanggung satu biaya transfer.
- Karena pencairan berbentuk batch mingguan, biaya transfer terbagi ke banyak transaksi; margin mendekati **5% - MDR** (4,3% untuk transaksi > Rp100.000; 5% untuk ≤ Rp100.000).
- Transaksi kecil ≤ Rp50.000 bisa merugi jika dicairkan sendiri. Mitigasi: **gulirkan pencairan** jika saldo batch di bawah ambang operasional (usulan: Rp50.000) atau gabungkan dengan transaksi lain dalam batch.

### 4.7 Contoh perhitungan lengkap

**Contoh A — QRIS DP Rp500.000**

| Komponen | Nilai |
|---|---:|
| Pembayaran | Rp500.000 |
| Komisi 5% | Rp25.000 |
| MDR (0,7%) | Rp3.500 |
| Hak vendor | Rp475.000 |
| Laba platform sebelum transfer | Rp21.500 |

**Contoh B — QRIS pelunasan Rp700.000**

| Komponen | Nilai |
|---|---:|
| Pembayaran | Rp700.000 |
| Komisi 5% | Rp35.000 |
| MDR (0,7%) | Rp4.900 |
| Hak vendor | Rp665.000 |
| Laba platform sebelum transfer | Rp30.100 |

**Contoh C — Satu rental Rp1.200.000 (DP Rp500.000 + pelunasan Rp700.000), satu batch**

| Komponen | Nilai |
|---|---:|
| Total pembayaran | Rp1.200.000 |
| Komisi | Rp60.000 |
| MDR | Rp8.400 |
| Transfer | Rp3.500 |
| Hak vendor | Rp1.140.000 |
| Laba platform | **Rp48.100** |

Rekonsiliasi: `1.140.000 + 48.100 + 8.400 + 3.500 = 1.200.000`.

**Contoh D — Tunai Rp500.000**

| Komponen | Nilai |
|---|---:|
| Diterima vendor | Rp500.000 |
| Komisi platform | Rp25.000 |
| Piutang komisi | Rp25.000 |
| Efek ke pencairan berikutnya | -Rp25.000 |

**Contoh E — Denda QRIS Rp200.000**

| Komponen | Nilai |
|---|---:|
| Denda | Rp200.000 |
| Komisi | Rp0 |
| MDR (0,7%) | Rp1.400 |
| Diterima vendor | Rp198.600 |
| Laba platform | Rp0 |

### 4.8 Pembulatan

- Semua nominal disimpan `decimal(14,2)` tetapi perhitungan komisi/MDR dibulatkan ke **rupiah utuh**.
- Gunakan `(int) round($amount)` di PHP; jangan memakai `floor` agar tidak merugikan vendor.
- Selisih pembulatan Rp1 dicatat di `settlements.adjustment_amount` bila perlu.

### 4.9 Referensi tarif

- **BI — QRIS MDR**: 0% untuk transaksi ≤ Rp100.000 (UKE/UME/UBE) dan 0,7% di atasnya, efektif 1 Oktober 2026; UMi 0% ≤ Rp500.000 dan 0,3% di atasnya. Sumber: publikasi BI/Kontan, Oktober 2026.
- **Midtrans — Pricing**: QRIS 0,7%; VA Rp4.000; e-wallet bervariasi. Sumber: <https://midtrans.com/pricing>.
- **Midtrans Iris**: transfer bank Rp3.500–Rp5.000 per transaksi (SKN/LLG vs realtime). Sumber: dokumentasi Iris.
- **Benchmark marketplace**: Shopee/Lazada/TikTok/Blibli umumnya 5–15%; OTA 15–25%. Komisi 5% berada di batas bawah dan kompetitif untuk PBL.

> Semua tarif bersifat dinamis. Sebelum produksi, verifikasi ulang ke dashboard Midtrans dan ketentuan BI terbaru. Jangan hard-code angka di banyak tempat; gunakan `config/monetization.php`.

---

## 5. ESCROW & SETTLEMENT

### 5.1 Definisi status escrow

`payments.escrow_status`:

| Status | Arti | Transisi |
|---|---|---|
| `not_applicable` | Pembayaran tunai/transfer manual di luar escrow | default untuk `cash` |
| `held` | Dana QRIS sudah diterima platform, ditahan | saat webhook `paid` |
| `released` | Dana sudah masuk batch pencairan | saat settlement `paid` |
| `refunded` | Dana dikembalikan ke customer | refund disetujui |
| `void` | Pembayaran batal/expired | webhook gagal/expire |

### 5.2 Syarat pembayaran masuk settlement

Sebuah `payment` layak masuk batch jika:

1. `payments.status = 'paid'`.
2. `payments.escrow_status = 'held'` untuk QRIS, atau `payments.payment_method = 'cash'` untuk komisi tunai.
3. `rentals.status = 'completed'`.
4. Tidak ada refund/dispute terbuka.
5. `platform_commissions.status = 'accrued'` (jika ada komisi).
6. Belum pernah masuk `settlement_items` lain.

Pembayaran DP yang sudah lunas tetapi rental belum selesai **tetap ditahan**.

### 5.3 Batch mingguan

- Jadwal: setiap **Senin 01:00 WIB**, mencakup transaksi **Senin–Minggu sebelumnya** (atau definisi periode yang disepakati).
- Untuk PBL, batch dibuat **manual** lewat tombol/command, bukan otomatis production.
- Satu vendor = satu batch per periode.
- Status batch: `draft` → `approved` → `paid`; bisa `cancelled` sebelum `paid`.
- Super admin memeriksa, menyetujui, transfer manual, lalu mengunggah bukti/referensi.
- Owner melihat status dan invoice setelah `approved`/`paid`.

### 5.4 Perhitungan batch

```text
gross_qris          = Σ pembayaran QRIS standar
gross_denda_qris    = Σ denda QRIS
commission_qris     = Σ komisi QRIS (0 untuk denda)
commission_total    = Σ komisi (QRIS + tunai)
gateway_fee_total   = Σ MDR QRIS standar (beban platform)
fine_fee_total      = Σ MDR denda (dipotong dari vendor)
cash_commission     = Σ komisi tunai
transfer_fee        = biaya transfer batch (beban platform)

hak_vendor_qris     = gross_qris + gross_denda_qris - commission_qris - fine_fee_total
payout              = hak_vendor_qris - cash_commission + adjustment
platform_net        = commission_total - gateway_fee_total - transfer_fee
```

Jika `payout < 0`:
- Jangan transfer.
- Catat sebagai saldo utang vendor (`vendor_balances` debit).
- Gulirkan ke batch berikutnya.

Jika `payout = 0` dan tidak ada transaksi, batch tidak perlu dibuat.

### 5.5 Komisi tunai (net-off)

- Saat kasir mencatat pembayaran tunai, sistem membuat `platform_commissions` dengan `status = 'accrued'` dan `payment_method = 'cash'`.
- Komisi ini **tidak** mengurangi uang tunai yang sudah diterima vendor (vendor sudah memegang uangnya).
- Pada batch berikutnya, `cash_commission` dikurangkan dari payout QRIS.
- Jika vendor tidak punya QRIS sama sekali, buat invoice piutang; owner melihat saldo utang dan cara pembayaran.
- Jangan pernah menagih komisi tunai langsung ke customer.

### 5.6 Denda

- `payment_type = 'fine'` → komisi 0.
- QRIS denda: `escrow_status = 'held'` sampai batch; `N_v = P - M`.
- Tunai denda: tidak masuk escrow; vendor menerima penuh; tidak ada komisi.
- Denda tidak boleh dihitung sebagai GMV/omzet rental; tetap dicatat sebagai pendapatan lain vendor.

### 5.7 Refund, pembatalan, dan kegagalan

| Kejadian | Aksi |
|---|---|
| Webhook `expire`/`deny`/`cancel` | `payments.status = failed`; `escrow_status = void`; komisi belum dibuat |
| Refund penuh sebelum settlement | `payments.status = refunded`; `escrow_status = refunded`; komisi `void` |
| Refund setelah settlement | Buat penyesuaian negatif di batch berikutnya; jangan hapus histori |
| Rental cancelled setelah DP | Refund sesuai kebijakan; komisi `void` |
| Partial refund | Komisi dihitung ulang dari nominal yang dipertahankan; selisih menjadi adjustment |
| Dispute/chargeback | Tahan payout vendor terkait; eskalasi manual |

> Kebijakan refund detail belum final. Untuk PBL, gunakan refund manual dengan persetujuan super admin dan catat di `settlement_items` tipe `adjustment`.

### 5.8 Invoice

- Setiap batch menghasilkan **invoice komisi** dan **rekap pencairan**.
- Nomor invoice: `INV/{vendor_id}/{YYYYMM}/{sequence}`.
- Fase lite: invoice HTML + print view (browser print to PDF), bukan file PDF tersimpan.
- Isi invoice: periode, gross, komisi, MDR, denda, transfer, payout, status, referensi transfer.
- Owner dapat mengunduh CSV rekap dan mencetak invoice.

---

## 6. HALAMAN, ROLE, & AKSES

### 6.1 Matriks akses

| Halaman | super_admin | vendor_owner | vendor_admin (kasir) | customer |
|---|---:|---:|---:|---:|
| `/admin/pendapatan` | ✅ penuh | ❌ | ❌ | ❌ |
| `/admin/settlements` | ✅ approve/paid | ❌ | ❌ | ❌ |
| `/vendor/pendapatan` | ✅ (opsional) | ✅ vendor sendiri | ❌ | ❌ |
| `/vendor/pendapatan/invoice/{id}` | ✅ | ✅ vendor sendiri | ❌ | ❌ |
| `/kasir/pembayaran` | ✅ | ✅ | ✅ | ❌ |
| Riwayat pembayaran customer | ✅ | ✅ | ✅ | ✅ milik sendiri |

Aturan keras:
- Kasir **tidak boleh** melihat komisi, escrow, laba platform, atau payout vendor.
- Owner **tidak boleh** melihat data vendor lain.
- Export CSV mengikuti filter dan otorisasi halaman; jangan hanya menyembunyikan tombol.
- Invoice print memeriksa `vendor_id` invoice terhadap user.

### 6.2 Super admin — `/admin/pendapatan`

**KPI cards:**
- Komisi hari ini / 7 hari / bulan ini / tahun ini.
- Escrow ditahan (total).
- Batch menunggu approval.
- Total payout bulan ini.
- Perbandingan dengan periode sebelumnya (delta %).

**Chart tabs:**
- Harian — 30 hari terakhir.
- Mingguan — 12 minggu.
- Bulanan — 12 bulan (label MoM).
- Tahunan — 5 tahun.

**Filter:**
- Vendor (semua/satu).
- Periode (preset + custom).
- Metode (`qris`, `cash`, `transfer`).
- Status escrow.
- Tipe pembayaran (`dp`, `settlement`, `fine`).

**Tabel:**
1. Ringkasan per vendor: GMV, komisi, escrow, payout, piutang tunai.
2. Transaksi: kode booking, vendor, nominal, komisi, MDR, status escrow, batch.
3. Batch settlement: periode, status, payout, aksi.

**Aksi:**
- Export CSV sesuai filter.
- Print view.
- Buka detail batch, approve, tandai paid, unggah bukti.

> **Status implementasi NTN-64 (2026-10-25):** route `/admin/pendapatan` dan `/admin/pendapatan/export` aktif untuk `super_admin` (role lain HTTP 403, guest diarahkan ke `superadmin.login`). Filter periode/vendor/metode/escrow/tipe, chart 30 hari/12 minggu/12 bulan/5 tahun, ringkasan per vendor, dan tabel 50 transaksi terbaru sudah tersedia. KPI **batch menunggu approval** dan **total payout bulan ini** sengaja bernilai **0** karena tabel `settlements` baru dibuat di NTN-67. Laba platform ditampilkan sebagai estimasi `komisi - MDR standar - (biaya transfer × jumlah vendor)`, bukan hasil batch final.

### 6.3 Owner — `/vendor/pendapatan`

- Komponen yang sama dengan super admin, tetapi **scoped** `vendor_id` dan tanpa data platform.
- KPI: pendapatan kotor, komisi, pendapatan bersih, escrow ditahan, payout terakhir, piutang komisi tunai.
- Chart dan filter sama (tanpa filter vendor).
- Tabel transaksi dan batch.
- Banner: "Dana QRIS ditahan sampai rental selesai; pencairan setiap Senin."
- Daftar invoice + tombol print/CSV.
- Peringatan jika saldo piutang komisi tunai > 0.

### 6.4 Kasir — `/kasir/pembayaran`

- Daftar tagihan customer: DP, pelunasan, denda.
- Tombol "Bayar Tunai" → buat `payments` cash + komisi piutang.
- Tombol "QRIS" → tampilkan Snap/QR (platform), polling status.
- Rekap shift: tunai masuk, QRIS masuk, denda, jumlah transaksi.
- Tidak ada menu komisi/escrow.
- Kasir tetap bisa melihat status pembayaran dan sisa tagihan.

### 6.5 Customer

- Melihat status pembayaran dan struk.
- Tidak melihat komisi, MDR, atau escrow internal.
- Untuk PBL, struk cukup menampilkan nominal, metode, status, dan kode booking.

### 6.6 Route & middleware (rencana)

| Route | Name | Middleware | Komponen/Controller |
|---|---|---|---|
| `GET /admin/pendapatan` | `admin.pendapatan` | `auth`, `verified`, `role:super_admin` | `Admin\RevenueDashboard` |
| `GET /admin/pendapatan/export` | `admin.pendapatan.export` | sama | `Admin\RevenueExportController@csv` |
| `GET /admin/settlements` | `admin.settlements` | sama | `Admin\SettlementManager` |
| `GET /vendor/pendapatan` | `vendor.pendapatan` | `auth`, `verified`, `vendor.owner`, `vendor.active` | `Vendor\RevenueDashboard` |
| `GET /vendor/pendapatan/invoice/{invoice}` | `vendor.pendapatan.invoice` | sama | `Vendor\InvoiceController@print` |
| `GET /kasir/pembayaran` | `kasir.pembayaran` | `auth`, `verified`, `role:vendor_admin`, `vendor.active` | `Kasir\PaymentRecap` |

Catatan: route admin yang sudah ada memakai prefix `admin` dan middleware role; ikuti pola di `routes/web.php`, jangan membuat grup baru yang duplikatif.

---

## 7. RANCANGAN DATA

> **Penting:** semua tabel/kolom di bawah ini masih **usulan**. Belum ada migrasi. Setelah migrasi dibuat, barulah dicatat di `docs/contract.md` §5.

### 7.1 Perubahan tabel yang sudah ada

#### `payments`

| Kolom baru | Tipe | Default | Keterangan |
|---|---|---|---|
| `payment_channel` | `string(30) nullable` | `null` | `qris`, `va_bca`, `gopay`, `shopeepay`, `card`, dll. |
| `gateway_fee_amount` | `decimal(12,2)` | `0` | MDR/biaya gateway riil per pembayaran |
| `escrow_status` | `enum('not_applicable','held','released','refunded','void')` | `not_applicable` | Status dana |
| `settlement_id` | `foreignId nullable` | `null` | Batch tempat pembayaran dicairkan |

Catatan:
- Jangan menambah `settled` ke `payments.status`. Status pembayaran tetap `pending|paid|failed|refunded`; status pencairan ada di `settlements`/`settlement_items`.
- `VendorAnalyticsService` saat ini menyebut status `settled` yang tidak ada di enum. Saat implementasi, ganti logika tersebut agar memakai relasi settlement, bukan status fiktif.
- Laporan omzet rental harus mengecualikan `payment_type = fine`; denda adalah pendapatan lain vendor, bukan omzet sewa.
- `payment_method = midtrans_snap` tetap; `payment_channel` memperjelas kanal.

#### `vendors`

| Kolom baru | Tipe | Default | Keterangan |
|---|---|---|---|
| `commission_rate` | `decimal(5,4) nullable` | `null` | Override tarif; `null` = config global 5% |

### 7.2 Tabel baru

#### `platform_commissions`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `payment_id` | FK unique | Satu komisi per pembayaran |
| `rental_id` | FK | Untuk laporan per rental |
| `vendor_id` | FK | Scoping |
| `base_amount` | decimal(14,2) | Nominal pembayaran |
| `rate` | decimal(5,4) | Snapshot tarif saat transaksi |
| `amount` | decimal(14,2) | Komisi final |
| `status` | enum(`accrued`,`settled`,`void`) | |
| `settlement_id` | FK nullable | Diisi saat batch |
| `recognized_at` | timestamp | Saat komisi diakui |
| `voided_at` | timestamp nullable | Saat refund/void |
| `notes` | text nullable | |
| timestamps | | |

Index: `(vendor_id, status)`, `rental_id`, `settlement_id`; unique `payment_id`.

#### `settlements`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `vendor_id` | FK | |
| `period_start` | date | |
| `period_end` | date | |
| `status` | enum(`draft`,`approved`,`paid`,`cancelled`) | |
| `gross_qris` | decimal(14,2) | |
| `gross_fine_qris` | decimal(14,2) | |
| `commission_total` | decimal(14,2) | Termasuk komisi tunai |
| `gateway_fee_total` | decimal(14,2) | MDR standar (beban platform) |
| `fine_fee_total` | decimal(14,2) | MDR denda (dipotong vendor) |
| `cash_commission_offset` | decimal(14,2) | |
| `transfer_fee` | decimal(14,2) | Default 3.500 |
| `adjustment_amount` | decimal(14,2) | Default 0 |
| `net_payout` | decimal(14,2) | |
| `bank_name`, `bank_account_number`, `bank_account_name` | string | Snapshot rekening |
| `reference_number` | string nullable | Referensi transfer |
| `proof_url` | string nullable | Bukti transfer |
| `approved_by` | FK users nullable | |
| `approved_at` | timestamp nullable | |
| `paid_at` | timestamp nullable | |
| `notes` | text nullable | |
| timestamps | | |

Unique: `(vendor_id, period_start, period_end)`.

#### `settlement_items`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `settlement_id` | FK cascade | |
| `payment_id` | FK nullable | Null untuk adjustment |
| `platform_commission_id` | FK nullable | |
| `vendor_id` | FK | Scoping/audit |
| `type` | enum(`payment`,`fine`,`cash_commission`,`adjustment`) | |
| `gross_amount` | decimal(14,2) | |
| `commission_amount` | decimal(14,2) | |
| `fee_amount` | decimal(14,2) | |
| `net_amount` | decimal(14,2) | |
| `notes` | text nullable | |
| timestamps | | |

Unique: `(settlement_id, payment_id, type)` (dengan toleransi null untuk adjustment).

#### `vendor_invoices`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `vendor_id` | FK | |
| `settlement_id` | FK nullable | |
| `number` | string unique | `INV/{vendor}/{YYYYMM}/{seq}` |
| `type` | enum(`commission`,`settlement`) | |
| `period_start`, `period_end` | date | |
| `subtotal` | decimal(14,2) | |
| `commission_amount` | decimal(14,2) | |
| `fee_amount` | decimal(14,2) | |
| `total` | decimal(14,2) | |
| `status` | enum(`draft`,`issued`,`paid`,`void`) | |
| `issued_at`, `paid_at` | timestamp nullable | |
| `notes` | text nullable | |
| timestamps | | |

#### `vendor_balances` (opsional, direkomendasikan)

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `vendor_id` | FK | |
| `entry_type` | enum(`credit`,`debit`,`adjustment`) | |
| `amount` | decimal(14,2) | |
| `balance_after` | decimal(14,2) | |
| `reference_type`, `reference_id` | string, bigint nullable | Polymorphic ringan |
| `notes` | text nullable | |
| timestamps | | |

Gunakan untuk escrow, piutang komisi tunai, dan carry-forward saldo negatif. Jika waktu sempit, bisa ditunda dan diturunkan dari tabel lain.

### 7.3 Model & trait

| Model | Trait | Relasi penting |
|---|---|---|
| `PlatformCommission` | `BelongsToVendor` | `payment`, `rental`, `vendor`, `settlement` |
| `Settlement` | `BelongsToVendor` | `vendor`, `items`, `invoices`, `approvedBy` |
| `SettlementItem` | `BelongsToVendor` | `settlement`, `payment`, `commission` |
| `VendorInvoice` | `BelongsToVendor` | `vendor`, `settlement` |
| `VendorBalance` | `BelongsToVendor` | `vendor` |
| `Payment` | sudah | tambah `commission`, `settlementItem`, `settlement` |
| `Vendor` | — | tambah `commissions`, `settlements`, `invoices`, `balances` |

Semua model dengan `vendor_id` **wajib** memakai `BelongsToVendor` sesuai `docs/contract.md` §2. Saat super admin membuat batch/item untuk vendor tertentu, isi `vendor_id` **eksplisit**; trait hanya auto-isi dari user vendor yang login.

### 7.4 Config

Buat `config/monetization.php`:

```php
<?php

return [
    'commission' => [
        'rate' => (float) env('MONETIZATION_COMMISSION_RATE', 0.05),
        'minimum' => (int) env('MONETIZATION_COMMISSION_MINIMUM', 2500),
    ],

    'qris' => [
        'mdr_threshold' => (int) env('MONETIZATION_QRIS_MDR_THRESHOLD', 100000),
        'mdr_rate_below_threshold' => (float) env('MONETIZATION_QRIS_MDR_RATE_BELOW', 0.0),
        'mdr_rate_above_threshold' => (float) env('MONETIZATION_QRIS_MDR_RATE_ABOVE', 0.007),
    ],

    'settlement' => [
        'schedule' => 'weekly',
        'day' => 'monday',
        'time' => '01:00',
        'timezone' => 'Asia/Jakarta',
        'transfer_fee' => (int) env('MONETIZATION_TRANSFER_FEE', 3500),
        'minimum_payout' => (int) env('MONETIZATION_MINIMUM_PAYOUT', 50000),
    ],

    'fine_commission_rate' => 0.0,
    'cash_commission_net_off' => true,
];
```

Tambahkan variabel opsional ke `.env.example` dengan komentar, tanpa nilai rahasia.

### 7.5 Urutan migrasi yang diusulkan

1. `add_monetization_fields_to_payments_table`
2. `add_commission_rate_to_vendors_table`
3. `create_platform_commissions_table`
4. `create_settlements_table`
5. `create_settlement_items_table`
6. `create_vendor_invoices_table`
7. `create_vendor_balances_table` (opsional)

Semua migrasi harus memiliki `down()` yang aman dan diuji di SQLite/PostgreSQL sesuai environment proyek.

### 7.6 Catatan integritas

- Gunakan transaksi DB saat membuat komisi dari webhook.
- `platform_commissions.payment_id` unik → idempoten.
- `settlements` unik per vendor/periode → cegah batch ganda.
- `settlement_items` unik per batch/pembayaran/tipe → cegah double payout.
- Jangan menyimpan uang dalam `float`; gunakan decimal dan bulatkan di service.
- Snapshot tarif dan rekening bank agar histori tidak berubah saat config/vendor berubah.

---

## 8. ARSITEKTUR APLIKASI

### 8.1 Service

| Service | Tanggung jawab |
|---|---|
| `CommissionCalculator` | Fungsi murni: `commissionFor(float $amount, float $rate): int`, `mdrFor(float $amount): int`, `platformNet(...)`; mudah diuji |
| `CommissionService` | `accrueForPayment(Payment $payment)`, `voidForPayment(Payment $payment)`, `eligibleForSettlement(...)` |
| `EscrowService` | Transisi `held`/`released`/`refunded`/`void`; validasi transisi |
| `SettlementService` | `buildWeeklyDraft(Carbon $weekStart)`, `approve`, `markPaid`, `generateInvoice` |
| `RevenueReportService` | KPI, series harian/mingguan/bulanan/tahunan, tabel per vendor/transaksi; filter |
| `RevenueCsvExporter` | Stream CSV; tidak memuat seluruh data ke memory |

### 8.2 Livewire / controller

| Komponen | Halaman |
|---|---|
| `App\Livewire\Admin\RevenueDashboard` | `/admin/pendapatan` |
| `App\Livewire\Admin\SettlementManager` | `/admin/settlements` |
| `App\Livewire\Vendor\RevenueDashboard` | `/vendor/pendapatan` |
| `App\Livewire\Kasir\PaymentRecap` | `/kasir/pembayaran` |

Controller tipis untuk export/print:
- `App\Http\Controllers\Admin\RevenueExportController`
- `App\Http\Controllers\Vendor\InvoiceController`

### 8.3 Reuse pola yang sudah ada

- Chart CSS bar: tiru `app/Livewire/Admin/GmvDashboard.php` dan blade-nya; jangan tambah library chart.
- Scoping owner: tiru `OwnerDashboardController` + `VendorAnalyticsService`.
- Tabel dan filter: gunakan komponen Blade/Livewire yang sudah ada di halaman GMV/owner.
- Layout: `layouts/admin`, `layouts/vendor`, `layouts/app` (sesuai halaman kasir).

### 8.4 Integrasi webhook

Di `MidtransService::handleNotification()` setelah `$payment->save()`:

```text
if ($payment->status === 'paid') {
    if ($payment->payment_method === 'midtrans_snap') {
        escrowService->markHeld($payment);
    }
    commissionService->accrueForPayment($payment); // service memfilter dp/settlement
}
if ($payment->status === 'refunded') {
    escrowService->markRefunded($payment);
    commissionService->voidForPayment($payment);
}
```

Ketentuan:
- Bungkus dalam transaksi yang sudah ada; jangan membuat transaksi baru di dalam transaksi.
- Idempoten: `firstOrCreate` berdasarkan `payment_id`.
- Jangan mengubah signature verification.
- Perluas test webhook yang sudah ada (`tests/Feature/MidtransPaymentTest.php`, `tests/Concerns/CreatesMidtransFixtures.php`).

### 8.5 Command & scheduler

- `php artisan monetization:generate-settlements {--week=}` — membuat draft batch.
- `php artisan monetization:reconcile` (opsional) — cek pembayaran paid tanpa komisi, batch menggantung.
- Scheduler: Senin 01:00 WIB; untuk PBL, command dijalankan manual saat demo.

### 8.6 Audit trail

- `settlements.approved_by`, `approved_at`, `paid_at`.
- `settlement_items` menyimpan jejak per pembayaran.
- Jangan menghapus data; gunakan `void`/adjustment.
- Jika audit trail umum (NTN-43) sudah ada, hubungkan event approve/paid ke log tersebut.

---

## 9. TIKET & TIMELINE

### 9.1 Tiket baru

| Tiket | Judul | PIC | Mulai | Selesai | Target | Prioritas | Dependensi |
|---|---|---|---|---|---:|---|---|
| **NTN-62** | Engine komisi 5% + config + skema DB + unit test | Boby | 2026-11-17 | 2026-11-17 | 1 hari | Highest | — |
| **NTN-63** | QRIS platform di checkout + webhook escrow/komisi | Boby | 2026-11-17 | 2026-11-18 | 2 hari | Highest | NTN-62; review checkout (Byan), payments (Andini) |
| **NTN-64** | Dashboard Pendapatan Superadmin + filter/chart + export CSV | Boby | 2026-11-18 | 2026-11-19 | 2 hari | High | NTN-62, NTN-63 |
| **NTN-65** | Halaman Pendapatan & Invoice Owner (mirror, scoped) | Boby | 2026-11-20 | 2026-11-20 | 1 hari | High | NTN-64; review analytics (Arsya) |
| **NTN-66** | Aturan kasir: QRIS pelunasan, catat tunai, piutang komisi, rekap shift | Boby | 2026-11-20 | 2026-11-21 | 2 hari | Medium | NTN-62, NTN-63; review kasir (Bunga), payments (Andini) |
| **NTN-67** | Batch settlement mingguan + approve + bukti transfer + invoice | Boby | 2026-11-19 | 2026-11-20 | 2 hari | High | NTN-62, NTN-63 |
| **NTN-68** | Dokumen skema komisi + test matrix + persiapan UAT | Boby | 2026-11-21 | 2026-11-21 | 1 hari | Medium | Semua |

**Total target:** **5 hari Boby (17–21 Nov 2026)**, dengan bantuan AI dan scope lite. Estimasi awal 42 hari-orang adalah total effort tim tanpa kompresi; angka itu tidak dipakai lagi sebagai jadwal. Durasi per tiket di atas adalah **jendela pengerjaan yang saling tumpang tindih**, bukan penjumlahan hari-orang. Cross-module review dan UAT formal berjalan paralel/di luar sprint ini.

### 9.2 Acceptance criteria per tiket

**NTN-62**
- `CommissionCalculator` lulus test batas Rp100.000, minimum Rp2.500, pembulatan, override rate.
- Migrasi berjalan dan `down()` bersih.
- Config `monetization.php` ada; tidak ada angka hard-code di service.
- Tidak ada perubahan perilaku checkout sebelum NTN-63.

**NTN-63**
- Pembayaran QRIS `dp`/`settlement` yang `paid` membuat `platform_commissions` tepat satu.
- `escrow_status = held`; pembayaran tunai `not_applicable`.
- Webhook duplikat tidak membuat komisi ganda.
- Refund/expire tidak meninggalkan komisi aktif.
- Test webhook lama tetap hijau.

**NTN-64**
- Super admin dapat melihat KPI, chart, filter, tabel; vendor lain tidak.
- CSV menghormati filter dan authorization.
- Angka cocok dengan data contoh (Rp1.200.000 → komisi Rp60.000 → laba Rp48.100).
- Tidak ada query N+1 parah; index digunakan.

> **Verifikasi 2026-10-25:** 13 test / 74 assertion untuk dashboard + export; full suite branch 306 test / 1092 assertion hijau; Pint bersih. Index pelaporan ditambahkan lewat `2026_10_25_124318_add_revenue_reporting_indexes.php`. Batch/payout tetap placeholder sampai NTN-67.

**NTN-65**
- Owner hanya melihat data vendor sendiri.
- Invoice hanya bisa dibuka owner yang berhak.
- Tampilan konsisten dengan dashboard admin, tanpa data platform.
- Print view rapi di A4.

**NTN-66**
- Kasir dapat mencatat tunai dan melihat status QRIS.
- Kasir tidak dapat mengakses route pendapatan/komisi.
- Komisi tunai tercatat sebagai piutang.
- Rekap shift benar untuk skenario tunai + QRIS + denda.

**NTN-67**
- Draft batch hanya berisi pembayaran eligible.
- Approve/paid mengubah status dan escrow secara konsisten.
- Bukti transfer tersimpan; invoice terbit.
- Saldo negatif/piutang tunai terbawa ke batch berikutnya.
- Tidak ada double payout saat command dijalankan ulang.

**NTN-68**
- Dokumen skema dan keputusan selaras dengan `docs/contract.md`.
- Test matrix mencakup unit/feature/UAT.
- Skenario UAT dan checklist disiapkan; eksekusi formal dilakukan setelah sprint 5 hari.
- Tidak ada klaim fitur yang belum diimplementasikan.

### 9.3 Penempatan di Gantt

- Tambahkan **section addendum** setelah Sprint 4: `ADDENDUM: MONETISASI PLATFORM — BOBY 5-DAY SPRINT (17–21 Nov 2026)`.
- Nomor baris `6.1`–`6.7`; Key Notion `NTN-62`–`NTN-68`.
- Status `To Do`, progress `0%`, bar `To Do` (abu-abu).
- Bar mingguan: **W (Nov W3 / 16–22 Nov)** untuk semua tiket; tanggal 17–21 Nov.
- Header Gantt tetap `68 Task (43 Selesai, 6 Dalam Proses, 19 Terjadwal)`.
- PIC seluruh tiket: **Boby Rozak**; label `cross-module` tetap dipakai untuk review modul anggota lain.

### 9.4 Keputusan kapasitas: Boby mengambil alih

Boby memutuskan **seluruh epic monetisasi menjadi jobdesknya**, bukan didelegasikan. Target: **5 hari, 17–21 Nov 2026**, dengan bantuan AI dan scope lite. Slot ini dipilih karena NTN-37 selesai 16 Nov dan NTN-43 baru mulai 24 Nov, sehingga jobdesk QA Desember tidak berubah.

Beban nominal tetap **42 hari-orang** bila dikerjakan tim tanpa kompresi. Komitmen 5 hari ini adalah **vertical slice lite** yang mengutamakan alur uang + laporan + test core; bukan berarti 42 hari-orang hilang. Risiko utama dan mitigasinya:

| Risiko | Mitigasi |
|---|---|
| 7 tiket dalam 5 hari | Scope lite; AI untuk scaffolding/test; urutan 62 → 63 → 64/67 → 65/66 → 68 |
| Review modul lain | Review paralel/harian dengan Andini (payments), Byan (checkout), Bunga (kasir), Arsya (analytics) |
| UAT & bugfix | UAT formal setelah sprint; temuan masuk antrean perbaikan, bukan menahan sprint |
| Kualitas | Unit test kalkulator + feature test webhook/settlement wajib hijau sebelum tiket ditutup |

**Di luar 5 hari:** UAT formal, perbaikan temuan, deployment, dan sign-off dosen. Dengan demikian, “5 hari” adalah target implementasi lite Boby, bukan tanggal seluruh epic benar-benar ditutup.

### 9.5 Timeline 5 hari (17–21 Nov 2026)

```text
Sel 17 Nov  NTN-62  Engine komisi + skema + unit test
            NTN-63  Mulai QRIS + webhook escrow
Rab 18 Nov  NTN-63  Selesai webhook/escrow
            NTN-64  Mulai dashboard admin + CSV
Kam 19 Nov  NTN-64  Selesai dashboard + CSV
            NTN-67  Mulai batch settlement
Jum 20 Nov  NTN-67  Selesai approve + bukti + invoice
            NTN-65  Halaman owner + invoice (review Arsya)
            NTN-66  Mulai aturan kasir
Sab 21 Nov  NTN-66  Selesai rekap shift + piutang
            NTN-68  Dokumen + test matrix + persiapan UAT
```

---

## 10. RENCANA PENGUJIAN

### 10.1 Unit test

`tests/Unit/CommissionCalculatorTest.php`:
- 5% dari 500.000 = 25.000.
- 5% dari 50.000 = 2.500 (batas minimum).
- 5% dari 10.000 = 2.500 (minimum menang).
- 5% dari 100.000 = 5.000.
- MDR 0 untuk 100.000; 700 untuk 100.001; 7.000 untuk 1.000.000.
- Override rate 3% → 15.000 untuk 500.000.
- Pembulatan 5% dari 99.999 = 5.000 (bukan 4.999,95).
- Tipe `fine`/`deposit`/`refund` → komisi 0.

### 10.2 Feature test

- `CommissionAccrualTest`: webhook paid membuat komisi; duplikat tidak menggandakan; refund void.
- `EscrowTest`: status held/released/refunded; pembayaran tunai not_applicable.
- `SettlementTest`: eligibility, batch, approve, paid, double-run, saldo negatif, net-off tunai.
- `RevenueDashboardAccessTest`: superadmin boleh; owner hanya sendiri; kasir 403.
- `RevenueCsvExportTest`: isi, filter, authorization.
- `OwnerInvoiceTest`: print hanya vendor sendiri.
- `CashierPaymentTest`: tunai membuat komisi piutang; QRIS menampilkan status.

Gunakan `RefreshDatabase`, factory, `Carbon::setTestNow`, dan `Http::fake()` bila perlu. Jangan memanggil Midtrans sandbox asli di test.

### 10.3 UAT

1. Super admin membuat/menyiapkan vendor + rekening.
2. Customer checkout DP via QRIS sandbox → pembayaran `paid`, escrow `held`, komisi `accrued`.
3. Kasir pickup/return → rental `completed`.
4. Super admin generate batch → cek angka, approve, transfer, tandai paid.
5. Owner melihat pendapatan bersih, invoice, dan status pencairan.
6. Kasir mencatat pembayaran tunai → owner melihat piutang komisi.
7. Batch berikutnya memotong piutang tunai.
8. Denda QRIS → komisi 0, MDR dipotong dari remitansi.
9. Export CSV dan print invoice.
10. Negative test: kasir membuka `/admin/pendapatan` → 403; owner membuka invoice vendor lain → 403.

### 10.4 Perintah verifikasi

```bash
php artisan test --compact tests/Unit/CommissionCalculatorTest.php
php artisan test --compact tests/Feature/Monetization
vendor/bin/pint --dirty --format agent
```

---

## 11. RISIKO & HAL YANG BELUM DIPUTUSKAN

### 11.1 Risiko

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Regulasi escrow/dana pihak ketiga | Produksi tidak boleh menahan dana tanpa izin/partner | Untuk PBL: sandbox/simulasi; produksi: konsultasi legal/partner pembayaran |
| Kebijakan MDR QRIS berubah | Margin berubah | Config, verifikasi berkala, jangan hard-code |
| Midtrans production KYC | Demo gagal | Siapkan sandbox; ajukan KYC lebih awal |
| Transfer manual salah tujuan | Dana hilang | Snapshot rekening, approval 2 orang, bukti transfer |
| Komisi tunai tidak tertagih | Piutang menumpuk | Dashboard piutang, invoice, net-off otomatis |
| Refund/dispute | Payout ganda | Tahan payout, adjustment, audit trail |
| Escrow menggantung (rental tidak pernah `completed`) | Dana tertahan terlalu lama | Job auto-cancel/refund setelah batas hari; laporan escrow aging |
| Pajak tidak dihitung | Laporan tidak sesuai akuntansi | Nyatakan non-goal; konsultasi pajak sebelum produksi |
| Data lama tanpa komisi | Laporan historis bolong | Backfill hanya untuk transaksi setelah tanggal cut-off; dokumentasikan |
| Kapasitas Boby | Deadline meleset | Sprint 5 hari §9.4; review paralel; UAT formal di luar sprint |
| `payments.status` vs `settled` | Bug laporan | Perbaiki saat NTN-62; jangan menambah status fiktif |
| Query laporan berat | Dashboard lambat | Index, agregasi DB, cache 5 menit, pagination |
| XLSX dependency | Scope creep | CSV dulu; ajukan dependency terpisah |

### 11.2 Belum diputuskan (perlu keputusan saat implementasi)

1. **Refund policy** untuk DP yang sudah dibayar tetapi rental cancelled/expired.
2. **Dasar komisi** apakah termasuk biaya antar/jemput dalam `payments.amount` (usulan: ya, karena pembayaran adalah gross; alternatif: exclude delivery fee).
3. **Ambang minimum payout** (usulan Rp50.000) dan carry-forward.
4. **Pajak** (PPN/PPh) dan apakah komisi ditampilkan include/exclude tax.
5. **Settlement day/cut-off** final (usulan Senin 01:00 WIB).
6. **Apakah owner boleh melihat detail transaksi customer** atau hanya agregat.
7. **XLSX** — setuju dependency `phpoffice/phpspreadsheet` atau tetap CSV.
8. **Deposit hangus/forfeited** — dianggap pembayaran rental (kena komisi) atau denda/kompensasi (tanpa komisi)? Usulan: tanpa komisi sampai ada keputusan tertulis.

---

## 12. DAMPAK DOKUMENTASI

| Dokumen | Perubahan yang diperlukan | Kapan |
|---|---|---|
| `docs/prd/monetisasi.md` | Dokumen ini | Sekarang |
| `excel/gantchart_tendaku.xlsx` | NTN-62..68: PIC Boby, 17–21 Nov, bar W, section header 5-day sprint | Sekarang |
| `docs/contract.md` §6 | Catat keputusan #12–#18 (revisi #18: Boby PIC seluruh epic, 5 hari) | Sekarang (perencanaan) |
| `docs/contract.md` §5 | Catat migrasi **aktual** | Setelah migrasi dibuat |
| `PRD_BOBY.md` | Tambah Prioritas 4 — Monetisasi Platform (jobdesk Boby, 5 hari) | Sekarang |
| `docs/qa/test-cases.md` | Tambah skenario monetisasi | NTN-68 |
| `docs/qa/uat-checklist.md` | Tambah checklist UAT monetisasi | NTN-68 |
| `docs/progress.md` | Catat progres berkala | Selama implementasi |
| ERD (Bunga) | Tabel/relasi baru | Sebelum migrasi final |

---

## 13. CHECKLIST IMPLEMENTASI (NANTI)

- [ ] Konfirmasi jadwal 5 hari (17–21 Nov 2026) dan slot review cross-module ke tim.
- [ ] Buat tiket NTN-62..68 di tracker (Gantt sudah diperbarui).
- [ ] Buat migrasi + model + factory.
- [x] Buat `CommissionCalculator` + unit test. *(NTN-62)*
- [x] Integrasikan webhook + escrow. *(NTN-63)*
- [x] Buat dashboard admin + CSV. *(NTN-64)*
- [ ] Buat dashboard owner + invoice.
- [ ] Buat aturan kasir + rekap shift.
- [ ] Buat batch settlement + command.
- [ ] UAT formal + perbaiki temuan (setelah sprint 5 hari).
- [x] Update `docs/contract.md` §5 setelah migrasi. *(NTN-62/64)*
- [ ] Update `test-cases.md` dan `uat-checklist.md`.
- [x] Pint + test suite penuh. *(branch NTN-64, 2026-10-25)*

---

## 14. SIGN-OFF

| Peran | Nama | Keputusan | Tanggal |
|---|---|---|---|
| PM / Super Admin / QA Lead | Boby Rozak | ✅ Menyetujui 7 keputusan monetisasi; **PIC seluruh epic**, target 5 hari (17–21 Nov 2026) | 2026-10-24 |
| Owner Vendor (perwakilan) | — | Menunggu UAT | — |
| Dosen Pembimbing | — | Menunggu review | — |

> Catatan: persetujuan ini adalah persetujuan **perencanaan**, bukan sign-off implementasi. Sign-off implementasi dilakukan setelah UAT dan test suite hijau.
