import { test, expect } from '@playwright/test';

// The demo account is the only admin (usually the only user too), so deleting it must be refused.
test('sole admin cannot delete their account', async ({ page }) => {
    await test.step('Open the profile page', async () => {
        await page.goto('/profile');
        await expect(page.getByRole('heading', { name: 'Profile', level: 1 })).toBeVisible();
    });

    const deleteButton = page.getByRole('button', { name: 'Delete Account' }).first();
    test.skip((await deleteButton.count()) === 0, 'Account deletion is hidden in demo mode');

    await test.step('Confirm account deletion with the password', async () => {
        await deleteButton.click();
        const modal = page.locator('form[wire\\:submit="deleteUser"]');
        await modal.locator('#password').fill('password');
        await modal.getByRole('button', { name: 'Delete Account' }).click();
    });

    await test.step('See that the only admin cannot be deleted', async () => {
        await expect(page.getByText(/only account, so it cannot be deleted|You are the only admin/)).toBeVisible();
        await test.info().attach('See that the only admin cannot be deleted', {
            body: await page.screenshot(),
            contentType: 'image/png',
        });
        await page.goto('/profile');
        await expect(page).toHaveURL(/\/profile/);
    });
});
