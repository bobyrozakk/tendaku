## 🚀 Deskripsi Perubahan
Ringkas perubahan yang dilakukan pada Pull Request ini.

- **Modul Utama:** [Multi-Tenant / Auth / Katalog / Booking / Kasir / Super Admin / WhatsApp / Weather]
- **Tipe Perubahan:** [feat / fix / refactor / test / docs]
- **Issue Terkait:** #[Nomor Issue]

---

## 📋 Checklist Kualitas Pengembang (Wajib Diisi Sebelum Merge)

- [ ] **Laravel Pint Lolos:** Telah menjalankan `vendor/bin/pint --dirty --format agent` tanpa violation style.
- [ ] **PHPUnit Tests Hijau:** Semua test unit & feature berhasil lulus (`php artisan test`).
- [ ] **Isolasi Multi-Tenant Aman:** Perubahan tidak melanggar batasan `vendor_id` dan tidak menyebabkan kebocoran data antar toko.
- [ ] **Skema Sinkron:** Jika ada migrasi baru, sudah dicatat di `docs/contract.md` bagian *Perubahan Skema*.
- [ ] **Label Cross-Module:** Jika PR ini menyentuh file milik anggota tim lain, sudah diberi label `cross-module` dan dikoordinasikan.
- [ ] **Tanpa Hardcode Rahasia:** Token / API key tidak di-hardcode (tersedia di `.env.example`).
