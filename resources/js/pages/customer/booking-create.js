document.addEventListener("alpine:init", () => {
    Alpine.data("checkoutPage", (cfg) => ({
        ...cfg,
        selectedChannel: "qris",
        paymentOption: "full",
        isProcessing: false,
        formError: "",
        errors: {},
        phone: "",
        nik: "",
        address: cfg.deliveryAddress || "",
        notes: cfg.deliveryNotes || "",
        editAddress: false,
        touched: false,
        remaining: "",
        expired: false,
        countdownTimer: null,

        addresses: cfg.addresses || [],
        selectedAddressId: cfg.selectedAddressId || null,
        storeAddressUrl: cfg.storeAddressUrl || "",
        showAddressPicker: false,
        showAddAddressModal: false,
        newAddressSubmitting: false,
        newAddressErrors: {},
        newAddressForm: {
            label: "Rumah",
            recipient_name: cfg.name || "",
            phone_number: "",
            full_address: "",
            province: "Jawa Barat",
            city: "Bandung",
            district: "",
            village: "",
            postal_code: "",
            notes: "",
            is_default: true,
        },

        // Serah terima setelah booking dibuat: form -> handoff
        phase: "form",
        createdCode: "",
        // State & Logika Kalender
        calOpen: null, // 'pickup' | 'return' | null
        calYear: new Date().getFullYear(),
        calMonth: new Date().getMonth(),
        monthNames: [
            "Januari",
            "Februari",
            "Maret",
            "April",
            "Mei",
            "Juni",
            "Juli",
            "Agustus",
            "September",
            "Oktober",
            "November",
            "Desember",
        ],

        // Timepicker state (wheel, 24 jam, dibatasi 07.00 - 17.00)
        timeOpen: false,
        timeItemH: 40,
        timeMinHour: 7,
        timeMaxHour: 17,
        timeHours: Array.from({ length: 11 }, (_, i) => 7 + i), // 7..17
        timeMinutes: [0, 15, 30, 45],
        _timeMinTimer: null,
        _timeHourTimer: null,

        // Rapikan jam: dalam rentang 07.00-17.00, menit kelipatan 15, jam 17 hanya 17.00
        normTime(h, m) {
            h = Math.min(
                this.timeMaxHour,
                Math.max(this.timeMinHour, Number.isFinite(h) ? h : 9),
            );
            m = Number.isFinite(m) ? m : 0;
            m = this.timeMinutes.reduce((a, b) =>
                Math.abs(b - m) < Math.abs(a - m) ? b : a,
            );
            if (h === this.timeMaxHour) m = 0;
            return [h, m];
        },
        get timeHour() {
            const [h, m] = (this.pickupTime || "09:00")
                .split(":")
                .map((n) => parseInt(n, 10));
            return this.normTime(h, m)[0];
        },
        get timeMinute() {
            const [h, m] = (this.pickupTime || "09:00")
                .split(":")
                .map((n) => parseInt(n, 10));
            return this.normTime(h, m)[1];
        },
        setTime(h, m) {
            [h, m] = this.normTime(h, m);
            this.pickupTime =
                String(h).padStart(2, "0") + ":" + String(m).padStart(2, "0");
        },
        toggleTime() {
            this.timeOpen = !this.timeOpen;
            this.calOpen = null;
            if (this.timeOpen) {
                // pastikan nilai awal valid, lalu posisikan wheel
                this.setTime(this.timeHour, this.timeMinute);
                this.$nextTick(() => {
                    this.scrollColTo(
                        this.$refs.hourCol,
                        this.timeHours.indexOf(this.timeHour),
                        false,
                    );
                    this.scrollColTo(
                        this.$refs.minCol,
                        this.timeMinutes.indexOf(this.timeMinute),
                        false,
                    );
                });
            }
        },
        scrollColTo(el, index, smooth = true) {
            if (!el || index < 0) return;
            el.scrollTo({
                top: index * this.timeItemH,
                behavior: smooth ? "smooth" : "auto",
            });
        },
        pickHour(h) {
            this.setTime(h, this.timeMinute);
            this.scrollColTo(this.$refs.hourCol, this.timeHours.indexOf(h));
            if (h === this.timeMaxHour) this.scrollColTo(this.$refs.minCol, 0);
        },
        pickMinute(m) {
            this.setTime(this.timeHour, m);
            this.scrollColTo(
                this.$refs.minCol,
                this.timeMinutes.indexOf(this.timeMinute),
            );
        },
        onHourScroll() {
            const el = this.$refs.hourCol;
            if (!el) return;
            const idx = Math.min(
                this.timeHours.length - 1,
                Math.max(0, Math.round(el.scrollTop / this.timeItemH)),
            );
            this.setTime(this.timeHours[idx], this.timeMinute);
            // jam 17 hanya boleh 00 -> geser kolom menit ke 00
            clearTimeout(this._timeHourTimer);
            this._timeHourTimer = setTimeout(() => {
                if (this.timeHour === this.timeMaxHour)
                    this.scrollColTo(this.$refs.minCol, 0);
            }, 150);
        },
        onMinScroll() {
            const el = this.$refs.minCol;
            if (!el) return;
            const idx = Math.min(
                this.timeMinutes.length - 1,
                Math.max(0, Math.round(el.scrollTop / this.timeItemH)),
            );
            this.setTime(this.timeHour, this.timeMinutes[idx]);
            // kalau jam 17 dan menit bukan 00, balikkan ke 00 setelah scroll berhenti
            clearTimeout(this._timeMinTimer);
            this._timeMinTimer = setTimeout(() => {
                if (
                    this.timeHour === this.timeMaxHour &&
                    this.timeMinute === 0 &&
                    el.scrollTop > 1
                ) {
                    this.scrollColTo(el, 0);
                }
            }, 150);
        },

        // Tanggal kembali minimal 1 hari setelah tanggal ambil
        addDaysIso(iso, n) {
            const [y, m, d] = String(iso).split("-").map(Number);
            const dt = new Date(y, m - 1, d + n);
            return (
                dt.getFullYear() +
                "-" +
                String(dt.getMonth() + 1).padStart(2, "0") +
                "-" +
                String(dt.getDate()).padStart(2, "0")
            );
        },
        get minReturnDate() {
            return this.pickupDate
                ? this.addDaysIso(this.pickupDate, 1)
                : this.minDate;
        },

        openCalendar(type) {
            this.calOpen = this.calOpen === type ? null : type;
            const cur = type === "pickup" ? this.pickupDate : this.returnDate;
            if (cur) {
                const d = new Date(cur);
                if (!isNaN(d)) {
                    this.calYear = d.getFullYear();
                    this.calMonth = d.getMonth();
                }
            }
        },
        prevMonth() {
            if (this.calMonth === 0) {
                this.calMonth = 11;
                this.calYear--;
            } else {
                this.calMonth--;
            }
        },
        nextMonth() {
            if (this.calMonth === 11) {
                this.calMonth = 0;
                this.calYear++;
            } else {
                this.calMonth++;
            }
        },
        get calendarDays() {
            const days = [];
            const firstDay = new Date(this.calYear, this.calMonth, 1).getDay();
            const totalDays = new Date(
                this.calYear,
                this.calMonth + 1,
                0,
            ).getDate();

            for (let i = 0; i < firstDay; i++) days.push({ empty: true });

            for (let d = 1; d <= totalDays; d++) {
                const mm = String(this.calMonth + 1).padStart(2, "0");
                const dd = String(d).padStart(2, "0");
                const iso = `${this.calYear}-${mm}-${dd}`;
                const isPast = iso < this.minDate;
                const isBeforePickup =
                    this.calOpen === "return" &&
                    this.pickupDate &&
                    iso < this.minReturnDate;
                const isSelected =
                    (this.calOpen === "pickup" && this.pickupDate === iso) ||
                    (this.calOpen === "return" && this.returnDate === iso);
                const inRange =
                    this.pickupDate &&
                    this.returnDate &&
                    iso >= this.pickupDate &&
                    iso <= this.returnDate;

                days.push({
                    day: d,
                    iso: iso,
                    disabled: isPast || isBeforePickup,
                    selected: isSelected,
                    inRange: inRange,
                });
            }
            return days;
        },
        selectCalDate(iso) {
            if (this.calOpen === "pickup") {
                this.pickupDate = iso;
                this.onPickupChange();
            } else {
                this.returnDate = iso;
                this.onReturnChange();
            }
            this.calOpen = null;
        },

        init() {
            // Data awal: pastikan tanggal kembali minimal 1 hari setelah tanggal ambil
            if (
                this.pickupDate &&
                (!this.returnDate || this.returnDate <= this.pickupDate)
            ) {
                this.returnDate = this.minReturnDate;
            }
            this.startCountdown();
            if (this.selectedAddressId) {
                this.selectAddress(this.selectedAddressId);
            }
            this.$nextTick(() => {
                if (typeof window.renderLucideIcons === "function") {
                    window.renderLucideIcons();
                }
            });
        },

        get isGuest() {
            return !this.isLoggedIn;
        },
        get showAddressForm() {
            return this.needsAddress || this.editAddress;
        },

        get selectedAddress() {
            return (
                this.addresses.find((a) => a.id === this.selectedAddressId) ||
                null
            );
        },

        selectAddress(id) {
            this.selectedAddressId = id;
            const addr = this.addresses.find((a) => a.id === id);
            if (addr) {
                this.address = addr.delivery_snapshot || addr.formatted_address;
            }
            this.showAddressPicker = false;
            if (this.errors.address) delete this.errors.address;
        },

        openAddAddressModal() {
            this.newAddressErrors = {};
            if (!this.newAddressForm.recipient_name && this.name) {
                this.newAddressForm.recipient_name = this.name;
            }
            if (!this.newAddressForm.phone_number && this.phone) {
                this.newAddressForm.phone_number = this.phone;
            }
            this.showAddAddressModal = true;
            this.$nextTick(() => {
                if (typeof window.renderLucideIcons === "function") {
                    window.renderLucideIcons();
                }
            });
        },

        closeAddAddressModal() {
            this.showAddAddressModal = false;
            this.newAddressErrors = {};
        },

        async saveNewAddress() {
            this.newAddressSubmitting = true;
            this.newAddressErrors = {};
            try {
                const resp = await fetch(this.storeAddressUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrf,
                    },
                    body: JSON.stringify(this.newAddressForm),
                });

                const data = await resp.json();

                if (!resp.ok) {
                    this.newAddressErrors = data.errors || {
                        general: data.message || "Gagal menyimpan alamat.",
                    };
                    this.newAddressSubmitting = false;
                    return;
                }

                const created = data.data;
                if (created.is_default) {
                    this.addresses.forEach((a) => (a.is_default = false));
                }
                this.addresses.unshift(created);
                this.selectAddress(created.id);
                this.showAddAddressModal = false;

                // Reset form
                this.newAddressForm = {
                    label: "Rumah",
                    recipient_name: this.name || "",
                    phone_number: "",
                    full_address: "",
                    province: "Jawa Barat",
                    city: "Bandung",
                    district: "",
                    village: "",
                    postal_code: "",
                    notes: "",
                    is_default: false,
                };
            } catch (err) {
                this.newAddressErrors = {
                    general: "Terjadi gangguan jaringan saat menyimpan alamat.",
                };
            } finally {
                this.newAddressSubmitting = false;
            }
        },

        get rentalDays() {
            const a = new Date(this.pickupDate),
                b = new Date(this.returnDate);
            if (isNaN(a) || isNaN(b)) return this.initialDays;
            return Math.max(1, Math.round((b - a) / 86400000));
        },
        get totalUnits() {
            return this.items.reduce((n, it) => n + (Number(it.qty) || 0), 0);
        },
        get subtotal() {
            return (
                this.items.reduce(
                    (n, it) => n + it.price_per_day * (Number(it.qty) || 0),
                    0,
                ) * this.rentalDays
            );
        },
        get adminFee() {
            return Math.max(2000, Math.round(this.subtotal * 0.03));
        },
        get deposit() {
            return 0;
        },
        get deliveryFee() {
            return this.isLoggedIn && this.pickupMethod === "delivery"
                ? this.deliveryFeeAmount
                : 0;
        },
        get grandTotal() {
            return Math.max(
                0,
                this.subtotal +
                    this.adminFee +
                    this.deliveryFee -
                    this.discount,
            );
        },
        get dpAmount() {
            return Math.round((this.grandTotal * this.dpPercentage) / 100);
        },
        get payableAmount() {
            return this.paymentOption === "dp"
                ? this.dpAmount
                : this.grandTotal;
        },

        // Form menyusut ke atas, animasi "Booking dibuat" tampil di area konten, lalu pindah ke halaman status (?new=1).
        async playHandoff(data) {
            const wait = (ms) => new Promise((r) => setTimeout(r, ms));
            const full = !window.matchMedia("(prefers-reduced-motion: reduce)")
                .matches;

            this.createdCode = data.booking_code || "";
            const url = new URL(data.redirect_url, window.location.origin);
            url.searchParams.set("new", "1");

            window.scrollTo({ top: 0, behavior: full ? "smooth" : "auto" });
            this.phase = "handoff";
            await wait(full ? 2400 : 300);
            window.location.href = url.toString();
        },

        formatRupiah(num) {
            return (
                "Rp " +
                Math.round(num || 0)
                    .toString()
                    .replace(/\B(?=(\d{3})+(?!\d))/g, ".")
            );
        },
        fmtLong(iso) {
            const d = new Date(iso);
            return isNaN(d)
                ? "-"
                : d.toLocaleDateString("id-ID", {
                      weekday: "long",
                      day: "numeric",
                      month: "long",
                      year: "numeric",
                      timeZone: "UTC",
                  });
        },
        fmtShort(iso) {
            const d = new Date(iso);
            return isNaN(d)
                ? "-"
                : d.toLocaleDateString("id-ID", {
                      day: "numeric",
                      month: "short",
                      year: "numeric",
                      timeZone: "UTC",
                  });
        },

        changeQty(i, delta) {
            this.items[i].qty = Number(this.items[i].qty || 1) + delta;
            this.clampQty(i);
        },
        clampQty(i) {
            const it = this.items[i];
            let q = parseInt(it.qty, 10);
            if (isNaN(q) || q < 1) q = 1;
            if (q > it.max_qty) q = it.max_qty;
            it.qty = q;
            this.touched = true;
        },
        onPickupChange() {
            if (!this.pickupDate) return;

            if (!this.returnDate || this.returnDate <= this.pickupDate) {
                this.returnDate = this.minReturnDate;
            }

            this.touched = true;
        },

        onReturnChange() {
            this.touched = true;

            if (
                this.pickupDate &&
                this.returnDate &&
                this.returnDate <= this.pickupDate
            ) {
                this.returnDate = this.minReturnDate;
            }
        },

        startCountdown() {
            if (!this.expiresAtIso) return;

            const tick = () => {
                const expiresAt = new Date(this.expiresAtIso).getTime();

                if (!Number.isFinite(expiresAt)) {
                    this.remaining = "";
                    clearInterval(this.countdownTimer);
                    return;
                }

                const ms = expiresAt - Date.now();

                if (ms <= 0) {
                    this.remaining = "00:00:00";
                    this.expired = true;
                    clearInterval(this.countdownTimer);
                    return;
                }

                const h = Math.floor(ms / 3600000);
                const m = Math.floor((ms % 3600000) / 60000);
                const s = Math.floor((ms % 60000) / 1000);

                this.remaining = [h, m, s]
                    .map((n) => String(n).padStart(2, "0"))
                    .join(":");
            };

            tick();

            if (!this.expired && this.remaining) {
                this.countdownTimer = setInterval(tick, 1000);
            }
        },

        validate() {
            const e = {};
            if (this.needsName && this.name.trim().length < 3)
                e.name = "Isi nama lengkap sesuai KTP.";
            if (
                this.needsPhone &&
                !/^(\+62|62|0)8[0-9]{8,12}$/.test(
                    this.phone.replace(/[\s-]/g, ""),
                )
            ) {
                e.phone = "Nomor WhatsApp belum valid. Contoh: 081234567890";
            }
            if (this.needsNik && !/^\d{16}$/.test(this.nik))
                e.nik = "NIK harus 16 digit angka.";

            if (this.pickupMethod === "delivery") {
                if (this.addresses.length > 0 && !this.selectedAddressId) {
                    e.address = "Silakan pilih salah satu alamat pengantaran.";
                } else if (
                    this.addresses.length === 0 &&
                    this.address.trim().length < 5
                ) {
                    e.address =
                        "Silakan tambahkan alamat pengantaran terlebih dahulu.";
                }
            }
            if (
                !this.pickupDate ||
                !this.returnDate ||
                this.returnDate <= this.pickupDate
            ) {
                e.dates =
                    "Tanggal kembali harus setelah tanggal ambil (minimal 1 hari).";
            } else if (this.pickupDate < this.minDate) {
                e.dates = "Tanggal ambil tidak boleh sebelum hari ini.";
            }
            if (
                this.items.some((it) => !(it.qty >= 1 && it.qty <= it.max_qty))
            ) {
                e.items = "Jumlah unit tidak valid.";
            }

            this.errors = e;
            this.scrollToError();
            return Object.keys(e).length === 0;
        },

        scrollToError() {
            const e = this.errors;
            const target =
                e.name || e.phone || e.nik
                    ? "section-identity"
                    : e.address || e.notes
                      ? "section-delivery"
                      : e.dates || e.items
                        ? "section-rental"
                        : null;
            if (!target) return;
            const el = document.getElementById(target);
            if (el) el.scrollIntoView({ behavior: "smooth", block: "center" });
        },

        async submitBooking() {
            // Aturan bisnis: delivery hanya untuk akun login
            if (this.pickupMethod === "delivery" && !this.isLoggedIn) {
                window.location.href = this.loginUrl;
                return;
            }
            if (this.isProcessing) return;

            if (this.expired) {
                this.formError =
                    "Waktu penahanan alat sudah habis. Muat ulang halaman untuk memulai lagi.";
                return;
            }

            this.formError = "";
            if (!this.validate()) return;

            this.isProcessing = true;

            try {
                const fd = new FormData();
                fd.append("_token", this.csrf);
                fd.append("pickup_method", this.pickupMethod);
                fd.append("payment_option", this.paymentOption);
                fd.append("payment_channel", this.selectedChannel);

                // Jadwal & jumlah unit (harga dihitung ulang di server)
                fd.append("pickup_date", this.pickupDate);
                fd.append("return_date", this.returnDate);
                this.items.forEach((it, i) => {
                    fd.append("items[" + i + "][id]", it.id);
                    fd.append("items[" + i + "][qty]", it.qty);
                });

                // Data penyewa (hanya yang memang diminta)
                if (this.isGuest) {
                    fd.append("guest_name", this.name.trim());
                    fd.append("guest_phone", this.phone.trim());
                    fd.append("guest_id_number", this.nik);
                } else {
                    if (this.needsName) fd.append("name", this.name.trim());
                    if (this.needsPhone) fd.append("phone", this.phone.trim());
                    if (this.needsNik) fd.append("nik", this.nik);
                }

                // Alamat antar (member + delivery)
                if (this.pickupMethod === "delivery") {
                    if (this.selectedAddressId) {
                        fd.append(
                            "customer_address_id",
                            this.selectedAddressId,
                        );
                    }
                    fd.append("delivery_address", this.address.trim());
                    fd.append("delivery_notes", this.notes.trim());
                    fd.append("notes", this.notes.trim());
                }

                const response = await fetch(this.storeUrl, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrf,
                    },
                    body: fd,
                });

                const contentType = response.headers.get("content-type") || "";

                if (!contentType.includes("application/json")) {
                    throw new Error(
                        `Respons server tidak valid (HTTP ${response.status})`,
                    );
                }

                const data = await response.json();

                if (!response.ok) {
                    const map = {
                        guest_name: "name",
                        name: "name",
                        guest_phone: "phone",
                        phone: "phone",
                        guest_id_number: "nik",
                        nik: "nik",
                        delivery_address: "address",
                        delivery_notes: "notes",
                        pickup_date: "dates",
                        return_date: "dates",
                    };
                    const serverErrors = {};
                    Object.keys(data.errors || {}).forEach((k) => {
                        const key = k.startsWith("items") ? "items" : map[k];
                        if (key && !serverErrors[key])
                            serverErrors[key] = data.errors[k][0];
                    });
                    this.errors = serverErrors;
                    this.formError =
                        data.message || "Terjadi kesalahan. Silakan coba lagi.";
                    this.scrollToError();
                    this.isProcessing = false;
                    return;
                }

                if (!data.redirect_url) {
                    this.formError =
                        "Booking dibuat, tetapi halaman status tidak ditemukan. Buka Riwayat Sewa untuk melanjutkan pembayaran.";
                    this.isProcessing = false;
                    return;
                }

                await this.playHandoff(data);
            } catch (err) {
                this.formError =
                    "Koneksi bermasalah. Periksa internet Anda lalu coba lagi.";
                this.isProcessing = false;
            }
        },
    }));
});
