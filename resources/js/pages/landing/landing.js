import { initScrollReveal } from "./scroll-reveal.js";
import { initCounters } from "./counters.js";
import { initHeroVideo } from "./hero-video.js";

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
