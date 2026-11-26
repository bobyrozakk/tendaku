/**
 * Smooth scrolling helper for cross-page anchor links and page-top scrolling.
 * Follows senior-frontend accessibility standards (prefers-reduced-motion).
 */

function prefersReducedMotion() {
    return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
}

function scrollToTarget(targetEl) {
    if (!targetEl) return;
    const behavior = prefersReducedMotion() ? "auto" : "smooth";
    targetEl.scrollIntoView({
        behavior: behavior,
        block: "start",
    });
}

function handleAnchorClick(e) {
    const link = e.target.closest("a");
    if (!link) return;

    const href = link.getAttribute("href");
    if (!href || href === "#" || href.startsWith("javascript:")) return;

    // Check if link contains a hash
    const url = new URL(link.href, window.location.href);
    const hasHash = url.hash.length > 1;

    // Check if target is on the same path
    const isSamePath = url.pathname === window.location.pathname;

    if (isSamePath && hasHash) {
        const targetId = url.hash.slice(1);

        if (targetId === "top") {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: prefersReducedMotion() ? "auto" : "smooth",
            });
            if (window.history.pushState) {
                window.history.pushState(null, "", window.location.pathname);
            }
            return;
        }

        const targetEl = document.getElementById(targetId);
        if (targetEl) {
            e.preventDefault();
            scrollToTarget(targetEl);
            if (window.history.pushState) {
                window.history.pushState(null, "", `#${targetId}`);
            }
        }
    } else if (isSamePath && !hasHash && (href === "/" || href === url.pathname)) {
        // Clicking "Beranda" while already on "/"
        if (url.pathname === "/") {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: prefersReducedMotion() ? "auto" : "smooth",
            });
        }
    }
}

function handleInitialHash() {
    if (window.location.hash && window.location.hash.length > 1) {
        const targetId = window.location.hash.slice(1);
        if (targetId === "top") {
            window.scrollTo({ top: 0, behavior: "auto" });
            return;
        }

        const targetEl = document.getElementById(targetId);
        if (targetEl) {
            setTimeout(() => {
                scrollToTarget(targetEl);
            }, 120);
        }
    }
}

document.addEventListener("click", handleAnchorClick);
document.addEventListener("DOMContentLoaded", handleInitialHash);
window.addEventListener("load", handleInitialHash);
document.addEventListener("livewire:navigated", handleInitialHash);
