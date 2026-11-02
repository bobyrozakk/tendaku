# Analisis Alur Payment Customer & Pencairan Vendor

**Tanggal:** 27 Oktober 2026  
**Proyek:** TENDAKU  
**Jenis dokumen:** Hasil review kode, rekomendasi desain alur dana, dan catatan implementasi  
**Status:** Sebagian diimplementasikan — lihat §0  
**Ruang lingkup:** checkout customer, pembayaran Midtrans, escrow, komisi platform, batch settlement, invoice vendor

> Dokumen ini disusun dari pembacaan kode dan data dev, bukan audit keuangan formal. Angka contoh dipakai untuk memperjelas logika, bukan nilai final produksi.

---

## 0. Status Implementasi (1 November 2026)

Perubahan sudah di-commit ke **`develop`** dalam commit terpisah per fitur: komisi/pembayaran, settlement invoice-first, halaman pendapatan & rekening vendor, monitoring transaksi, Midtrans Iris, dan guest checkout. Tidak ada branch fitur terpisah. File bersama `routes/web.php` digabung per hunk sehingga perubahan tidak saling menimpa.

### Sudah dikerjakan

| Item | Perubahan | Bukti |
|---|---|---|
| P0 — tipe pembayaran | Enum `App\Enums\PaymentType` (`full`, `dp`, `remaining`, `fine`) menjadi sumber tunggal; komisi dan settlement memakai `full`/`dp`/`remaining` | `app/Enums/PaymentType.php`, `CommissionCalculator`, `SettlementService` |
| P0 — komisi per rental | Komisi = `max(5% × total pembayaran rental, Rp2.500)`; minimum hanya sekali per rental dan pelunasan menambah selisih secara inkremental. Command `payments:reconcile` membackfill komisi per pembayaran, termasuk top-up `remaining` | `CommissionCalculator::incrementalCommissionFor()`, `CommissionService::accrueForPayment()`, `ReconcilePaidPayments` |
| P0 — periode batch stabil | `rentals.completed_at` ditambahkan + backfill; eligibility memakai `completed_at` dengan catch-up untuk pembayaran yang belum ter-assign | migrasi `2026_10_30_171113_add_completed_at_to_rentals_table.php`, `SettlementService` |
| P0 — invoice-first | Invoice diterbitkan saat `approve` (`issued`), menjadi `paid` saat `markPaid`, dan `void` saat `cancel` | `SettlementService::approve()`/`markPaid()`/`cancel()` |
| P1 — satu take rate | Alur pembayaran aktif mengisi `platform_fee = 0`; metode payout lama (`calculatePlatformFee`, `payoutEligibleSummary`, `markPaymentsAsPaidOut`) dihapus | `PaymentService`, `MidtransService` |
| P1 — halaman vendor | `/vendor/pendapatan` untuk owner: daftar invoice, status, rincian, dan cetak invoice dengan scope `vendor_id` | `Vendor\RevenueController`, `Vendor\SettlementInvoiceController`, `resources/views/vendor/revenue` |
| P1 — pelunasan | Pembayaran `remaining` didukung online (Midtrans) maupun tunai; komisi tunai diakrukan saat kasir mencatat pembayaran | `PaymentService::createOnlinePayment()`/`createCashRemainingPayment()` |
| P1 — rekening bank vendor | Vendor owner dapat menyimpan nama bank, nomor rekening, dan nama pemilik; data disinkronkan ke settlement yang belum dibayar | `App\Livewire\Vendor\BankAccountSettings`, `resources/views/livewire/vendor/bank-account-settings.blade.php` |
| P1 — monitoring transaksi | Super Admin dapat melihat rantai pembayaran → escrow → komisi → invoice → settlement dan membuka batch terkait | `App\Livewire\Admin\TransactionMonitor`, `MonetizationMonitorService` |
| P1 — Midtrans Iris | Payout memakai kredensial Creator/Approver terpisah, idempoten, dan hanya menandai lunas saat status `completed`; bukti payout di route terproteksi | `SettlementPayoutService`, `MidtransIrisService`, `SettlementPayoutProofController` |
| P1 — guest checkout | Guest yang memilih produk langsung diarahkan ke checkout tanpa keranjang; customer terautentikasi tetap memakai keranjang | `App\Livewire\Customer\ProductBooking`, `resources/views/livewire/customer/product-booking.blade.php` |

### Verifikasi

- Suite terarah (komisi/pembayaran, settlement/invoice/Iris, monitoring, halaman vendor, guest checkout): **112 test / 513 assertion**; **110 lulus, 2 gagal**. Dua kegagalan berada di `ProductBookingTest` dan sudah ada sebelum perubahan ini (`availableStock` mengharapkan 1 sedangkan kode menghitung 2 unit tersedia; `booking_cart.product_id` tidak lagi disimpan oleh `CartService`).
- Full suite terakhir: **483 test, 454 lulus, 28 gagal, 1.959 assertion**. Kegagalan yang tersisa berada di luar lingkup perubahan ini: ekspektasi status rental setelah webhook (alur sekarang menunggu scan barcode kasir), checkout/package yang masih memakai kontrak lama, durasi rental, `ZipArchive` tidak tersedia di lingkungan Windows, serta test profil/auth.
- Dua skenario lama di `PaymentScenarioTest` (batch payout marking dan platform fee 2%) dihapus karena menguji alur yang sudah digantikan; cakupan penggantinya ada di `SettlementTest` dan `CommissionCalculatorTest`.

### Belum dikerjakan (P1/P2)

- Adjustment refund setelah settlement `paid` dan penahanan payout saat dispute/chargeback.
- Kebijakan `minimum_payout` (bayar langsung atau carry-forward).
- `payments.gateway_settled_at` + rekonsiliasi Midtrans.
- Notifikasi WhatsApp/email dan audit trail approve/paid/cancel.
- Penyimpanan bukti transfer di private disk.
- Pembersihan total kolom legacy `platform_fee`/`vendor_payout_id` dan tabel `vendor_payouts`.

---

## 1. Ringkasan Eksekutif

Secara konsep, arah yang diinginkan sudah benar:

```text
Customer → Midtrans (akun platform/superadmin) → rekening platform
         → komisi platform dipotong → invoice/nota pencairan ke vendor
         → superadmin transfer → rekening vendor
```

Namun implementasi saat ini **belum konsisten** dengan alur tersebut. Tiga masalah paling kritis:

1. **Pembayaran `full` dan `remaining` tidak pernah masuk settlement.** Kode settlement dan komisi masih memakai tipe lama `settlement`, sedangkan database dan checkout memakai `full`/`remaining`. Akibatnya pembayaran penuh tidak dihitung komisinya dan tidak pernah dicairkan ke vendor.
2. **Invoice dibuat setelah transfer, bukan sebelum pencairan.** Invoice saat ini berfungsi sebagai bukti bayar, bukan tagihan/nota yang dilihat vendor lebih dulu.
3. **Batch mingguan bisa melewatkan pembayaran selamanya.** Batch memilih pembayaran berdasarkan `paid_at` di minggu tersebut dan mensyaratkan rental sudah `completed` saat batch dibuat. Rental yang selesai setelah minggu tutup tidak akan pernah masuk batch berikutnya.

Selain itu ada dua skema potongan yang masih hidup bersamaan: `platform_fee` 2% (warisan lama) dan komisi 5%. Settlement hanya memakai komisi 5%, sehingga `platform_fee` menjadi data yang menyesatkan.

**Rekomendasi inti:** jadikan **komisi 5% sebagai satu-satunya potongan platform**, perbaiki tipe pembayaran, terbitkan **invoice settlement saat batch di-approve**, dan transfer baru dilakukan setelah invoice terbit. Vendor melihat invoice dan status pencairan di halaman `/vendor/pendapatan`.

---

## 2. Jawaban Singkat atas Pertanyaan

### Apakah uang customer masuk ke superadmin?

**Ya, secara desain saat ini.** `config/services.php` memakai satu akun Midtrans platform:

```php
// Notes: This currently represents one platform Midtrans account.
// Resolve credentials per payment.vendor_id here if the vendor owns its own account.
'server_key' => env('MIDTRANS_SERVER_KEY'),
```

Midtrans men-settlement dana ke rekening platform, lalu platform menahan dana tersebut di ledger escrow aplikasi. Vendor **tidak** menerima langsung dari Midtrans.

Catatan: tabel `vendor_payment_settings` masih menyimpan `midtrans_merchant_id`, `midtrans_server_key`, dan `is_midtrans_active`, tetapi **tidak dipakai** oleh `MidtransService`. Ini perlu dijelaskan atau dibersihkan agar tidak menimbulkan asumsi bahwa vendor punya akun Midtrans sendiri.

### Apakah vendor mendapatkan invoice sebelum dicairkan?

**Belum.** Saat ini invoice baru dibuat di `SettlementService::markPaid()`, yaitu setelah admin mengunggah bukti transfer dan batch ditandai `paid`. Invoice langsung berstatus `issued` dan `paid_at` sudah terisi. Jadi invoice lebih mirip **kwitansi/bukti pencairan** daripada tagihan yang diterima vendor lebih dulu.

### Apakah superadmin mengambil persen dari pendapatan?

**Ya, 5% dengan minimum Rp2.500**, tetapi penerapannya belum konsisten:

- `CommissionCalculator` memakai 5% (`config/monetization.php`).
- Komisi hanya dihitung untuk `dp` dan `settlement`; `full` dan `remaining` tidak.
- Masih ada `platform_fee` 2% di `PaymentService`, tetapi tidak dipakai settlement.
- Minimum Rp2.500 berlaku **per pembayaran**, bukan per transaksi/rental.

### Logic yang disarankan

**Satu transaksi rental = satu basis komisi.**

```text
Komisi = max(5% × total pembayaran rental, Rp2.500)
```

Dengan ketentuan:

- `full` dan `dp + remaining` sama-sama dihitung.
- Denda dan refund **tidak** dikenai komisi.
- Minimum Rp2.500 hanya sekali per rental, bukan per pembayaran.
- Invoice diterbitkan saat batch `approved`, sebelum transfer.
- Transfer dilakukan setelah invoice terbit; bukti transfer mengubah status invoice menjadi `paid`.

---

## 3. Alur yang Berjalan Sekarang

### 3.1 Checkout customer

1. Customer memilih item dan mengisi checkout.
2. `CheckoutController::store()` membuat `Rental` dan satu `Payment` pending.
3. Tipe pembayaran default adalah **`full`**; customer juga bisa memilih **`dp`**.
4. `PaymentService::createOnlinePayment()` membuat order Midtrans.
5. `MidtransService::createTransaction()` membuat Snap token.
6. Customer membayar melalui Midtrans.

Bukti kode:

- `app/Http/Controllers/Customer/CheckoutController.php` — `$paymentOption = ... ?: 'full'`.
- `resources/views/customer/checkout/index.blade.php` — `paymentOption: 'full'`.
- `app/Services/PaymentService.php` — `createOnlinePayment()` hanya menerima `full`/`dp`.

### 3.2 Webhook pembayaran

1. Midtrans mengirim notifikasi ke `/webhook/midtrans`.
2. `MidtransWebhookController` memvalidasi input dan signature.
3. `MidtransService::handleNotification()` memvalidasi nominal, mengunci payment, lalu memetakan status.
4. Jika `paid`:
   - `EscrowService::markHeld()` menandai dana ditahan.
   - `CommissionService::accrueForPayment()` mencatat komisi.
   - MDR QRIS dihitung jika method/channel dianggap QRIS.
5. Jika `refunded`: escrow refunded dan komisi di-void (hanya yang masih `accrued`).
6. Jika `failed/expired`: escrow void.

### 3.3 Settlement mingguan

1. Scheduler menjalankan `monetization:generate-settlements` setiap Senin 01:00 WIB.
2. `SettlementService::buildWeeklyDraft()` mengambil pembayaran:
   - status `paid`;
   - belum punya `settlement_id`;
   - `paid_at` berada dalam periode Senin–Minggu;
   - rental berstatus `completed`;
   - escrow `held` (QRIS) atau cash non-denda;
   - tipe `dp`, `settlement`, atau `fine`.
3. Draft dikelompokkan per vendor.
4. Admin membuka `/admin/settlements`, lalu **approve**.
5. Approve mengunci item dan komisi menjadi `settled`, tetapi escrow masih ditahan.
6. Admin transfer manual, lalu **markPaid** dengan bukti transfer.
7. `markPaid` melepas escrow dan **menerbitkan invoice**.

### 3.4 Invoice

- `SettlementService::generateInvoice()` membuat `VendorInvoice` dengan status `issued`.
- `issued_at` = sekarang, `paid_at` = `settlement.paid_at` (sudah terisi).
- Nomor invoice: `INV/{vendor_id}/{YYYYMM}/{sequence}`.
- Hanya admin yang bisa mencetak invoice lewat `/admin/settlements/{settlement}/invoice`.
- Belum ada halaman vendor untuk melihat invoice.

### 3.5 Diagram as-is

```text
Customer
  │ bayar full / DP
  ▼
Midtrans (akun platform)
  │ dana masuk rekening platform
  ▼
payments.status = paid
escrow_status = held
komisi 5% dicatat (hanya dp/settlement)
  │
  │ rental completed
  ▼
Batch mingguan (hanya dp/settlement/fine)
  │
  ├─ draft → approved (escrow masih held)
  │
  └─ admin transfer manual
        │
        ▼
     markPaid → escrow released
        │
        └─ invoice dibuat SETELAH transfer
```

---

## 4. Temuan dan Dampak

### P0-1 — Pembayaran `full` dan `remaining` tidak masuk komisi/settlement

**Bukti:**

- Database hanya mengizinkan `full`, `dp`, `remaining`, `fine`
  (`database/migrations/2026_10_24_041606_alter_payments_table_for_payment_spec.php`).
- Checkout default `full`; pelunasan memakai `remaining`
  (`PaymentService::createRemainingPayment()`).
- `CommissionCalculator::COMMISSIONABLE_PAYMENT_TYPES = ['dp', 'settlement']`.
- `CommissionService::isCommissionable()` memakai daftar yang sama.
- `SettlementService::buildWeeklyDraft()` memakai
  `whereIn('payment_type', ['dp', 'settlement', 'fine'])`.
- `RevenueReportService::PAYMENT_TYPES` dan filter dashboard juga memakai `settlement`.
- Fixture test `tests/Concerns/CreatesRevenueFixtures.php` membuat payment bertipe `settlement`.
  SQLite tidak menegakkan CHECK constraint Postgres, sehingga test tetap lolos meskipun tipe itu
  sudah tidak valid di produksi.

**Dampak:**

- Pembayaran **full**: komisi 0, tidak masuk settlement, vendor tidak menerima dana.
- Pembayaran **DP**: hanya DP yang masuk; sisa `remaining` tidak pernah masuk.
- Dashboard pendapatan dan filter “Pelunasan” tidak menampilkan data yang benar.
- Test suite memberi rasa aman palsu karena memakai tipe lama.

**Contoh:**

| Skenario | Yang terjadi sekarang |
|---|---|
| Rental Rp1.000.000 dibayar full | Komisi Rp0, settlement Rp0, dana Rp1.000.000 tertahan |
| Rental Rp1.000.000, DP Rp500.000 + sisa Rp500.000 | Hanya DP yang bisa masuk; sisa Rp500.000 tidak pernah dicairkan |
| Rental Rp1.000.000, DP Rp500.000 | Komisi DP Rp25.000; sisa Rp500.000 tidak masuk settlement |

**Perbaikan minimal:**

1. Gunakan satu sumber tipe pembayaran (sebaiknya PHP enum `PaymentType`).
2. Ubah tipe yang dikenai komisi menjadi `full`, `dp`, `remaining`.
3. Ubah filter settlement menjadi `full`, `dp`, `remaining`, `fine`.
4. Ubah filter dashboard/CSV agar menerima `full` dan `remaining`.
5. Perbaiki fixture test dan tambahkan test khusus `full` + `remaining`.

---

### P0-2 — Batch mingguan bisa melewatkan pembayaran secara permanen

**Bukti:** `SettlementService::buildWeeklyDraft()` memakai:

```php
->whereBetween('paid_at', [$periodStart, $periodEnd])
->whereHas('rental', fn ($query) => $query->where('status', 'completed'))
```

**Masalah:** kedua kondisi dievaluasi pada saat batch dibuat. Jika pembayaran terjadi di minggu ini, tetapi rental baru `completed` minggu depan, pembayaran itu tidak masuk batch minggu ini. Saat batch minggu depan dibuat, `paid_at`-nya sudah di luar periode, sehingga tidak akan pernah diambil.

Selain itu, jika batch untuk vendor+periode sudah ada, `createDraftForVendor()` langsung mengembalikan `null`. Jadi menjalankan ulang minggu lama pun tidak menambahkan pembayaran yang terlewat.

**Dampak:** rental yang melewati batas minggu (terutama booking Jumat/Minggu dan DP yang dibayar sebelum pickup) berisiko tidak pernah dicairkan.

**Perbaikan yang disarankan:**

- Tambahkan `rentals.completed_at` (atau gunakan `returned_at` yang sudah ada).
- Tentukan periode batch berdasarkan **tanggal completion**, bukan `paid_at`.
- Atau buat mekanisme catch-up: ambil semua payment `paid`, belum `settlement_id`, rental `completed`, tanpa batas `paid_at`; masukkan ke draft periode berjalan.
- Tambahkan test untuk rental yang dibayar minggu ini tetapi selesai minggu depan.

---

### P0-3 — Invoice dibuat setelah transfer, bukan sebelum pencairan

**Bukti:** `SettlementService::markPaid()`:

```php
$settlement->update(['status' => 'paid', 'paid_at' => now(), ...]);
$this->assignItemsToSettlement($settlement, true);
$this->releaseEscrow($settlement);

return $this->generateInvoice($settlement);
```

`generateInvoice()` membuat invoice dengan status `issued` dan `paid_at` dari settlement yang sudah `paid`.

**Dampak:**

- Vendor tidak punya dokumen tagihan/nota sebelum uang ditransfer.
- Invoice tidak bisa dipakai sebagai dasar persetujuan pencairan.
- Tidak ada status “menunggu transfer” yang terlihat oleh vendor.
- Jika batch `approved` lalu dibatalkan, belum ada mekanisme void invoice karena invoice belum dibuat.

**Perbaikan yang disarankan:**

- Terbitkan invoice saat **approve**, bukan saat `markPaid`.
- Status invoice: `issued` (menunggu transfer) → `paid` (sudah ditransfer) → `void` (batch dibatalkan).
- `markPaid` hanya mengubah invoice menjadi `paid` dan menyimpan referensi/bukti transfer.
- Tambahkan halaman vendor untuk melihat invoice `issued` dan `paid`.

---

### P1-1 — Dua skema potongan: `platform_fee` 2% dan komisi 5%

**Bukti:**

- `PaymentService::calculatePlatformFee()`: 2%, minimum Rp1.000.
- `PaymentService::createMidtransPayment()` mengisi `platform_fee` untuk pembayaran online pertama.
- `CommissionCalculator`: 5%, minimum Rp2.500.
- Settlement hanya memakai `platform_commissions`, bukan `platform_fee`.
- `PaymentService::payoutEligibleSummary()` dan `markPaymentsAsPaidOut()` masih memakai `platform_fee` dan `vendor_payout_id`, tetapi tidak dipanggil alur settlement.
- `Payment::vendorPayout()` merujuk `VendorPayout` yang tidak ada di `app/Models`.

**Dampak:**

- Data `platform_fee` menyesatkan; bisa dianggap potongan tambahan.
- Jika kode payout lama diaktifkan, vendor bisa terpotong dua kali.
- Laporan dan kontrak tidak jelas mana yang menjadi take rate resmi.

**Perbaikan:**

- Tetapkan **5% all-in** sebagai satu-satunya komisi platform (sesuai PRD).
- Hapus/legacy-kan `platform_fee` dan metode payout lama.
- Jika kolom `platform_fee` tetap disimpan untuk audit, beri label jelas dan jangan dipakai perhitungan.

---

### P1-2 — Basis komisi per pembayaran vs per transaksi

**Kondisi sekarang:** minimum Rp2.500 berlaku per pembayaran. PRD bahkan mencatat risiko ini:

> Minimum Rp2.500 berlaku per pembayaran, bukan per rental. Rental yang dipecah menjadi DP dan pelunasan kecil bisa terkena minimum dua kali.

**Contoh dampak:**

| Nilai rental | Skema | Komisi |
|---|---|---|
| Rp1.000.000 | Full | 5% = Rp50.000 |
| Rp1.000.000 | DP 500k + sisa 500k | 5% × 500k + 5% × 500k = Rp50.000 |
| Rp40.000 | DP 20k + sisa 20k | max(1.000, 2.500) × 2 = **Rp5.000 (12,5%)** |
| Rp40.000 | Per rental, minimum sekali | max(2.000, 2.500) = **Rp2.500 (6,25%)** |

**Rekomendasi:** ubah basis menjadi **per rental/transaksi**:

```text
Komisi = max(5% × total pembayaran rental, Rp2.500)
```

Total pembayaran rental = `full` atau `dp + remaining`. Denda dan refund dikecualikan.

Jika ingin tetap per pembayaran, minimum harus diterapkan **sekali per rental** (misalnya komisi pertama memakai minimum, pembayaran berikutnya memakai tarif murni, lalu dilakukan true-up saat rental selesai).

---

### P1-3 — Belum ada halaman vendor untuk invoice dan pencairan

**Bukti:** route yang tersedia hanya:

- `/admin/settlements`
- `/admin/settlements/{settlement}/invoice`
- `/admin/pendapatan`

Tidak ada `/vendor/pendapatan` atau route invoice vendor. NTN-65 masih pending.

**Dampak:** vendor tidak bisa melihat:

- invoice yang akan/telah diterbitkan;
- komisi yang dipotong;
- status pencairan;
- bukti transfer;
- saldo/penyesuaian.

**Perbaikan:** implementasikan NTN-65 dengan scope `vendor_id`:

- daftar invoice + status (`issued`, `paid`, `void`);
- detail bruto, komisi, MDR denda, cash offset, adjustment, net payout;
- rekening tujuan;
- tombol cetak/unduh;
- bukti transfer (akses terbatas).

---

### P1-4 — Refund setelah settlement belum menyesuaikan batch berikutnya

**Bukti:**

- `CommissionService::voidForPayment()` hanya mengubah komisi berstatus `accrued`.
- Komisi `settled` tidak diubah.
- `SettlementService` hanya membawa `net_payout` negatif dari batch sebelumnya; tidak membuat adjustment saat refund terjadi setelah batch `paid`.

**Dampak:** refund setelah pencairan tidak mengurangi hak vendor di batch berikutnya; platform bisa menanggung kerugian.

**Perbaikan:**

- Saat refund setelah settlement, buat baris adjustment negatif di batch berikutnya.
- Hubungkan refund ke payment asal dan settlement asal.
- Tahan payout vendor terkait jika ada dispute/chargeback.
- Tambahkan test refund sebelum dan sesudah settlement.

---

### P1-5 — Komisi tunai belum diakru secara otomatis

**Bukti:**

- `PaymentService::createCashRemainingPayment()` dan `createCashFinePayment()` membuat payment `paid` langsung.
- Tidak ada pemanggilan `CommissionService::accrueForPayment()` untuk pembayaran tunai.
- `SettlementService` sudah menyiapkan item `cash_commission` dan field `cash_commission_offset`, tetapi datanya tidak pernah terbentuk.

**Dampak:** penjualan tunai lolos dari komisi 5%, atau vendor tidak punya piutang yang tercatat. NTN-66 masih pending.

**Perbaikan:**

- Saat kasir mencatat pembayaran tunai, akrukan komisi sebagai piutang.
- Net-off piutang tunai dari payout QRIS berikutnya.
- Jika belum ada payout, tampilkan sebagai saldo utang vendor di halaman vendor.
- Pastikan denda tunai tetap tanpa komisi.

---

### P1-6 — Pelunasan (`remaining`) belum memiliki alur pembayaran

**Bukti:**

- `PaymentService::createRemainingPayment()` ada, tetapi tidak dipanggil dari controller/route/view mana pun.
- `MidtransPaymentController::store()` hanya mencari `full`, `dp`, `settlement`.
- `BookingController::pay()` hanya menerima `full`/`dp`.
- `PaymentRetryController` memanggil `createOnlinePayment()` yang menolak `remaining`.
- Tidak ada tombol “Bayar sisa” di halaman customer.

**Dampak:** customer DP tidak bisa melunasi sisa secara online. Jika sisa dibayar tunai, alurnya juga belum ada (NTN-66). Vendor hanya menerima DP.

**Perbaikan:**

- Tambahkan endpoint + tombol “Bayar sisa” untuk customer.
- Dukung `remaining` di MidtransPaymentController, BookingController, dan PaymentRetryController.
- Atau jika sisa dibayar di outlet, implementasikan pencatatan tunai kasir dan akrual komisi.
- Pastikan `remaining` masuk settlement.

---

### P1-7 — `minimum_payout` belum dipakai

`config/monetization.php` memiliki `minimum_payout` Rp50.000, tetapi `SettlementService` tidak membacanya. Batch dengan net payout kecil tetap dibuat dan bisa ditransfer.

**Rekomendasi:** putuskan apakah payout di bawah minimum:

- dibayar langsung (biaya transfer platform bisa lebih besar dari komisi), atau
- digulirkan ke batch berikutnya, dengan status “belum mencapai minimum” di halaman vendor.

---

### P2-1 — Channel dan MDR belum berdasarkan kanal Midtrans yang sebenarnya

**Bukti:**

- `payment_channel` diisi dari pilihan UI checkout (`qris`, `bca_va`, dll.), bukan dari `payment_type` webhook Midtrans.
- `MidtransService::applyMonetization()` hanya menghitung MDR jika `method`/`payment_channel` bernilai `qris`.
- `chargeBankTransfer()` mengisi `method = va_{bank}` tetapi tidak menghitung MDR/VA fee.
- `config/payment.php` `enabled_channels` tidak memuat `qris`, sedangkan UI checkout menawarkan “QRIS Realtime”.

**Dampak:** MDR bisa 0 padahal transaksi QRIS, atau dihitung QRIS padahal customer membayar VA. Laporan platform net menjadi tidak akurat.

**Perbaikan:**

- Petakan `payment_type` Midtrans (`qris`, `bank_transfer`, `echannel`, dll.) ke channel internal.
- Simpan `gateway_fee_amount` berdasarkan kanal aktual, bukan pilihan UI.
- Pisahkan tarif QRIS, VA, dan e-wallet.
- Verifikasi `enabled_payments` Midtrans dengan pilihan channel di UI.

---

### P2-2 — Belum ada rekonsiliasi dana Midtrans ke rekening platform

Aplikasi menandai `escrow_status = held` saat webhook `paid`, tetapi tidak mencatat kapan Midtrans benar-benar men-settlement dana ke rekening platform. Admin bisa menandai batch `paid` sebelum dana masuk.

**Rekomendasi:**

- Tambahkan `payments.gateway_settled_at` atau tabel rekonsiliasi.
- Batch hanya boleh dibayar jika dana Midtrans sudah masuk (atau beri peringatan).
- Cocokkan dengan laporan settlement Midtrans secara berkala.

---

### P2-3 — Bukti transfer disimpan di disk publik

`SettlementManager` menyimpan bukti ke disk `public` dan memakai `Storage::url()`. Bukti transfer berisi data rekening yang sensitif.

**Rekomendasi:** simpan di disk privat, akses lewat signed URL/route ber-otorisasi.

---

### P2-4 — Belum ada audit trail dan notifikasi vendor

- Approve/paid/cancel hanya mengubah kolom, tanpa log siapa melakukan apa.
- Vendor tidak mendapat notifikasi saat invoice terbit atau dana ditransfer.

**Rekomendasi:**

- Tambahkan activity log/audit table untuk aksi settlement.
- Kirim notifikasi WhatsApp/email (Fonnte sudah tersedia) saat invoice `issued` dan `paid`.

---

### P2-5 — Jadwal batch otomatis bertentangan dengan PRD

PRD menyebut batch PBL dibuat **manual** lewat tombol/command, tetapi `routes/console.php` sudah menjadwalkan otomatis setiap Senin. Pilih salah satu:

- otomatis untuk produksi, atau
- manual untuk PBL, dengan scheduler dinonaktifkan.

---

## 5. Alur Target yang Disarankan

### 5.1 Prinsip

1. **Satu akun Midtrans platform** — semua pembayaran online masuk ke platform.
2. **Satu komisi 5% all-in** — tidak ada `platform_fee` 2% tambahan.
3. **Komisi per rental** — minimum Rp2.500 sekali per rental.
4. **Invoice sebelum transfer** — vendor melihat kewajiban platform lebih dulu.
5. **Transfer manual + bukti** — sesuai fase PBL.
6. **Escrow dilepas setelah transfer** — bukan saat approve.
7. **Vendor hanya melihat data miliknya** — scope `vendor_id`.

### 5.2 Diagram to-be

```text
Customer
  │ bayar full / DP / sisa
  ▼
Midtrans (akun platform)
  │ dana masuk rekening platform
  ▼
Payment paid → escrow held → komisi accrued
  │
  │ rental completed (completed_at)
  ▼
Batch settlement (semua payment eligible, termasuk yang terlewat)
  │
  ├─ draft (internal)
  │
  ├─ approved
  │     ├─ invoice diterbitkan (status issued)
  │     ├─ vendor melihat invoice + status "menunggu transfer"
  │     └─ escrow masih held
  │
  └─ paid
        ├─ transfer manual + bukti
        ├─ invoice menjadi paid
        ├─ escrow released
        └─ vendor melihat bukti transfer
```

### 5.3 Status yang disarankan

**Payment**

```text
pending → paid → refunded
        ↘ expired/failed/cancelled
```

**Escrow**

```text
not_applicable → held → released
                       ↘ refunded/void
```

**Settlement**

```text
draft → approved → paid
   ↘ cancelled
```

**Vendor Invoice**

```text
issued → paid
   ↘ void (jika batch dibatalkan)
```

### 5.4 Aturan invoice-first

| Tahap | Settlement | Invoice | Vendor melihat? | Dana |
|---|---|---|---|---|
| Draft | `draft` | belum ada | tidak | ditahan |
| Approve | `approved` | `issued` | ya | ditahan |
| Transfer | `paid` | `paid` | ya + bukti | dilepas |
| Batal | `cancelled` | `void` | ya (arsip) | ditahan/kembali |

Dengan aturan ini, vendor menerima **invoice lebih dulu**, lalu superadmin mencairkan. Invoice bukan lagi bukti bayar yang dibuat setelah transfer.

---

## 6. Rumus dan Contoh Perhitungan

### 6.1 Rumus yang disarankan

```text
Komisi rental = max( round(5% × total_pembayaran_rental), Rp2.500 )

total_pembayaran_rental = full
                        = dp + remaining

Dikecualikan dari komisi:
- fine
- refund
```

```text
Net payout vendor =
    gross_qris
  + gross_fine_qris
  + adjustment
  - komisi
  - MDR denda (pass-through)
  - komisi tunai (piutang)
```

```text
Laba platform =
    komisi
  - MDR QRIS standar (beban platform)
  - biaya transfer (beban platform)
```

### 6.2 Contoh: full payment

Rental Rp1.000.000 dibayar full via QRIS.

| Komponen | Nilai |
|---|---|
| Bruto | Rp1.000.000 |
| Komisi 5% | Rp50.000 |
| MDR QRIS 0,7% (beban platform) | Rp7.000 |
| Biaya transfer (beban platform) | Rp3.500 |
| **Diterima vendor** | **Rp950.000** |
| **Laba platform** | **Rp39.500** |

### 6.3 Contoh: DP + pelunasan

Rental Rp1.000.000, DP Rp500.000, sisa Rp500.000.

| Komponen | Nilai |
|---|---|
| DP | Rp500.000 |
| Pelunasan | Rp500.000 |
| Total bruto | Rp1.000.000 |
| Komisi 5% (sekali per rental) | Rp50.000 |
| **Diterima vendor** | **Rp950.000** |

Jika memakai aturan lama per pembayaran, komisi tetap Rp50.000 untuk nominal ini; perbedaannya muncul pada rental kecil.

### 6.4 Contoh: rental kecil (efek minimum)

Rental Rp40.000, DP Rp20.000 + sisa Rp20.000.

| Skema | Komisi | Efektif |
|---|---|---|
| Per pembayaran (min Rp2.500 × 2) | Rp5.000 | 12,5% |
| Per rental (min Rp2.500 sekali) | Rp2.500 | 6,25% |

### 6.5 Contoh: denda QRIS

Denda Rp200.000 via QRIS.

| Komponen | Nilai |
|---|---|
| Denda bruto | Rp200.000 |
| Komisi | Rp0 |
| MDR denda 0,7% (pass-through) | Rp1.400 |
| **Diterima vendor** | **Rp198.600** |

### 6.6 Contoh: tunai

Customer membayar sisa Rp500.000 tunai di kasir.

| Komponen | Nilai |
|---|---|
| Uang diterima vendor | Rp500.000 |
| Komisi 5% menjadi piutang | Rp25.000 |
| Efek ke payout QRIS berikutnya | -Rp25.000 |

---

## 7. Perubahan Data yang Diperlukan

| Prioritas | Perubahan | Tujuan |
|---|---|---|
| P0 | Enum/konstanta `PaymentType` (`full`, `dp`, `remaining`, `fine`) | Satu sumber tipe pembayaran; mencegah bug string |
| P0 | Perbaiki query komisi & settlement | `full` dan `remaining` ikut dihitung |
| P0 | `rentals.completed_at` | Basis periode batch yang stabil |
| P0 | Backfill komisi/settlement data lama | Data `paid` lama tidak tertinggal |
| P1 | `vendor_invoices.due_at` | Tanggal jatuh tempo transfer |
| P1 | Invoice dibuat saat `approve` | Alur invoice-first |
| P1 | `vendor_invoices.status` dipakai penuh (`issued`/`paid`/`void`) | Status invoice jelas |
| P1 | Halaman `/vendor/pendapatan` | Vendor melihat invoice & pencairan |
| P1 | `payments.gateway_settled_at` | Rekonsiliasi dana Midtrans |
| P1 | Adjustment refund setelah settlement | Menjaga hak vendor/platform |
| P2 | `settlement_audits` / activity log | Audit approve/paid/cancel |
| P2 | Notifikasi WhatsApp/email | Vendor tahu invoice terbit/dibayar |
| P2 | Hapus/legacy `platform_fee`, `vendor_payout_id`, `VendorPayout` | Menghindari double charge |
| P2 | Private disk untuk bukti transfer | Keamanan data rekening |

Catatan: perubahan skema sebaiknya dalam migrasi terpisah dan diuji di SQLite serta Postgres. Jangan menjalankan migrasi ke database remote tanpa persetujuan.

---

## 8. Rencana Implementasi Bertahap

### Sprint 0 — Perbaikan kritis (P0)

Target: uang vendor untuk `full` dan `remaining` bisa dihitung dan dicairkan.

1. Buat enum/konstanta `PaymentType` dan ganti semua string literal.
2. Ubah `CommissionCalculator::COMMISSIONABLE_PAYMENT_TYPES` menjadi `full`, `dp`, `remaining`.
3. Ubah filter `SettlementService::buildWeeklyDraft()` menjadi `full`, `dp`, `remaining`, `fine`.
4. Ubah `RevenueReportService::PAYMENT_TYPES` dan opsi filter dashboard/CSV.
5. Perbaiki fixture test dan tambahkan test:
   - full payment → komisi 5%;
   - DP + remaining → keduanya masuk;
   - filter dashboard menampilkan `full`/`remaining`.
6. Tambahkan `rentals.completed_at` dan ubah eligibility batch berbasis completion.
7. Buat command backfill dengan mode `--dry-run` untuk data `paid` lama.

**Acceptance criteria:**

- Full payment Rp1.000.000 menghasilkan komisi Rp50.000 dan net payout Rp950.000.
- DP Rp500.000 + remaining Rp500.000 menghasilkan total komisi Rp50.000 dan net payout Rp950.000.
- Rental yang selesai setelah minggu tutup tetap masuk batch berikutnya.
- Test suite hijau, termasuk test baru untuk `full`/`remaining`.

### Sprint 1 — Invoice-first dan halaman vendor (P1)

1. Pindahkan pembuatan invoice dari `markPaid` ke `approve`.
2. `markPaid` mengubah invoice menjadi `paid`, bukan membuat invoice baru.
3. `cancel` men-void invoice.
4. Tambahkan halaman `/vendor/pendapatan` (NTN-65) dengan scope vendor.
5. Tambahkan cetak/unduh invoice untuk vendor.
6. Kirim notifikasi saat invoice `issued` dan `paid`.

**Acceptance criteria:**

- Vendor melihat invoice saat batch `approved`, sebelum transfer.
- Vendor tidak bisa melihat invoice vendor lain.
- Setelah transfer, invoice berubah `paid` dan bukti terlihat.
- Batch `cancelled` membuat invoice `void`.

### Sprint 2 — Rekonsiliasi, refund, dan tunai (P1/P2)

1. Tambahkan `gateway_settled_at` dan rekonsiliasi Midtrans.
2. Implementasikan adjustment refund setelah settlement.
3. Akrukan komisi tunai saat kasir mencatat pembayaran.
4. Implementasikan flow pelunasan (`remaining`) online atau tunai.
5. Terapkan `minimum_payout` (bayar atau carry-forward).
6. Tambahkan audit log dan alert operasional.

---

## 9. Skenario Uji Wajib

| No | Skenario | Ekspektasi |
|---|---|---|
| 1 | Full payment QRIS | Komisi 5%, settlement memasukkan, invoice terbit sebelum transfer |
| 2 | DP + remaining | Keduanya masuk settlement, komisi total 5% (atau sesuai keputusan) |
| 3 | Rental melewati batas minggu | Tetap masuk batch setelah `completed`, tidak hilang |
| 4 | Denda QRIS | Komisi 0, MDR denda dipotong dari remitansi |
| 5 | Denda tunai | Tidak masuk escrow, vendor terima penuh |
| 6 | Tunai sisa | Komisi menjadi piutang, net-off batch berikutnya |
| 7 | Refund sebelum settlement | Komisi void, escrow refunded |
| 8 | Refund setelah settlement | Adjustment negatif di batch berikutnya |
| 9 | Webhook duplikat | Tidak ada komisi/settlement ganda |
| 10 | Vendor A membuka invoice vendor B | 403/404 |
| 11 | Batch draft | Vendor belum melihat invoice |
| 12 | Batch approved | Vendor melihat invoice `issued` |
| 13 | Batch paid | Invoice `paid`, bukti transfer tersedia |
| 14 | Batch cancelled | Invoice `void`, item kembali ke draft berikutnya |
| 15 | Payout di bawah minimum | Sesuai keputusan: dibayar atau carry-forward |
| 16 | Rental kecil Rp40.000 DP + sisa | Komisi minimum sekali, bukan dua kali |
| 17 | Dashboard filter full/remaining | Data tampil benar |
| 18 | Bukti transfer | Hanya admin/vendor berhak yang bisa akses |

---

## 10. Keputusan yang Perlu Diambil

1. **Basis komisi:** per rental (disarankan) atau per pembayaran?
2. **Take rate:** 5% all-in (disarankan) atau 2% + 5%?
3. **Invoice:** platform menerbitkan “Invoice Settlement/Nota Pencairan” (disarankan untuk PBL), atau vendor menerbitkan invoice pajak ke platform?
4. **Jadwal pencairan:** mingguan Senin, cut-off Minggu 23:59, transfer Selasa?
5. **Minimum payout:** Rp50.000 dibayar langsung atau digulirkan?
6. **Tunai:** diizinkan di outlet? Jika ya, komisi menjadi piutang; jika tidak, nonaktifkan.
7. **Refund setelah payout:** potong batch berikutnya atau proses manual?
8. **Akun Midtrans:** platform (disarankan, sesuai kode sekarang) atau per vendor?
9. **Batch:** otomatis atau manual untuk PBL?
10. **Basis komisi:** apakah ongkir ikut dihitung? Disarankan hanya nilai sewa, bukan ongkir.

---

## 11. Lampiran

### 11.1 Snapshot data dev (27 Oktober 2026)

| Tipe | Status | Jumlah | Nominal |
|---|---|---:|---:|
| `dp` | paid | 9 | Rp426.250 |
| `full` | paid | 19 | Rp1.129.250 |
| `dp` | pending | 5 | Rp205.000 |
| `full` | pending | 8 | Rp444.000 |
| `dp` | failed | 14 | Rp778.125 |
| `full` | failed | 11 | Rp1.265.000 |

Data lain:

- `platform_commissions`: **0**
- `settlements`: **0**
- `settlement_items`: **0**
- `vendor_invoices`: **0**
- Pembayaran `paid` yang belum punya `settlement_id`: **28**
- `payment_channel` dan `escrow_status` data lama masih `null`/`not_applicable`.

Angka ini menunjukkan belum ada backfill untuk pembayaran lama. Sebagian data mungkin berasal dari sebelum fitur monetisasi aktif, tetapi tetap perlu strategi migrasi data.

### 11.2 File utama yang diperiksa

- `app/Http/Controllers/Customer/CheckoutController.php`
- `app/Http/Controllers/Customer/BookingController.php`
- `app/Http/Controllers/Customer/PaymentRetryController.php`
- `app/Http/Controllers/Api/MidtransPaymentController.php`
- `app/Http/Controllers/Webhook/MidtransWebhookController.php`
- `app/Http/Controllers/Admin/SettlementInvoiceController.php`
- `app/Services/PaymentService.php`
- `app/Services/MidtransService.php`
- `app/Services/CommissionCalculator.php`
- `app/Services/CommissionService.php`
- `app/Services/EscrowService.php`
- `app/Services/SettlementService.php`
- `app/Services/RevenueReportService.php`
- `app/Livewire/Admin/SettlementManager.php`
- `app/Models/Payment.php`
- `app/Models/Settlement.php`
- `app/Models/VendorInvoice.php`
- `app/Models/VendorPaymentSetting.php`
- `database/migrations/2026_10_24_041606_alter_payments_table_for_payment_spec.php`
- `database/migrations/2026_10_26_010852_create_settlements_table.php`
- `tests/Concerns/CreatesRevenueFixtures.php`
- `tests/Feature/Monetization/SettlementTest.php`
- `config/payment.php`
- `config/monetization.php`
- `config/services.php`
- `routes/console.php`
- `docs/prd/monetisasi.md`
- `docs/contract.md`

### 11.3 Referensi keputusan PRD

- Alur uang QRIS: `docs/prd/monetisasi.md` §3.2.
- Rumus komisi: §4.1.
- Syarat pembayaran masuk settlement: §5.2.
- Batch mingguan: §5.3.
- Perhitungan batch: §5.4.
- Komisi tunai: §5.5.
- Denda: §5.6.
- Refund: §5.7.
- Invoice: §5.8.

---

## 12. Kesimpulan

Arah bisnisnya sudah tepat: **customer membayar ke platform, platform memotong komisi, lalu mencairkan ke vendor.** Yang perlu diperbaiki bukan konsepnya, tetapi konsistensi implementasi:

1. **Perbaiki tipe pembayaran** agar `full` dan `remaining` ikut dihitung.
2. **Perbaiki periode batch** agar pembayaran tidak hilang.
3. **Terbitkan invoice sebelum transfer**, bukan setelahnya.
4. **Hapus dualisme 2% vs 5%** dan tetapkan satu take rate.
5. **Beri vendor halaman invoice/pencairan** agar alur transparan.

Jika empat langkah pertama dikerjakan, alur “superadmin menahan uang → vendor menerima invoice → superadmin mencairkan” akan sesuai dengan yang diinginkan dan aman untuk diuji.
