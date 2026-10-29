/**
 * Playwright E2E config.
 *
 * Base URL: PLAYWRIGHT_BASE_URL, then APP_URL, then https://logr.ddev.site (DDEV).
 *   PLAYWRIGHT_BASE_URL=http://127.0.0.1:8000 npm run test:e2e
 *
 * The specs log in as the demo account (demo / password) from DemoSeeder. Seed it first:
 *   php artisan db:seed --class=DemoSeeder        (in DDEV: ddev artisan db:seed --class=DemoSeeder)
 */
import { defineConfig } from '@playwright/test';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const authFile = join(__dirname, 'playwright', '.auth', 'user.json');

export default defineConfig({
    testDir: './e2e',
    timeout: 30_000,
    expect: { timeout: 5_000 },
    fullyParallel: false,
    retries: 0,
    workers: 1,
    reporter: 'list',
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL || process.env.APP_URL || 'https://logr.ddev.site',
        ignoreHTTPSErrors: true,
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
    projects: [
        { name: 'setup', testMatch: /auth\.setup\.ts/ },
        {
            name: 'chromium',
            use: {
                browserName: 'chromium',
                storageState: authFile,
            },
            dependencies: ['setup'],
        },
    ],
});
