import { initScrollReveal } from "./scroll-reveal.js";
import { initCounters } from "./counters.js";
import { initHeroVideo } from "./hero-video.js";

const MONTH_NAMES_SHORT = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "Mei",
    "Jun",
    "Jul",
    "Agu",
    "Sep",
    "Okt",
    "Nov",
    "Des",
];

const registerBestsellerDateRange = () => {
    if (!window.Alpine) return;

    window.Alpine.data("bestsellerDateRange", (cfg = {}) => ({
        initialStart: cfg.initialStart || "",
        initialEnd: cfg.initialEnd || "",
        pickupDate: cfg.initialStart || "",
        returnDate: cfg.initialEnd || "",
        minDate: cfg.minDate || "",
        isLoading: false,
        calOpen: null,
        calYear: new Date().getFullYear(),
        calMonth: new Date().getMonth(),

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
        get calendarTitle() {
            return MONTH_NAMES_SHORT[this.calMonth] + " " + this.calYear;
        },
        openCalendar() {
            if (this.calOpen) {
                this.closeCalendar();
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
        closeCalendar() {
            if (!this.calOpen) return;
            this.calOpen = null;

            if (!this.pickupDate || !this.returnDate || this.returnDate <= this.pickupDate) {
                this.pickupDate = this.initialStart;
                this.returnDate = this.initialEnd;
                return;
            }

            if (
                this.pickupDate !== this.initialStart ||
                this.returnDate !== this.initialEnd
            ) {
                this.refreshItems();
            }
        },
        async refreshItems() {
            const form = this.$refs.dateForm;
            if (!form || this.isLoading) return;

            const actionUrl = new URL(form.getAttribute("action") || window.location.href, window.location.origin);
            actionUrl.searchParams.set("start_date", this.pickupDate);
            actionUrl.searchParams.set("end_date", this.returnDate);

            const fetchUrl = new URL(actionUrl.toString());
            fetchUrl.searchParams.set("bestsellers_partial", "1");

            const gridEl = document.getElementById("bestsellers-grid");
            this.isLoading = true;
            if (gridEl) gridEl.classList.add("opacity-60");

            try {
                const res = await fetch(fetchUrl.toString(), {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                if (!res.ok) throw new Error("Failed to load");

                const html = await res.text();
                const tpl = document.createElement("template");
                tpl.innerHTML = html.trim();

                const nextPeriod = tpl.content.querySelector("#bestsellers-stock-period");
                const currentPeriod = document.getElementById("bestsellers-stock-period");
                if (nextPeriod && currentPeriod) {
                    currentPeriod.replaceWith(nextPeriod);
                }

                const nextGrid = tpl.content.querySelector("#bestsellers-grid");
                if (nextGrid && gridEl) {
                    gridEl.innerHTML = nextGrid.innerHTML;
                    gridEl.querySelectorAll("[data-sr]").forEach((el) => {
                        el.classList.add("sr-visible");
                    });
                }

                this.initialStart = this.pickupDate;
                this.initialEnd = this.returnDate;
                window.history.replaceState({}, "", actionUrl.pathname + actionUrl.search + actionUrl.hash);

                if (window.lucide && typeof window.lucide.createIcons === "function") {
                    window.lucide.createIcons();
                }
            } catch (_) {
                form.submit();
            } finally {
                this.isLoading = false;
                if (gridEl) gridEl.classList.remove("opacity-60");
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
        buildCalendarCell(iso, outside = false) {
            const isPast = !!this.minDate && iso < this.minDate;
            const isStart = !!this.pickupDate && iso === this.pickupDate;
            const isEnd = !!this.returnDate && iso === this.returnDate;
            const inRange =
                !!this.pickupDate &&
                !!this.returnDate &&
                iso >= this.pickupDate &&
                iso <= this.returnDate;

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
            const firstDay =
                (new Date(this.calYear, this.calMonth, 1).getDay() + 6) % 7;
            const totalDays = new Date(
                this.calYear,
                this.calMonth + 1,
                0,
            ).getDate();

            for (let i = 0; i < firstDay; i++) days.push({ empty: true });

            const mm = String(this.calMonth + 1).padStart(2, "0");
            for (let d = 1; d <= totalDays; d++) {
                const dd = String(d).padStart(2, "0");
                days.push(this.buildCalendarCell(`${this.calYear}-${mm}-${dd}`));
            }

            const trailing = (7 - (days.length % 7)) % 7;
            let nextIso = this.addDaysIso(
                `${this.calYear}-${mm}-${String(totalDays).padStart(2, "0")}`,
                1,
            );
            for (let i = 0; i < trailing; i++) {
                days.push(this.buildCalendarCell(nextIso, true));
                nextIso = this.addDaysIso(nextIso, 1);
            }
            return days;
        },
        selectCalDate(iso) {
            if (
                this.calOpen === "return" &&
                this.pickupDate &&
                iso >= this.minReturnDate
            ) {
                this.returnDate = iso;
                this.calOpen = "pickup";
                return;
            }

            this.pickupDate = iso;
            if (this.returnDate && this.returnDate < this.minReturnDate) {
                this.returnDate = "";
            }
            this.calOpen = "return";
        },
        fmtRange() {
            if (!this.pickupDate) return "Pilih tanggal sewa";
            const a = new Date(this.pickupDate);
            if (isNaN(a)) return "Pilih tanggal sewa";
            const b = this.returnDate ? new Date(this.returnDate) : null;
            const fmt = (d, withYear) =>
                d.toLocaleDateString("id-ID", {
                    day: "numeric",
                    month: "short",
                    ...(withYear ? { year: "numeric" } : {}),
                    timeZone: "UTC",
                });
            if (!b || isNaN(b)) return fmt(a, true);
            const sameYear = a.getUTCFullYear() === b.getUTCFullYear();
            return fmt(a, !sameYear) + " – " + fmt(b, true);
        },
    }));
};

if (window.Alpine) {
    registerBestsellerDateRange();
} else {
    document.addEventListener("alpine:init", registerBestsellerDateRange);
}

const reducedMotion = window.matchMedia(
    "(prefers-reduced-motion:reduce)",
).matches;

initScrollReveal(reducedMotion);
initCounters(reducedMotion);
initHeroVideo(reducedMotion);

// Re-init lucide icons
if (window.lucide && typeof window.lucide.createIcons === "function") {
    window.lucide.createIcons();
}
