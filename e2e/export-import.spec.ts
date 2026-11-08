import { test, expect } from '@playwright/test';

test.describe('Export', () => {
    test('export button exists on profile page', async ({ page }) => {
        await page.goto('/profile');
        const exportButton = page.locator('a[href*="export"]');
        await expect(exportButton).toBeVisible();
        await expect(exportButton).toContainText('Export All Data');
    });

    test('export downloads a JSON file', async ({ page }) => {
        const [download] = await Promise.all([
            page.waitForEvent('download'),
            page.goto('/export'),
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

test.describe('Backup Import', () => {
    test('a backup with a broken row is rolled back', async ({ page }) => {
        const backup = {
            version: 'e2e',
            exported_at: new Date().toISOString(),
            beers: [{ name: 'E2E Rollback IPA' }],
            // Malformed: collection beers must be objects, not strings.
            collections: [{ name: 'E2E Broken Collection', is_dynamic: false, beers: ['E2E Rollback IPA'] }],
        };

        await test.step('Upload the backup file', async () => {
            await page.goto('/import');
            await page.locator('input[type="file"][wire\\:model="jsonFile"]').setInputFiles({
                name: 'logr-export-e2e.json',
                mimeType: 'application/json',
                buffer: Buffer.from(JSON.stringify(backup)),
            });
            await expect(page.getByText('Confirm Import')).toBeVisible();
            await expect(page.getByText('Import valid rows only')).toBeVisible();
            await test.info().attach('Upload the backup file', { body: await page.screenshot(), contentType: 'image/png' });
        });

        await test.step('Import it in the default transactional mode', async () => {
            await page.getByRole('button', { name: 'Import All Data' }).click();
            await expect(page.getByText('Import Rolled Back')).toBeVisible();
            await expect(page.getByText('E2E Broken Collection')).toBeVisible();
            await test.info().attach('Import it in the default transactional mode', { body: await page.screenshot(), contentType: 'image/png' });
        });

        await test.step('Nothing from the backup was saved', async () => {
            await page.goto('/beers?search=E2E%20Rollback%20IPA');
            await expect(page.getByText('E2E Rollback IPA')).toHaveCount(0);
        });
    });
});
