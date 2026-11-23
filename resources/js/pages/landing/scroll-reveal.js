export function initScrollReveal(rm) {
    const els = document.querySelectorAll('[data-sr]');
    if (!els.length) return;
    if (rm) { els.forEach(el => el.classList.add('sr-visible')); return; }
    els.forEach(el => {
        const d = parseInt(el.getAttribute('data-sr-delay') || '0', 10);
        el.style.transitionDelay = d + 'ms';
    });
    const obs = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (!e.isIntersecting) return;
            e.target.classList.add('sr-visible');
            obs.unobserve(e.target);
        });
    }, { threshold: .07, rootMargin: '0px 0px -40px 0px' });
    els.forEach(el => obs.observe(el));
}
