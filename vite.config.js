import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/pages/auth/fog-glass.css',
                'resources/js/pages/auth/fog-glass.js',
            ],
            refresh: true,
        }),
    ],
});
