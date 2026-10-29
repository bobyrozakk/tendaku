# 🐞 FORMAT LAPORAN BUG (BUG REPORT TEMPLATE) — TENDAKU

Gunakan format ini saat menemukan kegagalan fungsi, celah keamanan isolasi, atau ketidaksesuaian data selama proses pengujian / UAT.

---

### **ID Bug:** BUG-[MODUL]-[NO] *(Contoh: BUG-KAS-001)*

- **Judul Bug:** [Ringkasan singkat dan jelas tentang masalah yang terjadi]
- **Modul Terkait:** [Multi-Tenant / Auth / Katalog / Booking / Kasir Pickup / Kasir Return / Super Admin / WhatsApp / Weather]
- **Pemilik Modul:** [Boby / Byan / Bunga / Arsya / Andini]
- **Tingkat Keparahan (Severity):**
  - [ ] **Blocker** (Aplikasi crash, proses checkout macet total, data leak antar vendor)
  - [ ] **Major** (Fitur utama tidak bekerja, salah hitung denda/stok, pembayaran gagal diproses)
  - [ ] **Minor** (UI glitch, kesalahan teks label, validasi form non-kritis)
  - [ ] **Trivial** (Typo, kosmetik tampilan minor)
- **Lingkungan Pengujian:**
  - OS & Browser: [misal: Windows 11 / Chrome 130]
  - Database: [Docker PostgreSQL / SQLite]
  - Akun yang digunakan: [Super Admin / Owner Vendor 1 / Kasir Vendor 1 / Customer]

---

### **Langkah Reproduksi (Steps to Reproduce):**
1. Masuk ke halaman `...`
2. Klik tombol `...`
3. Isi input dengan nilai `...`
4. Tekan simpan / submit

### **Hasil Aktual (Actual Behavior):**
> [Jelaskan apa yang terjadi saat ini, misalnya muncul exception 500 atau data vendor lain terlihat]

### **Hasil yang Diharapkan (Expected Behavior):**
> [Jelaskan apa yang seharusnya terjadi sesuai kontrak atau spesifikasi PRD]

### **Bukti Pendukung (Evidence):**
- Screenshot / Video tangkapan layar
- Cuplikan error log (`storage/logs/laravel.log`)

---

### **Status Siklus Bug:**
- [x] **New** (Baru dilaporkan)
- [ ] **Confirmed** (Telah diverifikasi oleh QA Lead)
- [ ] **In Progress** (Sedang diperbaiki oleh pemilik modul)
- [ ] **Fixed & Ready for Retest** (Sudah diperbaiki di branch terkait)
- [ ] **Closed / Verified** (Lolos regression test oleh QA Lead)
