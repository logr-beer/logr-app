import { test, expect } from '@playwright/test';

test.describe('Export', () => {
    test('export button exists on profile page', async ({ page }) => {
        await page.goto('/profile');
        const exportButton = page.locator('a[href*="export"]');
        await expect(exportButton).toBeVisible();
        await expect(exportButton).toContainText('Export All Data');
    });

    test('export downloads a JSON file', async ({ page }) => {
        // Navigating straight to /export aborts page.goto ("Download is starting"),
        // so trigger the download the way a user does: via the profile page button.
        await page.goto('/profile');
        const [download] = await Promise.all([
            page.waitForEvent('download'),
            page.locator('a[href*="export"]').click(),
        ]);
        expect(download.suggestedFilename()).toMatch(/logr-export-.*\.json/);
    });
});

test.describe('Import Page', () => {
    test('import page shows both JSON and CSV sections', async ({ page }) => {
        await page.goto('/import');
        await expect(page.locator('text=Restore from Backup')).toBeVisible();
        await expect(page.locator('text=Import from CSV')).toBeVisible();
    });
});
