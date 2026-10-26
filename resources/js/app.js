import { createIcons, icons } from 'lucide';

let renderTimeout = null;

function renderLucideIcons() {
    if (renderTimeout) return;
    renderTimeout = requestAnimationFrame(() => {
        createIcons({ icons });
        renderTimeout = null;
    });
}

window.renderLucideIcons = renderLucideIcons;

document.addEventListener('DOMContentLoaded', () => {
    renderLucideIcons();
});

// Livewire v3 hooks
document.addEventListener('livewire:navigated', () => {
    renderLucideIcons();
});

document.addEventListener('livewire:init', () => {
    if (window.Livewire) {
        Livewire.hook('commit', ({ succeed }) => {
            succeed(() => {
                renderLucideIcons();
            });
        });
    }
});


