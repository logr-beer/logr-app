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

    test('saved catalog beer api key is masked and can be cleared', async ({ page }) => {
        const field = page.locator('#catalog_beer_api_key');

        await test.step('Save a Catalog.beer API key', async () => {
            await page.goto('/admin/api');
            await field.fill('e2e-catalog-key-4321');
            await page.locator('form button[type="submit"]:has-text("Save")').click();
            await expect(page.locator('text=Saved.')).toBeVisible();
        });

        await test.step('Reload: the key is masked, not prefilled', async () => {
            await page.reload();
            await expect(field).toHaveValue('');
            await expect(field).toHaveAttribute('placeholder', '••••4321');
            expect(await page.content()).not.toContain('e2e-catalog-key-4321');
            await test.info().attach('Reload: the key is masked, not prefilled', { body: await page.screenshot(), contentType: 'image/png' });
        });

        await test.step('Clear the saved key', async () => {
            page.once('dialog', dialog => dialog.accept());
            await page.locator('button:has-text("Clear")').first().click();
            await expect(field).toHaveAttribute('placeholder', '');
        });
    });
});
