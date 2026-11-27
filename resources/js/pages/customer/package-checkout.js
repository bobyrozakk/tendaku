import { checkoutAddressState } from "../../components/checkout-address.js";

document.addEventListener("alpine:init", () => {
    Alpine.data("packageCheckout", (cfg) => ({
        ...cfg,
        errorMessage: "",
        isValid: true,
        isProcessing: false,
        formError: "",
        errors: {},
        nik: cfg.nik || "",
        phone: "",
        notes: "",
        address: "",
        touched: false,
        paymentOption: cfg.paymentOption || "full",
        showAddressPicker: false,

        // Serah terima setelah booking dibuat: form -> handoff -> halaman status
        phase: "form",
        createdCode: "",
        shippingReloadRequired: false,

        ...checkoutAddressState(cfg),
        shippingState: "incomplete",
        shipping: null,
        shippingMessage: "",
        shippingSequence: 0,
        shippingTimer: null,
        shippingController: null,
        shippingResultKey: "",
        shippingNeedsApproval: cfg.requiresApproval || false,

        // Calendar state
        calOpen: null,
        calYear: cfg.calYear !== undefined ? cfg.calYear : new Date().getFullYear(),
        calMonth: cfg.calMonth !== undefined ? cfg.calMonth : new Date().getMonth(),
        monthNamesShort: [
            "Jan", "Feb", "Mar", "Apr", "Mei", "Jun",
            "Jul", "Agu", "Sep", "Okt", "Nov", "Des",
        ],

        // Timepicker state (wheel, 07:00–17:00)
        timeOpen: false,
        timeItemH: 40,
        timeMinHour: 7,
        timeMaxHour: 17,
        timeHours: Array.from({ length: 11 }, (_, i) => 7 + i),
        timeMinutes: [0, 15, 30, 45],
        _timeMinTimer: null,
        _timeHourTimer: null,

        init() {
            this.recalculate();
            if (this.selectedAddressId) {
                const exists = this.addresses.some(a => parseInt(a.id) === parseInt(this.selectedAddressId));
                if (!exists && this.addresses.length > 0) {
                    this.selectedAddressId = this.addresses[0].id;
                }
            } else if (this.addresses.length > 0) {
                this.selectedAddressId = this.addresses[0].id;
            }
            this.$watch("shippingKey", () => this.scheduleShipping());
            this.$watch("pickupDate", () => this.dispatchWeatherRange());
            this.$watch("returnDate", () => this.dispatchWeatherRange());
            this.scheduleShipping();
            this.$nextTick(() => {
                if (typeof window.renderLucideIcons === "function") {
                    window.renderLucideIcons();
                }
            });
        },

        // ─── Timepicker ───────────────────────────────────────────────────────
        normTime(h, m) {
            h = Math.min(this.timeMaxHour, Math.max(this.timeMinHour, Number.isFinite(h) ? h : 9));
            m = Number.isFinite(m) ? m : 0;
            m = this.timeMinutes.reduce((a, b) => Math.abs(b - m) < Math.abs(a - m) ? b : a);
            if (h === this.timeMaxHour) m = 0;
            return [h, m];
        },
        get timeHour() {
            const [h, m] = (this.pickupTime || "09:00").split(":").map(n => parseInt(n, 10));
            return this.normTime(h, m)[0];
        },
        get timeMinute() {
            const [h, m] = (this.pickupTime || "09:00").split(":").map(n => parseInt(n, 10));
            return this.normTime(h, m)[1];
        },
        setTime(h, m) {
            [h, m] = this.normTime(h, m);
            this.pickupTime = String(h).padStart(2, "0") + ":" + String(m).padStart(2, "0");
            this.returnTime = this.pickupTime;
            this.touched = true;
        },
        toggleTime() {
            this.timeOpen = !this.timeOpen;
            this.calOpen = null;
            if (this.timeOpen) {
                this.setTime(this.timeHour, this.timeMinute);
                this.$nextTick(() => {
                    this.scrollColTo(this.$refs.hourCol, this.timeHours.indexOf(this.timeHour), false);
                    this.scrollColTo(this.$refs.minCol, this.timeMinutes.indexOf(this.timeMinute), false);
                });
            }
        },
        scrollColTo(el, index, smooth = true) {
            if (!el || index < 0) return;
            el.scrollTo({ top: index * this.timeItemH, behavior: smooth ? "smooth" : "auto" });
        },
        pickHour(h) {
            this.setTime(h, this.timeMinute);
            this.scrollColTo(this.$refs.hourCol, this.timeHours.indexOf(h));
            if (h === this.timeMaxHour) this.scrollColTo(this.$refs.minCol, 0);
        },
        pickMinute(m) {
            this.setTime(this.timeHour, m);
            this.scrollColTo(this.$refs.minCol, this.timeMinutes.indexOf(this.timeMinute));
        },
        onHourScroll() {
            const el = this.$refs.hourCol;
            if (!el) return;
            const idx = Math.min(this.timeHours.length - 1, Math.max(0, Math.round(el.scrollTop / this.timeItemH)));
            this.setTime(this.timeHours[idx], this.timeMinute);
            clearTimeout(this._timeHourTimer);
            this._timeHourTimer = setTimeout(() => {
                if (this.timeHour === this.timeMaxHour) this.scrollColTo(this.$refs.minCol, 0);
            }, 150);
        },
        onMinScroll() {
            const el = this.$refs.minCol;
            if (!el) return;
            const idx = Math.min(this.timeMinutes.length - 1, Math.max(0, Math.round(el.scrollTop / this.timeItemH)));
            this.setTime(this.timeHour, this.timeMinutes[idx]);
            clearTimeout(this._timeMinTimer);
            this._timeMinTimer = setTimeout(() => {
                if (this.timeHour === this.timeMaxHour && this.timeMinute === 0 && el.scrollTop > 1) {
                    this.scrollColTo(el, 0);
                }
            }, 150);
        },

        // ─── Calendar ─────────────────────────────────────────────────────────
        addDaysIso(iso, n) {
            const [y, m, d] = String(iso).split("-").map(Number);
            const dt = new Date(y, m - 1, d + n);
            return dt.getFullYear() + "-" + String(dt.getMonth() + 1).padStart(2, "0") + "-" + String(dt.getDate()).padStart(2, "0");
        },
        get minDate() {
            return cfg.minDate || new Date().toISOString().slice(0, 10);
        },
        get minReturnDate() {
            return this.pickupDate ? this.addDaysIso(this.pickupDate, 1) : this.minDate;
        },
        // Satu dropdown rentang: klik pertama = tanggal ambil, klik kedua = tanggal kembali
        openCalendar() {
            this.timeOpen = false;
            if (this.calOpen) {
                this.calOpen = null;
                return;
            }
            this.calOpen = "pickup";
            if (this.pickupDate) {
                const d = new Date(this.pickupDate);
                if (!isNaN(d)) {
                    this.calYear = d.getUTCFullYear();
                    this.calMonth = d.getUTCMonth();
                }
            }
        },
        get calendarTitle() {
            return this.monthNamesShort[this.calMonth] + " " + this.calYear;
        },
        prevMonth() {
            if (this.calMonth === 0) { this.calMonth = 11; this.calYear--; }
            else { this.calMonth--; }
        },
        nextMonth() {
            if (this.calMonth === 11) { this.calMonth = 0; this.calYear++; }
            else { this.calMonth++; }
        },
        buildCalendarCell(iso, outside = false) {
            const isPast = iso < this.minDate;
            const isStart = !!this.pickupDate && iso === this.pickupDate;
            const isEnd = !!this.returnDate && iso === this.returnDate;
            const inRange = !!this.pickupDate && !!this.returnDate && iso >= this.pickupDate && iso <= this.returnDate;
            return {
                day: Number(iso.slice(8, 10)),
                iso,
                outside,
                unavailable: isPast,
                disabled: isPast,
                start: isStart,
                end: isEnd,
                inRange,
            };
        },
        get calendarDays() {
            const days = [];
            // Minggu dimulai hari Senin (Sen ... Min)
            const firstDay = (new Date(this.calYear, this.calMonth, 1).getDay() + 6) % 7;
            const totalDays = new Date(this.calYear, this.calMonth + 1, 0).getDate();
            for (let i = 0; i < firstDay; i++) days.push({ empty: true });
            const mm = String(this.calMonth + 1).padStart(2, "0");
            for (let d = 1; d <= totalDays; d++) {
                days.push(this.buildCalendarCell(`${this.calYear}-${mm}-${String(d).padStart(2, "0")}`));
            }
            const trailing = (7 - (days.length % 7)) % 7;
            let nextIso = this.addDaysIso(`${this.calYear}-${mm}-${String(totalDays).padStart(2, "0")}`, 1);
            for (let i = 0; i < trailing; i++) {
                days.push(this.buildCalendarCell(nextIso, true));
                nextIso = this.addDaysIso(nextIso, 1);
            }
            return days;
        },
        selectCalDate(iso) {
            if (this.calOpen === "return" && this.pickupDate && iso >= this.minReturnDate) {
                this.returnDate = iso;
                this.returnTime = this.pickupTime;
                this.onReturnChange();
                this.calOpen = null;
                return;
            }
            // Langkah 1 (atau mulai ulang rentang): set tanggal ambil, lanjut pilih kembali
            this.pickupDate = iso;
            this.onPickupChange();
            this.calOpen = "return";
        },
        dispatchWeatherRange() {
            const detail = { pickupDate: this.pickupDate, returnDate: this.returnDate };
            if (typeof this.$dispatch === "function") this.$dispatch("booking-weather-updated", detail);
            if (typeof window !== "undefined" && window.Livewire?.dispatch) {
                window.Livewire.dispatch("booking-weather-updated", detail);
            }
            window.dispatchEvent(new CustomEvent("booking-weather-updated", { detail }));
        },

        // ─── Date helpers ─────────────────────────────────────────────────────
        fmtShort(iso) {
            const d = new Date(iso);
            return isNaN(d) ? "-" : d.toLocaleDateString("id-ID", { day: "numeric", month: "short", year: "numeric", timeZone: "UTC" });
        },
        fmtLong(iso) {
            const d = new Date(iso);
            return isNaN(d) ? "-" : d.toLocaleDateString("id-ID", { weekday: "long", day: "numeric", month: "long", year: "numeric", timeZone: "UTC" });
        },

        // "27 Nov – 30 Nov 2026"
        fmtRange() {
            if (!this.pickupDate) return "Pilih tanggal sewa";
            const a = new Date(this.pickupDate);
            if (isNaN(a)) return "Pilih tanggal sewa";
            const b = this.returnDate ? new Date(this.returnDate) : null;
            const fmt = (d, withYear) => d.toLocaleDateString("id-ID", {
                day: "numeric", month: "short", ...(withYear ? { year: "numeric" } : {}), timeZone: "UTC",
            });
            if (!b || isNaN(b)) return fmt(a, true);
            const sameYear = a.getUTCFullYear() === b.getUTCFullYear();
            return fmt(a, !sameYear) + " – " + fmt(b, true);
        },
        fmtRangeDay() {
            const a = new Date(this.pickupDate);
            if (isNaN(a)) return "-";
            const fmt = (d) => d.toLocaleDateString("id-ID", {
                weekday: "short", day: "numeric", month: "short", year: "numeric", timeZone: "UTC",
            });
            const b = new Date(this.returnDate);
            return isNaN(b) ? fmt(a) : fmt(a) + " – " + fmt(b);
        },
        formatMeasurement(value) {
            return Number(value).toLocaleString("id-ID", { maximumFractionDigits: 2 });
        },

        // ─── Duration / recalculate ───────────────────────────────────────────
        setDuration(days) {
            this.durationDays = Math.max(1, parseInt(days) || 1);
            if (!this.pickupDate) return;
            const [y, m, d] = this.pickupDate.split("-").map(Number);
            const [h, min] = (this.pickupTime || "09:00").split(":").map(Number);
            const pickup = new Date(y, m - 1, d, h, min, 0);
            if (isNaN(pickup.getTime())) return;
            const ret = new Date(pickup.getTime() + this.durationDays * 24 * 60 * 60 * 1000);
            this.returnDate = `${ret.getFullYear()}-${String(ret.getMonth() + 1).padStart(2, "0")}-${String(ret.getDate()).padStart(2, "0")}`;
            this.returnTime = `${String(ret.getHours()).padStart(2, "0")}:${String(ret.getMinutes()).padStart(2, "0")}`;
            this.recalculate();
        },
        onPickupChange() {
            this.touched = true;
            this.setDuration(this.durationDays);
        },
        onReturnChange() {
            this.touched = true;
            this.recalculate();
        },
        recalculate() {
            this.errorMessage = "";
            if (!this.pickupDate || !this.pickupTime || !this.returnDate || !this.returnTime) {
                this.isValid = false;
                return;
            }
            const [pY, pM, pD] = this.pickupDate.split("-").map(Number);
            const [pH, pMin] = this.pickupTime.split(":").map(Number);
            const pickup = new Date(pY, pM - 1, pD, pH, pMin, 0);
            const [rY, rM, rD] = this.returnDate.split("-").map(Number);
            const [rH, rMin] = this.returnTime.split(":").map(Number);
            const ret = new Date(rY, rM - 1, rD, rH, rMin, 0);
            if (isNaN(pickup.getTime()) || isNaN(ret.getTime())) { this.isValid = false; return; }
            const now = new Date();
            if (pickup.getTime() < now.getTime() - 60000) {
                this.errorMessage = "Waktu pengambilan tidak boleh di masa lalu.";
                this.isValid = false;
                return;
            }
            const diffMs = ret.getTime() - pickup.getTime();
            const diffHours = diffMs / (1000 * 60 * 60);
            if (diffMs <= 0) {
                this.errorMessage = "Waktu pengembalian wajib setelah waktu pengambilan (tidak boleh sama atau lebih awal).";
                this.isValid = false;
                this.durationDays = 0;
                this.totalHours = 0;
                this.subtotal = 0;
                return;
            }
            this.totalHours = Math.round(diffHours * 10) / 10;
            this.durationDays = Math.max(1, Math.ceil(diffHours / 24));
            this.subtotal = this.durationDays * this.packagePrice;
            this.isValid = true;
        },

        // ─── Formatting ───────────────────────────────────────────────────────
        formatRupiah(val) {
            if (val === null || val === undefined) return "—";
            return "Rp " + (Math.round(val) || 0).toLocaleString("id-ID");
        },
        measurement(value) {
            return Number(value).toLocaleString("id-ID", { maximumFractionDigits: 2 });
        },

        // ─── Address selection ────────────────────────────────────────────────
        get selectedAddress() {
            return this.getSelectedAddress ? this.getSelectedAddress() :
                   this.addresses.find(a => parseInt(a.id) === parseInt(this.selectedAddressId)) || null;
        },
        selectAddress(id) {
            this.selectedAddressId = id;
            this.showAddressPicker = false;
        },

        // ─── Shipping ─────────────────────────────────────────────────────────
        get shippingKey() {
            return JSON.stringify([this.packageId, this.pickupMethod, this.selectedAddressId, this.pickupDate, this.pickupTime, this.returnDate, this.returnTime]);
        },
        get shippingReady() {
            return !(this.pickupMethod === "delivery" && this.needsAddressLocation()) && this.shippingState === "ready" && this.shippingResultKey === this.shippingKey;
        },
        get deliveryFee() {
            return this.pickupMethod === "self_pickup" ? 0 : (this.shippingReady ? this.shipping.delivery_fee : null);
        },
        get grandTotal() {
            return this.shippingReady ? this.shipping.grand_total : null;
        },
        get rentalDays() {
            return this.durationDays;
        },
        get dpAmount() {
            if (!this.shippingReady) return null;
            return Math.floor((Math.max(0, this.shipping.grand_total) * this.dpPercentage + 99) / 100);
        },
        get payableAmount() {
            return this.paymentOption === "dp" ? this.dpAmount : this.grandTotal;
        },
        get canSubmitBooking() {
            return this.isValid && this.shippingReady && !this.isProcessing && !this.newAddressSubmitting && !this.showAddAddressModal && !this.shippingNeedsApproval;
        },
        get isGuest() {
            return false;
        },
        get needsName() {
            return false;
        },
        get totalWeightKg() {
            return this.shippingReady ? this.shipping.total_weight_kg : null;
        },
        validate() {
            const e = {};
            if (this.needsPhone && !/^[0-9]{10,13}$/.test(this.phone)) {
                e.phone = "Nomor ponsel harus terdiri dari 10 sampai 13 digit angka.";
            }
            if (this.needsNik && !/^\d{16}$/.test(this.nik)) e.nik = "NIK harus 16 digit angka.";
            if (this.pickupMethod === "delivery" && !this.selectedAddressId) {
                e.address = "Silakan pilih atau tambahkan alamat pengantaran terlebih dahulu.";
            }
            if (!this.isValid) e.dates = this.errorMessage || "Lengkapi jadwal sewa terlebih dahulu.";
            this.errors = e;
            const target = e.phone || e.nik ? "section-identity" : e.address ? "section-delivery" : e.dates ? "section-rental" : null;
            if (target) {
                this.$nextTick(() => document.getElementById(target)?.scrollIntoView({ behavior: "smooth", block: "start" }));
            }
            return Object.keys(e).length === 0;
        },
        async playHandoff(data) {
            const wait = (ms) => new Promise((r) => setTimeout(r, ms));
            const full = !window.matchMedia("(prefers-reduced-motion: reduce)").matches;
            this.createdCode = data.booking_code || "";
            const url = new URL(data.redirect_url, window.location.origin);
            url.searchParams.set("new", "1");
            window.scrollTo({ top: 0, behavior: full ? "smooth" : "auto" });
            this.phase = "handoff";
            await wait(full ? 2400 : 300);
            window.location.href = url.toString();
        },
        scheduleShipping() {
            clearTimeout(this.shippingTimer);
            this.shippingController?.abort();
            const sequence = ++this.shippingSequence;
            this.shipping = null;
            this.shippingResultKey = "";
            this.shippingState = "incomplete";
            this.recalculate();
            this.shippingMessage = this.isValid ? "Pilih alamat pengantaran terlebih dahulu." : (this.errorMessage || "Lengkapi jadwal sewa terlebih dahulu.");
            if (!this.isValid || (this.pickupMethod === "delivery" && !this.selectedAddressId)) return;
            if (this.pickupMethod === "delivery" && this.needsAddressLocation()) {
                this.shippingMessage = "Lengkapi titik lokasi alamat pengantaran terlebih dahulu.";
                return;
            }
            this.shippingState = "loading";
            this.shippingMessage = "Menghitung rincian pengiriman...";
            const key = this.shippingKey;
            this.shippingTimer = setTimeout(() => this.loadShipping(sequence, key), 450);
        },
        async loadShipping(sequence, key) {
            const controller = new AbortController();
            this.shippingController = controller;
            try {
                const response = await fetch(this.shippingPreviewUrl, {
                    method: "POST",
                    signal: controller.signal,
                    headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-TOKEN": this.csrf },
                    body: JSON.stringify({
                        checkout_type: "package",
                        package_id: this.packageId,
                        pickup_method: this.pickupMethod,
                        customer_address_id: this.pickupMethod === "delivery" ? this.selectedAddressId : null,
                        pickup_date: this.pickupDate,
                        pickup_time: this.pickupTime,
                        return_date: this.returnDate,
                        return_time: this.returnTime,
                    }),
                });
                const data = await response.json();
                if (sequence !== this.shippingSequence || key !== this.shippingKey) return;
                if (data.status === "out_of_delivery_range") {
                    this.shippingState = "out_of_range";
                    this.shippingMessage = data.message;
                    return;
                }
                if (!response.ok || !data.success) throw new Error(data.message || "Ongkir belum dapat dihitung.");
                this.applyShipping(data, key);
            } catch (error) {
                if (error.name === "AbortError" || sequence !== this.shippingSequence || key !== this.shippingKey) return;
                this.shippingState = "error";
                this.shippingMessage = error.message || "Koneksi bermasalah. Silakan coba lagi.";
            }
        },
        applyShipping(data, key) {
            if (Number(data.package_id) !== Number(this.packageId)) {
                this.shippingState = "error";
                this.shippingMessage = "Identitas paket berubah. Muat ulang halaman.";
                return;
            }
            this.shipping = data;
            this.shippingResultKey = key;
            this.shippingState = "ready";
            this.shippingMessage = "";
            this.packagePrice = data.package_price;
            this.packageName = data.package_name;
            const known = new Map((this.packageItems || []).map((it) => [Number(it.id), it]));
            this.packageItems = (data.items || []).map((it) => ({ ...(known.get(Number(it.id)) || {}), ...it, name: it.name || known.get(Number(it.id))?.name }));
            this.subtotal = data.subtotal;
            this.durationDays = data.rental_days;
            this.totalHours = data.total_hours;
        },
        approveShippingChange() {
            this.shippingNeedsApproval = false;
            this.formError = "";
        },

        // ─── Submit ───────────────────────────────────────────────────────────
        async submitBooking() {
            if (!this.canSubmitBooking || !this.validate()) return;
            this.isProcessing = true;
            this.formError = "";
            try {
                const body = new FormData();
                body.set("_token", this.csrf);
                body.set("package_id", this.packageId);
                body.set("pickup_date", this.pickupDate);
                body.set("pickup_time", this.pickupTime);
                body.set("return_date", this.returnDate);
                body.set("return_time", this.returnTime);
                body.set("pickup_method", this.pickupMethod);
                body.set("payment_option", this.paymentOption);
                if (this.pickupMethod === "delivery" && this.selectedAddressId) {
                    body.set("customer_address_id", this.selectedAddressId);
                }
                body.set("expected_delivery_fee", this.shipping.delivery_fee);
                body.set("expected_shipping_context", this.shipping.shipping_context);

                // NIK / phone if needed
                if (this.needsNik && this.nik) body.set("nik", this.nik);
                if (this.needsPhone && this.phone) body.set("phone", this.phone);
                body.set("checkout_attempt_id", this.checkoutAttemptId);

                const response = await fetch(this.storeUrl, {
                    method: "POST",
                    headers: { Accept: "application/json", "X-CSRF-TOKEN": this.csrf },
                    body,
                });
                const data = await response.json();
                if (response.status === 409 && data.shipping) {
                    this.applyShipping(data.shipping, this.shippingKey);
                    this.shippingNeedsApproval = true;
                    this.formError = data.message;
                    this.isProcessing = false;
                    return;
                }
                if (!response.ok) {
                    this.formError = Object.values(data.errors || {}).flat().join(" ") || data.message || "Pesanan belum dapat diproses.";
                    this.isProcessing = false;
                    return;
                }
                if (!data.redirect_url) throw new Error("Halaman status booking tidak tersedia.");
                await this.playHandoff(data);
            } catch (error) {
                this.formError = "Koneksi bermasalah. Silakan coba lagi; permintaan yang sama akan memakai booking yang sudah dibuat.";
                this.isProcessing = false;
            }
        },
        destroy() {
            clearTimeout(this.shippingTimer);
            this.shippingController?.abort();
        },
    }));
});
