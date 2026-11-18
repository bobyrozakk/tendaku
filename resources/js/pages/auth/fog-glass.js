const MEDIA = "(min-width: 1024px) and (hover: hover) and (pointer: fine)";
const REDUCED = "(prefers-reduced-motion: reduce)";

const SCALE = 0.4;
const BRUSH_MIN = 26;
const BRUSH_MAX = 44;
const GROW_STEP_MS = 33;
const GROW_ALPHA = 0.035;
const IDLE_STOP_MS = 4500;

const TINT = "rgba(15, 14, 58, 0.5)";
const HAZE = "rgba(236, 241, 255, 0.17)";

let state = null;
let resizeTimer = 0;

function coverFit(img, w, h) {
    const s = Math.max(w / img.naturalWidth, h / img.naturalHeight);
    const dw = img.naturalWidth * s;
    const dh = img.naturalHeight * s;
    return { dx: (w - dw) / 2, dy: (h - dh) / 2, dw, dh };
}

/** Foto buram + tint navy + kabut tipis */
function buildFog(img, w, h) {
    const fog = document.createElement("canvas");
    fog.width = w;
    fog.height = h;
    const g = fog.getContext("2d");
    g.imageSmoothingQuality = "high";

    const { dx, dy, dw, dh } = coverFit(img, w, h);
    g.drawImage(img, dx, dy, dw, dh);

    // blur
    const tiny = document.createElement("canvas");
    tiny.width = Math.max(16, Math.round(w / 12));
    tiny.height = Math.max(9, Math.round(h / 12));
    const tg = tiny.getContext("2d");
    tg.imageSmoothingQuality = "high";
    tg.drawImage(fog, 0, 0, tiny.width, tiny.height);
    g.imageSmoothingEnabled = true;
    if ("filter" in g) g.filter = "blur(2px)";
    g.drawImage(tiny, 0, 0, w, h);
    if ("filter" in g) g.filter = "none";

    g.fillStyle = TINT;
    g.fillRect(0, 0, w, h);

    g.fillStyle = HAZE;
    g.fillRect(0, 0, w, h);

    // awan embun tipis supaya tidak rata seperti plastik
    const m = Math.max(w, h);
    const clouds = [
        [0.2, 0.3, 0.5],
        [0.72, 0.18, 0.45],
        [0.55, 0.72, 0.55],
        [0.08, 0.88, 0.4],
        [0.92, 0.82, 0.42],
    ];
    for (const [cx, cy, r] of clouds) {
        const rg = g.createRadialGradient(
            cx * w,
            cy * h,
            0,
            cx * w,
            cy * h,
            r * m,
        );
        rg.addColorStop(0, "rgba(255, 255, 255, 0.10)");
        rg.addColorStop(1, "rgba(255, 255, 255, 0)");
        g.fillStyle = rg;
        g.fillRect(0, 0, w, h);
    }

    return fog;
}

/** Sprite sapuan: lingkaran dengan tepi halus, dipakai berulang. */
function buildBrush(radiusPx) {
    const d = Math.max(8, Math.round(radiusPx * 2 * SCALE));
    const c = document.createElement("canvas");
    c.width = c.height = d;
    const g = c.getContext("2d");
    const rg = g.createRadialGradient(d / 2, d / 2, 0, d / 2, d / 2, d / 2);
    rg.addColorStop(0, "rgba(0, 0, 0, 1)");
    rg.addColorStop(0.4, "rgba(0, 0, 0, 0.95)");
    rg.addColorStop(0.7, "rgba(0, 0, 0, 0.5)");
    rg.addColorStop(1, "rgba(0, 0, 0, 0)");
    g.fillStyle = rg;
    g.fillRect(0, 0, d, d);
    return c;
}

function sizeAndFill(s) {
    const w = Math.max(32, Math.ceil(window.innerWidth * SCALE));
    const h = Math.max(32, Math.ceil(window.innerHeight * SCALE));
    s.canvas.width = w;
    s.canvas.height = h;
    s.fog = buildFog(s.img, w, h);
    const radius = Math.min(
        BRUSH_MAX,
        Math.max(BRUSH_MIN, window.innerWidth * 0.02),
    );
    s.brush = buildBrush(radius);
    s.step = radius * SCALE * 0.3;
    fillFull(s);
}

function fillFull(s) {
    const ctx = s.ctx;
    ctx.globalCompositeOperation = "source-over";
    ctx.globalAlpha = 1;
    ctx.clearRect(0, 0, s.canvas.width, s.canvas.height);
    ctx.drawImage(s.fog, 0, 0);
}

function stamp(s, x, y) {
    const b = s.brush;
    s.ctx.drawImage(b, x - b.width / 2, y - b.height / 2);
}

/** Sapuan dari titik terakhir ke posisi kursor sekarang, dirapatkan supaya tidak putus-putus. */
function wipeTo(s, x, y) {
    const ctx = s.ctx;
    ctx.globalCompositeOperation = "destination-out";
    ctx.globalAlpha = 1;

    if (s.lastX === null) {
        stamp(s, x, y);
    } else {
        const dx = x - s.lastX;
        const dy = y - s.lastY;
        const dist = Math.hypot(dx, dy);
        const n = Math.min(60, Math.max(1, Math.ceil(dist / s.step)));
        for (let i = 1; i <= n; i++) {
            stamp(s, s.lastX + (dx * i) / n, s.lastY + (dy * i) / n);
        }
    }
    s.lastX = x;
    s.lastY = y;
}

function regrow(s) {
    const ctx = s.ctx;
    ctx.globalCompositeOperation = "source-over";
    ctx.globalAlpha = GROW_ALPHA;
    ctx.drawImage(s.fog, 0, 0);
    ctx.globalAlpha = 1;
}

function tick(now) {
    const s = state;
    if (!s) return;
    s.raf = 0;

    if (s.pending) {
        s.pending = false;
        wipeTo(s, s.px, s.py);
        s.lastActive = now;
    }

    if (now - s.lastGrow >= GROW_STEP_MS) {
        regrow(s);
        s.lastGrow = now;
    }

    if (now - s.lastActive > IDLE_STOP_MS) {
        fillFull(s);
        s.lastX = s.lastY = null;
        return;
    }

    s.raf = requestAnimationFrame(tick);
}

function wake(s) {
    if (s.raf) return;
    const now = performance.now();
    s.lastGrow = now;
    s.raf = requestAnimationFrame(tick);
}

function onMove(e) {
    const s = state;
    if (!s) return;
    s.px = e.clientX * (s.canvas.width / window.innerWidth);
    s.py = e.clientY * (s.canvas.height / window.innerHeight);
    s.pending = true;
    s.lastActive = performance.now();
    wake(s);
}

function onPointerEnd() {
    if (state) state.lastX = state.lastY = null;
}

function onLeave() {
    if (state) state.lastX = state.lastY = null;
}

function onVisibility() {
    if (document.hidden && state) {
        fillFull(state);
        state.lastX = state.lastY = null;
    }
}

function onResize() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        if (!shouldRun()) return teardown();
        if (!state) return init();
        sizeAndFill(state);
    }, 200);
}

function shouldRun() {
    return window.matchMedia(MEDIA).matches;
}

function teardown() {
    if (!state) return;
    cancelAnimationFrame(state.raf);
    window.removeEventListener("pointermove", onMove);
    window.removeEventListener("pointerup", onPointerEnd);
    window.removeEventListener("pointercancel", onPointerEnd);
    document.removeEventListener("mouseleave", onLeave);
    document.removeEventListener("visibilitychange", onVisibility);
    state.canvas.removeAttribute("data-ready");
    state = null;
}

async function init() {
    const canvas = document.querySelector("[data-auth-fog]");

    if (!canvas || !shouldRun()) {
        teardown();
        return;
    }
    if (state && state.canvas === canvas) return;
    teardown();

    const img = new Image();
    img.decoding = "async";
    img.src = canvas.dataset.src;
    try {
        await img.decode();
    } catch {
        return;
    }
    if (!canvas.isConnected || state) return;

    const ctx = canvas.getContext("2d");
    state = {
        canvas,
        ctx,
        img,
        fog: null,
        brush: null,
        step: 8,
        px: 0,
        py: 0,
        lastX: null,
        lastY: null,
        pending: false,
        raf: 0,
        lastGrow: 0,
        lastActive: 0,
    };
    sizeAndFill(state);
    canvas.setAttribute("data-ready", "");

    if (window.matchMedia(REDUCED).matches) return;

    window.addEventListener("pointermove", onMove, { passive: true });
    window.addEventListener("pointerup", onPointerEnd);
    window.addEventListener("pointercancel", onPointerEnd);
    document.addEventListener("mouseleave", onLeave);
    document.addEventListener("visibilitychange", onVisibility);
}

window.addEventListener("resize", onResize, { passive: true });
document.addEventListener("livewire:navigated", init);
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
} else {
    init();
}
