function run(el) {
    const target = parseInt(el.dataset.counter, 10);
    const suffix = el.dataset.suffix || '';
    const decimal = parseInt(el.dataset.decimal || '0', 10);
    const dur = 1300, t0 = performance.now();
    (function tick(now) {
        const p = Math.min((now - t0) / dur, 1), e = 1 - Math.pow(1 - p, 3), v = target * e;
        el.textContent = (decimal > 0 ? v.toFixed(decimal) : Math.floor(v)) + suffix;
        if (p < 1) requestAnimationFrame(tick);
        else el.textContent = (decimal > 0 ? target.toFixed(decimal) : target) + suffix;
    })(t0);
}

export function initCounters(rm) {
    const els = document.querySelectorAll('[data-counter]');
    if (!els.length) return;
    if (rm) {
        els.forEach(el => {
            const t = parseInt(el.dataset.counter, 10);
            const s = el.dataset.suffix || '';
            const d = parseInt(el.dataset.decimal || '0', 10);
            el.textContent = (d > 0 ? t.toFixed(d) : t) + s;
        });
        return;
    }
    const obs = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (!e.isIntersecting) return;
            run(e.target);
            obs.unobserve(e.target);
        });
    }, { threshold: .5 });
    els.forEach(el => obs.observe(el));
}
