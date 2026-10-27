import { createIcons, icons } from "lucide";
import "./pages/customer/booking-status.js";
import "../css/pages/customer/booking-status.css";
import "../css/pages/customer/booking-receipt.css";

let renderTimeout = null;

function renderLucideIcons() {
    if (renderTimeout) return;
    renderTimeout = requestAnimationFrame(() => {
        createIcons({ icons });
        renderTimeout = null;
    });
}

window.renderLucideIcons = renderLucideIcons;

document.addEventListener("DOMContentLoaded", () => {
    renderLucideIcons();
});

// Livewire v3 hooks
document.addEventListener("livewire:navigated", () => {
    renderLucideIcons();
});

document.addEventListener("livewire:init", () => {
    if (window.Livewire) {
        Livewire.hook("commit", ({ succeed }) => {
            succeed(() => {
                renderLucideIcons();
            });
        });
    }
});
