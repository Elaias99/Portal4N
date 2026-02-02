import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    // base: '/Portal4N/public/',
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/custom.css',
                'resources/css/landing.css',
                'resources/css/loader.css',
                'resources/css/login.css',
                'resources/css/dashboard.css',
                'resources/css/appcustom.css',
                'resources/css/panel-finanzas.css',
                'resources/css/cuentas-cobrar.css',
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/js/reports/delivery-links.js',

            ],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            usePolling: true,
            interval: 250,
        },
    },
});
