export function initProductHover(rm) {
    if (rm) return;
    document.querySelectorAll('.ld-prod-card').forEach(c => {
        c.style.transition = 'transform .25s ease,box-shadow .25s ease';
        c.addEventListener('mouseenter', () => {
            c.style.transform = 'translateY(-3px)';
            c.style.boxShadow = '0 10px 28px -4px rgba(43,34,2,.12)';
        });
        c.addEventListener('mouseleave', () => {
            c.style.transform = '';
            c.style.boxShadow = '';
        });
    });
}
