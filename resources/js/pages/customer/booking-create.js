import { checkoutAddressState } from "../../components/checkout-address.js";

document.addEventListener("alpine:init", () => {
    Alpine.data("checkoutPage", (cfg) => ({
        ...cfg,
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
        shippingState: "incomplete",
        shipping: null,
        shippingMessage: "",
        shippingTimer: null,
        shippingController: null,
        shippingSequence: 0,
        shippingResultKey: "",
        shippingNeedsApproval: false,
        shippingReloadRequired: false,

        ...checkoutAddressState(cfg, {
            province: "Jawa Barat",
            city: "Bandung",
            is_default: true,
        }),
        showAddressPicker: false,

        // Serah terima setelah booking dibuat: form -> handoff
        phase: "form",
        createdCode: "",
        bookedDates: Array.isArray(cfg.bookedDates) ? [...cfg.bookedDates] : [],
        availabilityUrl: cfg.availabilityUrl || "",
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

        get bookedDatesSet() {
            return new Set(
                Array.isArray(this.bookedDates) ? this.bookedDates : [],
            );
        },
        isDateBooked(iso) {
            return this.bookedDatesSet.has(iso);
        },
        hasBookedDateInRange(startIso, endIso) {
            if (!startIso || !endIso || startIso > endIso) return false;
            let cur = startIso;
            while (cur <= endIso) {
                if (this.isDateBooked(cur)) return true;
                cur = this.addDaysIso(cur, 1);
            }
            return false;
        },
        findFirstValidReturnDate(pickup) {
            if (!pickup) return "";
            const candidate = this.addDaysIso(pickup, 1);
            if (!this.hasBookedDateInRange(pickup, candidate)) {
                return candidate;
            }
            return "";
        },
        async fetchAvailability(year, month) {
            if (!this.availabilityUrl) return;
            const mm = String(month + 1).padStart(2, "0");
            const from = `${year}-${mm}-01`;
            const lastDay = new Date(year, month + 1, 0).getDate();
            const to = `${year}-${mm}-${String(lastDay).padStart(2, "0")}`;

            try {
                const query = new URLSearchParams({ from, to });
                (this.items || []).forEach((it, idx) => {
                    query.append(`items[${idx}][id]`, it.id);
                    query.append(`items[${idx}][qty]`, it.qty);
                });
                const res = await fetch(
                    `${this.availabilityUrl}?${query.toString()}`,
                    {
                        headers: { Accept: "application/json" },
                    },
                );
                if (!res.ok) return;
                const data = await res.json();
                if (Array.isArray(data.dates)) {
                    const merged = new Set([
                        ...this.bookedDates,
                        ...data.dates,
                    ]);
                    this.bookedDates = Array.from(merged);
                }
            } catch (_) {}
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
            if (this.calOpen) {
                this.fetchAvailability(this.calYear, this.calMonth);
            }
        },
        prevMonth() {
            if (this.calMonth === 0) {
                this.calMonth = 11;
                this.calYear--;
            } else {
                this.calMonth--;
            }
            this.fetchAvailability(this.calYear, this.calMonth);
        },
        nextMonth() {
            if (this.calMonth === 11) {
                this.calMonth = 0;
                this.calYear++;
            } else {
                this.calMonth++;
            }
            this.fetchAvailability(this.calYear, this.calMonth);
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
                const isBooked = this.isDateBooked(iso);

                let isBlocked = false;
                if (this.calOpen === "pickup") {
                    isBlocked = isBooked;
                } else if (this.calOpen === "return") {
                    const isBeforePickup =
                        this.pickupDate && iso < this.minReturnDate;
                    const rangeBlocked =
                        this.pickupDate &&
                        this.hasBookedDateInRange(this.pickupDate, iso);
                    isBlocked = isBeforePickup || isBooked || rangeBlocked;
                }

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
                    disabled: isPast || isBlocked,
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
            if (
                this.pickupDate &&
                (!this.returnDate ||
                    this.returnDate <= this.pickupDate ||
                    this.hasBookedDateInRange(this.pickupDate, this.returnDate))
            ) {
                const candidate = this.findFirstValidReturnDate(
                    this.pickupDate,
                );
                if (candidate) {
                    this.returnDate = candidate;
                }
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
            this.$watch("shippingInputKey", () => this.scheduleShipping());
            this.scheduleShipping();
        },

        get shippingInputKey() {
            return JSON.stringify([
                this.pickupMethod,
                this.selectedAddressId,
                this.pickupDate,
                this.returnDate,
                this.pickupTime,
                this.items.map((item) => [item.id, Number(item.qty)]),
            ]);
        },
        get shippingReady() {
            return (
                !(
                    this.pickupMethod === "delivery" &&
                    this.needsAddressLocation()
                ) &&
                this.shippingState === "ready" &&
                this.shippingResultKey === this.shippingInputKey
            );
        },
        get canSubmitBooking() {
            return (
                !this.isProcessing &&
                !this.newAddressSubmitting &&
                !this.showAddAddressModal &&
                !this.expired &&
                this.shippingReady &&
                !this.shippingNeedsApproval &&
                !this.hasBookedDateInRange(this.pickupDate, this.returnDate)
            );
        },
        get totalWeightKg() {
            return this.shippingReady ? this.shipping.total_weight_kg : null;
        },
        formatMeasurement(value) {
            return Number(value).toLocaleString("id-ID", {
                maximumFractionDigits: 2,
            });
        },
        scheduleShipping() {
            clearTimeout(this.shippingTimer);
            this.shippingController?.abort();
            ++this.shippingSequence;
            this.shipping = null;
            this.shippingResultKey = "";
            this.shippingNeedsApproval = false;
            this.shippingReloadRequired = false;
            this.shippingState = "incomplete";
            this.shippingMessage =
                this.pickupMethod === "delivery"
                    ? "Pilih alamat dan jadwal untuk menghitung ongkir."
                    : "Lengkapi jadwal untuk menghitung rincian pesanan.";
            if (
                !this.pickupDate ||
                !this.returnDate ||
                (this.pickupMethod === "delivery" && !this.selectedAddressId)
            )
                return;
            if (
                this.pickupMethod === "delivery" &&
                this.needsAddressLocation()
            ) {
                this.shippingMessage =
                    "Lengkapi titik lokasi alamat pengantaran terlebih dahulu.";
                return;
            }
            this.shippingState = "loading";
            this.shippingMessage = "Menghitung rincian pengiriman…";
            const sequence = this.shippingSequence;
            const key = this.shippingInputKey;
            this.shippingTimer = setTimeout(
                () => this.loadShipping(sequence, key),
                450,
            );
        },
        async loadShipping(sequence, key) {
            const controller = new AbortController();
            this.shippingController = controller;
            try {
                const response = await fetch(this.shippingPreviewUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrf,
                    },
                    signal: controller.signal,
                    body: JSON.stringify({
                        checkout_type: "regular",
                        pickup_method: this.pickupMethod,
                        customer_address_id:
                            this.pickupMethod === "delivery"
                                ? this.selectedAddressId
                                : null,
                        pickup_date: this.pickupDate,
                        return_date: this.returnDate,
                        pickup_time: this.pickupTime,
                        items: this.items.map((item) => ({
                            id: item.id,
                            qty: Number(item.qty),
                        })),
                    }),
                });
                const data = await response.json();
                if (
                    sequence !== this.shippingSequence ||
                    key !== this.shippingInputKey
                )
                    return;
                if (!response.ok || !data.success)
                    throw new Error(
                        data.message || "Ongkir belum dapat dihitung.",
                    );
                this.applyShipping(data, key);
            } catch (error) {
                if (
                    error.name === "AbortError" ||
                    sequence !== this.shippingSequence ||
                    key !== this.shippingInputKey
                )
                    return;
                this.shippingState = "error";
                this.shippingMessage =
                    error.message || "Koneksi bermasalah. Silakan coba lagi.";
            }
        },
        applyShipping(data, key) {
            const ids = (items) =>
                items
                    .map((item) => Number(item.id))
                    .sort((a, b) => a - b)
                    .join(",");
            if (ids(data.items || []) !== ids(this.items)) {
                this.shippingState = "error";
                this.shippingReloadRequired = true;
                this.shippingMessage =
                    "Isi keranjang berubah. Muat ulang checkout untuk memeriksa produk terbaru.";
                return;
            }
            this.shipping = data;
            this.shippingResultKey = key;
            this.shippingState = "ready";
            this.shippingMessage = "";
            for (const priced of data.items || []) {
                const item = this.items.find(
                    (entry) => Number(entry.id) === Number(priced.id),
                );
                if (item) item.price_per_day = priced.price_per_day;
            }
        },
        approveShippingChange() {
            this.shippingNeedsApproval = false;
            this.formError = "";
        },
        destroy() {
            clearTimeout(this.shippingTimer);
            clearInterval(this.countdownTimer);
            this.shippingController?.abort();
        },

        get isGuest() {
            return !this.isLoggedIn;
        },
        get showAddressForm() {
            return this.needsAddress || this.editAddress;
        },

        get selectedAddress() {
            return this.getSelectedAddress();
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
            if (this.shippingReady) return this.shipping.subtotal;
            return (
                this.items.reduce(
                    (n, it) => n + it.price_per_day * (Number(it.qty) || 0),
                    0,
                ) * this.rentalDays
            );
        },
        get deposit() {
            return 0;
        },
        get deliveryFee() {
            if (this.pickupMethod === "self_pickup") return 0;
            return this.shippingReady ? this.shipping.delivery_fee : null;
        },
        get grandTotal() {
            if (!this.shippingReady) return null;
            return Math.max(
                0,
                this.subtotal + this.deliveryFee - this.discount,
            );
        },
        get dpAmount() {
            if (!this.shippingReady) return null;
            const base = Math.max(
                0,
                this.subtotal + this.deliveryFee - this.discount,
            );
            return Math.floor((base * this.dpPercentage + 99) / 100);
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
            if (num === null || num === undefined) return "—";
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
            this.fetchAvailability(this.calYear, this.calMonth);
        },
        onPickupChange() {
            if (!this.pickupDate) return;

            if (
                !this.returnDate ||
                this.returnDate <= this.pickupDate ||
                this.hasBookedDateInRange(this.pickupDate, this.returnDate)
            ) {
                this.returnDate = this.findFirstValidReturnDate(
                    this.pickupDate,
                );
            }

            this.touched = true;
        },

        onReturnChange() {
            this.touched = true;

            if (
                this.pickupDate &&
                this.returnDate &&
                (this.returnDate <= this.pickupDate ||
                    this.hasBookedDateInRange(this.pickupDate, this.returnDate))
            ) {
                this.returnDate = this.findFirstValidReturnDate(
                    this.pickupDate,
                );
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
            } else if (
                this.hasBookedDateInRange(this.pickupDate, this.returnDate)
            ) {
                e.dates =
                    "Sebagian tanggal pada periode ini sudah penuh dipesan.";
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
            if (!this.canSubmitBooking) return;

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

                // Jadwal & jumlah unit (harga dihitung ulang di server)
                fd.append("pickup_date", this.pickupDate);
                fd.append("return_date", this.returnDate);
                fd.append("pickup_time", this.pickupTime);
                fd.append("expected_delivery_fee", this.shipping.delivery_fee);
                fd.append(
                    "expected_shipping_context",
                    this.shipping.shipping_context,
                );
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

                if (response.status === 409 && data.shipping) {
                    this.shippingController?.abort();
                    ++this.shippingSequence;
                    this.applyShipping(data.shipping, this.shippingInputKey);
                    this.shippingNeedsApproval = this.shippingReady;
                    this.formError = data.message;
                    this.isProcessing = false;
                    return;
                }

                if (!response.ok) {
                    const map = {
                        guest_name: "name",
                        name: "name",
                        guest_phone: "phone",
                        phone: "phone",
                        guest_id_number: "nik",
                        nik: "nik",
                        delivery_address: "address",
                        customer_address_id: "address",
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
