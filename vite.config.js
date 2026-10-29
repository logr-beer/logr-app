import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Inside DDEV the dev server is reached through the DDEV router (https://logr.ddev.site:5173).
// Everywhere else (e.g. `php artisan serve` + `npm run dev`) use Vite's defaults.
const inDdev = Boolean(process.env.DDEV_HOSTNAME || process.env.DDEV_PRIMARY_URL);
const ddevHost = process.env.DDEV_HOSTNAME?.split(',')[0] || 'logr.ddev.site';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: inDdev
        ? {
              host: '0.0.0.0',
              port: 5173,
              strictPort: true,
              hmr: {
                  host: ddevHost,
                  protocol: 'wss',
              },
          }
        : undefined,
});
