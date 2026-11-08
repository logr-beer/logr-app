import { test, expect } from '@playwright/test';

// Leaflet is bundled by Vite; maps must render without fetching anything from a CDN.
test('maps render from the bundled Leaflet without CDN requests', async ({ page }) => {
    const cdnRequests: string[] = [];
    page.on('request', (request) => {
        if (request.url().includes('unpkg.com')) cdnRequests.push(request.url());
    });

    await test.step('Open the breweries map', async () => {
        await page.goto('/locations/breweries', { waitUntil: 'domcontentloaded' });
        const map = page.locator('#location-map');
        await expect(map).toHaveClass(/leaflet-container/);
        await expect(map.locator('.leaflet-marker-icon').first()).toBeVisible();
        await test.info().attach('Open the breweries map', { body: await page.screenshot(), contentType: 'image/png' });
    });

    await test.step('Open a brewery and edit its location', async () => {
        await page.getByText('With Location').click();
        await page.locator('a[href*="/locations/breweries/"]').first().click();
        await page.waitForURL(/locations\/breweries\/\d+/, { waitUntil: 'domcontentloaded' });
        await page.click('button[title*="Edit"]');

        const picker = page.locator('#location-picker-map');
        await expect(picker).toHaveClass(/leaflet-container/);
        // The draggable pin uses Leaflet's default marker image, served from the build
        const pin = picker.locator('img.leaflet-marker-icon');
        await expect(pin).toBeVisible();
        expect(await pin.evaluate((img: HTMLImageElement) => img.complete && img.naturalWidth > 0)).toBe(true);
        await test.info().attach('Open a brewery and edit its location', { body: await page.screenshot(), contentType: 'image/png' });
    });

    expect(cdnRequests).toEqual([]);
});
