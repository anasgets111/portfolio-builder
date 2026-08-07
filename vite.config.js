import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/admin/theme.css',
            ],
            refresh: true,
            fonts: [
                bunny('Poppins', {
                    weights: [200, 400, 700, 900],
                    optimizedFallbacks: false,
                }),
                bunny('Inter', {
                    weights: [400, 700, 900],
                    optimizedFallbacks: false,
                }),
                bunny('Space Mono', {
                    weights: [400, 700],
                    optimizedFallbacks: false,
                }),
                bunny('Playfair Display', {
                    weights: [400, 700, 900],
                    optimizedFallbacks: false,
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
