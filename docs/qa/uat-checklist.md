# ✅ CHECKLIST UAT (USER ACCEPTANCE TESTING) — TENDAKU

**Proyek:** TENDAKU — Sistem Informasi Persewaan Tenda & Perlengkapan Acara (Multi-Tenant)  
**Koordinator UAT:** Boby (QA Lead / PM)  
**Tujuan:** Memvalidasi seluruh siklus hidup penyewaan alat secara end-to-end sebelum demo PBL.  

---

## 🏕️ SKENARIO 1: CUSTOMER MENYEWA (RENTAL BOOKING FLOW)
**Pelaku:** Customer (`customer@example.com` / Guest)  
**Penanggung Jawab Modul:** Byan (Katalog & Booking) & Andini (Payment Gateway)  

- [ ] 1.1 Customer membuka halaman katalog (`/katalog`).
- [ ] 1.2 Customer memfilter produk berdasarkan kategori dan rentang tanggal sewa.
- [ ] 1.3 Sistem menampilkan ketersediaan stok yang valid (tidak menampilkan unit yang sedang disewa atau maintenance).
- [ ] 1.4 Customer memilih barang dan menentukan durasi sewa (misal 2 hari).
- [ ] 1.5 Kalkulasi subtotal, deposit/jaminan, dan grand total dihitung akurat pada ringkasan pemesanan.
- [ ] 1.6 Customer menekan tombol "Lanjut ke Pembayaran" / Checkout.
- [ ] 1.7 Popup Midtrans Snap terbuka dengan total tagihan yang sesuai.
- [ ] 1.8 Customer menyelesaikan pembayaran via simulator sandbox (GoPay / Virtual Account).
- [ ] 1.9 Webhook Midtrans menerima callback settlement dan secara idempoten mengubah status rental menjadi `confirmed`.
- [ ] 1.10 Customer diarahkan ke halaman invoice/status (`/katalog/booking/status/{code}`) dengan status pembayaran terkonfirmasi.

---

## 📦 SKENARIO 2: VENDOR PICKUP (SERAH TERIMA BARANG)
**Pelaku:** Kasir / Admin Vendor (`kasir@tendaku.com`)  
**Penanggung Jawab Modul:** Bunga (Operasional Kasir)  

- [ ] 2.1 Kasir membuka modul Kasir Pickup (`/kasir/pickup`).
- [ ] 2.2 Kasir mencari transaksi berdasarkan `booking_code` pelanggan.
- [ ] 2.3 Kasir memindai / memilih barcode unit fisik yang berstatus `available`.
- [ ] 2.4 Kasir mengunggah foto kartu identitas (KTP) penyewa sebagai jaminan fisik.
- [ ] 2.5 Kasir melakukan konfirmasi serah terima barang.
- [ ] 2.6 Status transaksi rental berubah menjadi `picked_up`.
- [ ] 2.7 Status unit fisik yang diserahkan otomatis berubah menjadi `rented`.
- [ ] 2.8 Kolom jaminan KTP (`ktp_collateral_status`) tercatat sebagai `held`.

---

## 🔄 SKENARIO 3: VENDOR RETURN & DENDA (PENGEMBALIAN BARANG)
**Pelaku:** Kasir / Admin Vendor (`kasir@tendaku.com`)  
**Penanggung Jawab Modul:** Bunga (Operasional Kasir) & Byan (Kalkulasi Denda)  

- [ ] 3.1 Kasir membuka modul Kasir Return (`/kasir/return`).
- [ ] 3.2 Kasir memasukkan `booking_code` transaksi yang sedang aktif (`picked_up`).
- [ ] 3.3 Kasir melakukan inspeksi kondisi tiap unit fisik (`condition_after`: Baik / Perlu Perbaikan / Rusak).
- [ ] 3.4 Sistem menghitung denda keterlambatan (`late_fee`) secara otomatis jika pengembalian melewati batas waktu kontrak sewa.
- [ ] 3.5 Sistem mengakumulasi denda kerusakan barang (`damage_fee`) jika ada unit yang rusak.
- [ ] 3.6 Kasir memproses pelunasan denda (jika denda > 0) melalui tunai atau QRIS Midtrans kasir.
- [ ] 3.7 Kasir menyerahkan kembali fisik KTP pelanggan dan mengubah status jaminan menjadi `returned`.
- [ ] 3.8 Status akhir transaksi rental berubah menjadi `completed`.
- [ ] 3.9 Unit fisik yang dikembalikan dalam kondisi baik kembali berstatus `available`; unit yang rusak berubah menjadi `maintenance`.
- [ ] 3.10 Sistem mencetak / menampilkan struk pelunasan pengembalian barang.

---

## 🔒 SKENARIO 4: VERIFIKASI KEAMANAN & ISOLASI MULTI-TENANT
**Pelaku:** Seluruh Penguji (Lead: Boby)  

- [ ] 4.1 Kasir Vendor A tidak dapat mencari atau melihat transaksi rental milik Vendor B.
- [ ] 4.2 Owner Vendor A tidak dapat mengedit produk atau unit fisik milik Vendor B (menghasilkan 404).
- [ ] 4.3 Super Admin dapat melihat laporan transaksi dan data toko dari seluruh vendor.
- [ ] 4.4 Percobaan akses route tanpa login atau dengan role yang tidak berhak ditolak secara aman.
