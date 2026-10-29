function statusPage(cfg) {
    const wait = (ms) => new Promise((r) => setTimeout(r, ms));
    const reduced = () =>
        window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    return {
        ...cfg,
        paying: false,
        syncing: false,
        celebrate: false,
        justDone: null,
        copied: false,
        remaining: "",
        vaBank: cfg.va?.bank ?? "bca",
        vaNumber: cfg.va?.number ?? "",
        vaBiller: cfg.va?.biller ?? "",
        vaLoading: false,
        vaOpen: false,
        flash: "",
        flashTone: "info",
        iconPath: {
            doc: "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z",
            card: "M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z",
            bag: "M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z",
            return: "M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6",
            id: "M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0M9 14h.01M13 14h2",
            flag: "M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9",
        },

        init() {
            this.startCountdown();
            // Daftar bank ditutup saat layar digulir/diubah ukurannya (posisinya fixed)
            const closeBank = () => {
                if (this.vaOpen) this.vaOpen = false;
            };
            window.addEventListener("scroll", closeBank, true);
            window.addEventListener("resize", closeBank);
            if (this.fresh) {
                // Booking baru: bulatan "Booking dibuat" membal, aktivitas masuk dari atas
                this.justDone = "created";
                this.activities.forEach((a) => {
                    a.fresh = true;
                });
                setTimeout(() => {
                    this.justDone = null;
                }, 900);
                // Hapus ?new=1 supaya refresh tidak mengulang animasi
                const u = new URL(window.location.href);
                u.searchParams.delete("new");
                window.history.replaceState({}, "", u.toString());
            }
        },

        /* ---------- State timeline ---------- */
        get current() {
            return this.statusToNode[this.status] ?? null;
        },
        get ci() {
            return this.current ? this.order.indexOf(this.current) : -1;
        },

        state(key) {
            if (key === "created") return "done";
            if (this.ci < 0) return "todo";
            const i = this.order.indexOf(key);
            if (i < this.ci) return "done";
            if (i === this.ci)
                return this.status === "completed" ? "done" : "active";
            return "todo";
        },
        // Hanya titik yang sedang berjalan yang terbuka. Yang sudah lewat atau belum sampai tetap tertutup.
        isOpen(key) {
            return key !== "created" && this.current === key;
        },
        lineFill(i) {
            return this.ci > i ? 100 : 0;
        },

        dotCls(key) {
            const s = this.state(key);
            if (s === "done")
                return "bg-avocado-600 border-avocado-600 text-white";
            if (s === "active")
                return "bg-sunglow-300 border-goldenrod-400 text-darkbrown-900";
            return "bg-white border-wheat-300 text-darkbrown-400";
        },
        cardCls(key) {
            const s = this.state(key);
            if (s === "active")
                return "bg-white border-goldenrod-400 ring-2 ring-goldenrod-400/20 shadow-tendaku-sm";
            if (s === "done") return "bg-white border-avocado-200";
            return "bg-[#FAF6ED] border-wheat-200";
        },
        toneCls(t) {
            return (
                {
                    green: "text-avocado-600",
                    amber: "text-goldenrod-600",
                    red: "text-red-600",
                }[t] || "text-darkbrown-500"
            );
        },

        /* ---------- Countdown ---------- */
        startCountdown() {
            if (!this.expiresAtIso) return;
            let timer;
            const tick = () => {
                if (this.status !== "pending") {
                    this.remaining = "";
                    clearInterval(timer);
                    return;
                }
                const ms = new Date(this.expiresAtIso) - new Date();
                if (isNaN(ms)) {
                    this.remaining = "";
                    clearInterval(timer);
                    return;
                }
                if (ms <= 0) {
                    this.remaining = "";
                    this.expired = true;
                    clearInterval(timer);
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
            timer = setInterval(tick, 1000);
        },

        /* ---------- Aksi ---------- */
        setFlash(msg, tone = "info") {
            this.flash = msg;
            this.flashTone = tone;
        },

        headers() {
            return {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": this.csrf,
                Accept: "application/json",
            };
        },

        copy(text) {
            if (!text || !navigator.clipboard) return;
            navigator.clipboard.writeText(text);
            this.copied = true;
            setTimeout(() => {
                this.copied = false;
            }, 2000);
        },

        toggleBank() {
            if (this.vaLoading || this.expired) return;
            if (this.vaOpen) {
                this.vaOpen = false;
                return;
            }
            this.vaOpen = true;
            this.$nextTick(() => this.placeBankMenu());
        },
        placeBankMenu() {
            const btn = this.$refs.vaBtn,
                menu = this.$refs.vaMenu;
            if (!btn || !menu) return;
            const r = btn.getBoundingClientRect();
            const h = menu.offsetHeight,
                w = menu.offsetWidth;
            const spaceBelow = window.innerHeight - r.bottom;
            const top =
                spaceBelow < h + 12 && r.top > h + 12
                    ? r.top - h - 6
                    : r.bottom + 6;
            menu.style.top = top + "px";
            menu.style.left =
                Math.max(8, Math.min(r.left, window.innerWidth - w - 8)) + "px";
        },
        pickBank(key) {
            this.vaOpen = false;
            this.changeBank(key);
        },

        // Ganti bank VA: minta server membuat tagihan baru ke Midtrans. Nomor tidak pernah dibuat di browser.
        async changeBank(next) {
            const prev = this.vaBank;
            if (this.vaLoading || next === prev) return;
            const prevNumber = this.vaNumber,
                prevBiller = this.vaBiller;
            this.flash = "";
            this.vaLoading = true;
            this.vaNumber = "";
            try {
                const res = await fetch(this.vaUrl, {
                    method: "POST",
                    headers: this.headers(),
                    body: JSON.stringify({ bank: next }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.va_number)
                    throw new Error(
                        data.message || "Gagal membuat nomor VA. Coba lagi.",
                    );
                this.vaBank = next;
                this.vaNumber = data.va_number;
                this.vaBiller = data.biller_code || "";
                if (data.order_id) this.orderId = data.order_id;
            } catch (e) {
                // Gagal: kembali ke bank & nomor sebelumnya (nomor lama masih aktif di Midtrans)
                this.vaBank = prev;
                this.vaNumber = prevNumber;
                this.vaBiller = prevBiller;
                this.setFlash(
                    e.message || "Koneksi bermasalah. Coba lagi.",
                    "error",
                );
            } finally {
                this.vaLoading = false;
            }
        },

        async payNow() {
            if (this.paying) return;
            this.paying = true;
            this.flash = "";
            try {
                const res = await fetch(this.payUrl, {
                    method: "POST",
                    headers: this.headers(),
                });
                const data = await res.json();

                if (data && data.snap_token) {
                    if (typeof window.snap !== "undefined") {
                        window.snap.pay(data.snap_token, {
                            onSuccess: () => this.sync(true),
                            // Belum dibayar tapi tagihan/VA sudah terbit: muat ulang agar nomor VA tampil
                            onPending: () => window.location.reload(),
                            onError: (r) =>
                                this.setFlash(
                                    "Pembayaran gagal: " +
                                        ((r && r.status_message) ||
                                            "terjadi kesalahan"),
                                    "error",
                                ),
                            onClose: () => this.sync(true),
                        });
                    } else {
                        this.setFlash(
                            "Jendela pembayaran belum siap. Muat ulang halaman lalu coba lagi.",
                            "error",
                        );
                    }
                } else {
                    this.setFlash(
                        (data && data.message) ||
                            "Gagal membuat tagihan pembayaran.",
                        "error",
                    );
                }
            } catch (e) {
                this.setFlash(
                    "Koneksi bermasalah. Periksa internet Anda lalu coba lagi.",
                    "error",
                );
            } finally {
                this.paying = false;
            }
        },

        async sync(silent = false) {
            if (this.syncing) return;
            this.syncing = true;
            if (!silent) this.flash = "";
            try {
                const res = await fetch(this.syncUrl, {
                    method: "POST",
                    headers: this.headers(),
                });
                const data = await res.json();

                if (data && data.is_paid) {
                    await this.handlePaid(data);
                } else if (!silent) {
                    this.setFlash("Pembayaran belum terdeteksi");
                }
            } catch (e) {
                if (!silent)
                    this.setFlash(
                        "Gagal mengecek status pembayaran. Coba lagi sebentar.",
                        "error",
                    );
            } finally {
                this.syncing = false;
            }
        },

        // Pembayaran terdeteksi: centang -> tab bayar menutup -> tab serah terima membuka
        async handlePaid(data) {
            // Pembayaran denda (bukan tagihan awal): cukup muat ulang agar angka terbaru tampil
            if (this.current !== "payment") {
                window.location.reload();
                return;
            }

            const slow = !reduced();

            this.celebrate = true;
            await wait(slow ? 1400 : 200);

            const now = new Date().toLocaleString("id-ID", {
                day: "2-digit",
                month: "short",
                year: "numeric",
                hour: "2-digit",
                minute: "2-digit",
            });
            this.times.payment = data.paid_at_text || now;
            this.activities.unshift({
                title: "Pembayaran diterima",
                sub: this.payAmountText,
                time: now,
                type: "card",
                tone: "green",
                fresh: true,
            });

            // Ubah status: bulatan bayar jadi centang, garis terisi, tab bayar menutup, tab serah terima membuka
            this.justDone = "payment";
            this.status = data.rental_status || data.status || "pending";

            await this.$nextTick();
            setTimeout(() => {
                const el = document.getElementById("node-" + this.current);
                if (el)
                    el.scrollIntoView({
                        behavior: reduced() ? "auto" : "smooth",
                        block: "nearest",
                    });
            }, 350);
            setTimeout(() => {
                this.celebrate = false;
                this.justDone = null;
            }, 900);
        },
    };
}

// Expose to Alpine expressions used in the Blade view.
window.statusPage = statusPage;
