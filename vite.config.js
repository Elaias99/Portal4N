import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/landing.css',
                'resources/css/loader.css',
                'resources/css/login.css',
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
            host: '192.168.100.10',  // Reemplaza con la IP de tu máquina
        },
    },
});
