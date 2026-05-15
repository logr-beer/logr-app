import { test, expect } from '@playwright/test';

test.describe('API Settings', () => {
    test('page renders with Logr Pub section', async ({ page }) => {
        await page.goto('/admin/api');
        await expect(page.locator('h2')).toContainText('API Settings');
        await expect(page.locator('text=Logr Pub')).toBeVisible();
    });

    test('shows instance API key when provisioned', async ({ page }) => {
        await page.goto('/admin/api');
        // The key field should exist (either with a value or the "Get API Key" button)
        const keyField = page.locator('input[type="password"][readonly]');
        const getKeyButton = page.locator('button:has-text("Get API Key")');

        const hasKey = await keyField.count() > 0;
        const hasButton = await getKeyButton.count() > 0;

        expect(hasKey || hasButton).toBe(true);
    });

    test('Catalog.beer section is visible', async ({ page }) => {
        await page.goto('/admin/api');
        await expect(page.locator('text=Catalog.beer')).toBeVisible();
    });

    test('Untappd section is visible', async ({ page }) => {
        await page.goto('/admin/api');
        await expect(page.locator('h3:has-text("Untappd")')).toBeVisible();
    });

    test('secret key field is password type', async ({ page }) => {
        await page.goto('/admin/api');
        const secretField = page.locator('#pub_secret_key');
        if (await secretField.count() > 0) {
            await expect(secretField).toHaveAttribute('type', 'password');
        }
    });

    test('catalog beer api key field is password type', async ({ page }) => {
        await page.goto('/admin/api');
        const field = page.locator('#catalog_beer_api_key');
        await expect(field).toHaveAttribute('type', 'password');
    });
});
