import { test, expect } from '@playwright/test';

test.describe('Dark Mode Toggle', () => {
    test('toggle button is visible in desktop nav', async ({ page }) => {
        await page.goto('/dashboard');
        const toggle = page.locator('nav button[title="Toggle dark mode"]');
        await expect(toggle).toBeVisible();
    });

    test('clicking toggle switches to dark mode', async ({ page }) => {
        await page.goto('/dashboard');

        // Clear any stored preference
        await page.evaluate(() => localStorage.removeItem('theme'));
        await page.reload();

        const html = page.locator('html');
        const toggle = page.locator('nav button[title="Toggle dark mode"]');

        // Click to enable dark mode
        await toggle.click();
        await expect(html).toHaveClass(/dark/);

        // Verify localStorage was set
        const theme = await page.evaluate(() => localStorage.getItem('theme'));
        expect(theme).toBe('dark');
    });

    test('clicking toggle again switches back to light mode', async ({ page }) => {
        await page.goto('/dashboard');
        await page.evaluate(() => localStorage.setItem('theme', 'dark'));
        await page.reload();

        const html = page.locator('html');
        const toggle = page.locator('nav button[title="Toggle dark mode"]');

        await expect(html).toHaveClass(/dark/);
        await toggle.click();
        await expect(html).not.toHaveClass(/dark/);

        const theme = await page.evaluate(() => localStorage.getItem('theme'));
        expect(theme).toBe('light');
    });

    test('dark mode persists across navigation', async ({ page }) => {
        await page.goto('/dashboard');
        await page.evaluate(() => localStorage.setItem('theme', 'dark'));
        await page.reload();

        await expect(page.locator('html')).toHaveClass(/dark/);

        await page.goto('/beers');
        await expect(page.locator('html')).toHaveClass(/dark/);
    });
});
