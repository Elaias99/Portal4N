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
                'resources/js/app.js',  // Mantén el archivo JS si lo usas
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',  // Escucha en todas las interfaces de red
        port: 3000,        // Vite escuchará en el puerto 3000
        hmr: {
            host: '192.168.1.153',  // Reemplaza con la IP de tu máquina
        },
    },
});
