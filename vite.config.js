import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/css/pages/auth/fog-glass.css",
                "resources/js/pages/auth/fog-glass.js",
                "resources/css/pages/landing/landing.css",
                "resources/js/pages/landing/landing.js",
                "resources/css/pages/customer/profile-rentals.css",
            ],
            refresh: true,
        }),
    ],
});