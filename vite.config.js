import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js/react'),
        },
    },
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
                'resources/css/boleta_mensual.css',
                'resources/css/finanzas_compras.css',
                'resources/sass/app.scss',

                'resources/js/app.js',
                'resources/js/reports/delivery-links.js',
                'resources/js/index.js',
                'resources/js/finanzas_compras_proximo_pago.js',
                'resources/js/finanzas_general.js',
                'resources/js/boleta_mensual_panel.js',
                'resources/js/cobranzas_documentos.js',
                'resources/js/finanzas_compras_index.js',
                'resources/js/boleta_mensual_index.js',
                'resources/js/modal_pagos_masivos.js',
                'resources/js/finanzas_general_pagos_programados.js',

                // Modal crear cobranza / proveedor
                'resources/js/modules/cobranza/modalCrearCobranza.js',

                'resources/js/react/landing-test.jsx',
                'resources/js/react/landing/main.jsx',
                'resources/js/react/tracking/main.jsx',
                'resources/js/react/latam-tracking/main.jsx',
                'resources/js/react/sidebar/main.jsx',
            ],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        watch: {
            usePolling: true,
            interval: 250,
        },
    },
});