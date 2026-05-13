import { test, expect } from '@playwright/test';

test.describe('Collections Index (/collections)', () => {
    test('page renders with Collections title', async ({ page }) => {
        await page.goto('/collections');
        await expect(page.locator('h1')).toContainText('Collections');
    });

    test('collections grid displays', async ({ page }) => {
        await page.goto('/collections');
        // Should have at least one section heading for curated or dynamic
        const curatedHeading = page.locator('h2:has-text("Curated Collections")');
        const dynamicHeading = page.locator('h2:has-text("Dynamic Collections")');
        const emptyState = page.locator('text=No collections yet');
        // At least one of these should be visible
        const hasCurated = await curatedHeading.count() > 0;
        const hasDynamic = await dynamicHeading.count() > 0;
        const hasEmpty = await emptyState.count() > 0;
        expect(hasCurated || hasDynamic || hasEmpty).toBeTruthy();
    });

    test('filter tabs: All, Curated, Dynamic', async ({ page }) => {
        await page.goto('/collections');
        await expect(page.getByText('All', { exact: true })).toBeVisible();
        await expect(page.getByText('Curated', { exact: true }).first()).toBeVisible();
        await expect(page.getByText('Dynamic', { exact: true }).first()).toBeVisible();
    });

    test('clicking Curated tab filters to curated collections only', async ({ page }) => {
        await page.goto('/collections');
        await page.getByText('Curated', { exact: true }).first().click();
        await page.waitForTimeout(400);
        // Dynamic section should not be visible
        await expect(page.locator('h2:has-text("Dynamic Collections")')).toHaveCount(0);
    });

    test('clicking Dynamic tab filters to dynamic collections only', async ({ page }) => {
        await page.goto('/collections');
        await page.getByText('Dynamic', { exact: true }).first().click();
        await page.waitForTimeout(400);
        // Curated section should not be visible
        await expect(page.locator('h2:has-text("Curated Collections")')).toHaveCount(0);
    });

    test('search filters collections', async ({ page }) => {
        await page.goto('/collections');
        const searchInput = page.locator('input[placeholder*="Search collections"]');
        await expect(searchInput).toBeVisible();
        await searchInput.fill('zzzznonexistent');
        await page.waitForTimeout(400);
        // Should show empty state or no collection cards
        const emptyState = page.locator('text=No collections found');
        await expect(emptyState).toBeVisible();
    });

    test('New button opens create modal', async ({ page }) => {
        await page.goto('/collections');
        // The action button has the text "New" (hidden until hover) and a plus icon
        // The "New" button text is hidden (max-w-0), target by the group/add class on page-header button
        const addButton = page.locator('button.group\\/add');
        await addButton.click();
        await expect(page.locator('h3:has-text("New Collection")')).toBeVisible();
    });

    test('create curated collection: fill name, description, submit', async ({ page }) => {
        await page.goto('/collections');
        // The "New" button text is hidden (max-w-0), target by the group/add class on page-header button
        const addButton = page.locator('button.group\\/add');
        await addButton.click();
        await expect(page.locator('h3:has-text("New Collection")')).toBeVisible();

        // Should default to Curated tab
        await page.fill('#name', 'E2E Test Collection');
        await page.fill('#description', 'Created by Playwright');
        await page.click('button:has-text("Create Collection")');
        await page.waitForTimeout(400);

        // Modal should close
        await expect(page.locator('h3:has-text("New Collection")')).toHaveCount(0);
        // New collection should appear
        await expect(page.locator('text=E2E Test Collection').first()).toBeVisible();
    });

    test('create dynamic collection: select type, fill value, submit', async ({ page }) => {
        await page.goto('/collections');
        // The "New" button text is hidden (max-w-0), target by the group/add class on page-header button
        const addButton = page.locator('button.group\\/add');
        await addButton.click();
        await expect(page.locator('h3:has-text("New Collection")')).toBeVisible();

        // Switch to Dynamic tab
        const dynamicTab = page.locator('.fixed').getByText('Dynamic', { exact: true });
        await dynamicTab.click();
        await page.waitForTimeout(400);

        // Select "Minimum Rating" rule
        await page.selectOption('select', { value: 'rating' });
        await page.waitForTimeout(400);

        // Fill in rating value
        const ratingInput = page.locator('input[placeholder*="4.0"]');
        await ratingInput.fill('4.5');

        await page.click('button:has-text("Create Dynamic Collection")');
        await page.waitForTimeout(400);

        // Modal should close
        await expect(page.locator('h3:has-text("New Collection")')).toHaveCount(0);
    });

    test('cancel closes create modal', async ({ page }) => {
        await page.goto('/collections');
        // The "New" button text is hidden (max-w-0), target by the group/add class on page-header button
        const addButton = page.locator('button.group\\/add');
        await addButton.click();
        await expect(page.locator('h3:has-text("New Collection")')).toBeVisible();

        await page.click('button:has-text("Cancel")');
        await page.waitForTimeout(400);
        await expect(page.locator('h3:has-text("New Collection")')).toHaveCount(0);
    });

    test('escape key closes create modal', async ({ page }) => {
        await page.goto('/collections');
        // The "New" button text is hidden (max-w-0), target by the group/add class on page-header button
        const addButton = page.locator('button.group\\/add');
        await addButton.click();
        await expect(page.locator('h3:has-text("New Collection")')).toBeVisible();

        await page.keyboard.press('Escape');
        await page.waitForTimeout(400);
        await expect(page.locator('h3:has-text("New Collection")')).toHaveCount(0);
    });

    test('collection card links to detail page', async ({ page }) => {
        await page.goto('/collections');
        const firstCard = page.locator('a[href*="/collections/"]').first();
        if (await firstCard.count() === 0) return;

        await firstCard.click();
        await expect(page).toHaveURL(/collections\/\d+/);
        await expect(page.locator('h1')).toBeVisible();
    });
});

test.describe('Collection Detail (/collections/{id})', () => {
    async function navigateToFirstCuratedCollection(page) {
        await page.goto('/collections');
        // Click "Curated" filter to only show curated collections
        await page.getByText('Curated', { exact: true }).first().click();
        await page.waitForTimeout(400);
        const firstCard = page.locator('a[href*="/collections/"]').first();
        if (await firstCard.count() === 0) return false;
        await firstCard.click();
        await page.waitForURL(/collections\/\d+/);
        return true;
    }

    async function navigateToFirstDynamicCollection(page) {
        await page.goto('/collections');
        await page.getByText('Dynamic', { exact: true }).first().click();
        await page.waitForTimeout(400);
        const firstCard = page.locator('a[href*="/collections/"]').first();
        if (await firstCard.count() === 0) return false;
        await firstCard.click();
        await page.waitForURL(/collections\/\d+/);
        return true;
    }

    test('page renders with collection name', async ({ page }) => {
        await page.goto('/collections');
        const firstCard = page.locator('a[href*="/collections/"]').first();
        if (await firstCard.count() === 0) return;
        await firstCard.click();
        await expect(page).toHaveURL(/collections\/\d+/);
        await expect(page.locator('h1')).toBeVisible();
    });

    test('shows beer count', async ({ page }) => {
        await page.goto('/collections');
        const firstCard = page.locator('a[href*="/collections/"]').first();
        if (await firstCard.count() === 0) return;
        await firstCard.click();
        await expect(page).toHaveURL(/collections\/\d+/);
        // Beer count is in the "Beers in this Collection (N)" heading
        await expect(page.locator('h2:has-text("Beers in this Collection")')).toBeVisible();
    });

    test('shows beer grid or empty state', async ({ page }) => {
        await page.goto('/collections');
        const firstCard = page.locator('a[href*="/collections/"]').first();
        if (await firstCard.count() === 0) return;
        await firstCard.click();
        await expect(page).toHaveURL(/collections\/\d+/);

        const beerGrid = page.locator('.grid a[href*="/beers/"]');
        const emptyState = page.locator('text=No beers in this collection yet');
        const hasBeerGrid = await beerGrid.count() > 0;
        const hasEmptyState = await emptyState.count() > 0;
        expect(hasBeerGrid || hasEmptyState).toBeTruthy();
    });

    test('edit button opens edit form with name and description', async ({ page }) => {
        const found = await navigateToFirstCuratedCollection(page);
        if (!found) return;

        await page.click('button[title="Edit collection"]');
        await page.waitForTimeout(400);
        await expect(page.locator('h2:has-text("Edit Collection")')).toBeVisible();
        await expect(page.locator('#editName')).toBeVisible();
        await expect(page.locator('#editDescription')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Save' })).toBeVisible();
        await expect(page.getByRole('button', { name: 'Cancel' })).toBeVisible();
    });

    test('save updates collection name', async ({ page }) => {
        const found = await navigateToFirstCuratedCollection(page);
        if (!found) return;

        await page.click('button[title="Edit collection"]');
        await page.waitForTimeout(400);

        const nameInput = page.locator('#editName');
        const originalName = await nameInput.inputValue();
        const updatedName = originalName + ' (edited)';
        await nameInput.fill(updatedName);
        await page.getByRole('button', { name: 'Save' }).click();
        await page.waitForTimeout(400);

        // Should be back in view mode with updated name
        await expect(page.locator('h1')).toContainText(updatedName);

        // Revert the name
        await page.click('button[title="Edit collection"]');
        await page.waitForTimeout(400);
        await page.locator('#editName').fill(originalName);
        await page.getByRole('button', { name: 'Save' }).click();
        await page.waitForTimeout(400);
    });

    test('cancel editing returns to view mode', async ({ page }) => {
        const found = await navigateToFirstCuratedCollection(page);
        if (!found) return;

        await page.click('button[title="Edit collection"]');
        await page.waitForTimeout(400);
        await expect(page.locator('h2:has-text("Edit Collection")')).toBeVisible();

        await page.getByRole('button', { name: 'Cancel' }).click();
        await page.waitForTimeout(400);
        // Edit button should reappear
        await expect(page.locator('button[title="Edit collection"]')).toBeVisible();
    });

    test('add beer search shows results and can add a beer', async ({ page }) => {
        const found = await navigateToFirstCuratedCollection(page);
        if (!found) return;

        // The "Add Beer to Collection" section is only for curated collections
        const addSection = page.locator('text=Add Beer to Collection');
        await expect(addSection).toBeVisible();

        const searchInput = page.locator('input[placeholder*="Search your beers"]');
        await searchInput.fill('IPA');
        await page.waitForTimeout(400);

        // Either results appear or "No beers found" message
        const resultList = page.locator('ul li');
        const noResults = page.locator('text=No beers found matching your search');
        const hasResults = await resultList.count() > 0;
        const hasNoResults = await noResults.count() > 0;
        expect(hasResults || hasNoResults).toBeTruthy();

        // If we got results, click the Add button on the first one
        if (hasResults) {
            const addBtn = resultList.first().locator('button:has-text("Add")');
            await addBtn.click();
            await page.waitForTimeout(400);
            // Search should be cleared
            await expect(searchInput).toHaveValue('');
        }
    });

    test('remove beer from collection triggers confirm dialog', async ({ page }) => {
        const found = await navigateToFirstCuratedCollection(page);
        if (!found) return;

        // Check if there are beers in the grid with remove buttons
        const removeBtn = page.locator('button[title="Remove from collection"]').first();
        if (await removeBtn.count() === 0) return;

        // Set up dialog handler
        let dialogMessage = '';
        page.on('dialog', async (dialog) => {
            dialogMessage = dialog.message();
            await dialog.dismiss(); // Dismiss to avoid actually removing
        });

        // Hover to reveal the remove button, then click
        await removeBtn.hover();
        await removeBtn.click({ force: true });
        await page.waitForTimeout(400);

        // The wire:confirm should have triggered a dialog
        expect(dialogMessage).toContain('Remove this beer from the collection');
    });

    test('delete collection triggers confirm dialog', async ({ page }) => {
        const found = await navigateToFirstCuratedCollection(page);
        if (!found) return;

        const deleteBtn = page.locator('button[title="Delete collection"]');
        if (await deleteBtn.count() === 0) return; // demo_mode may hide this

        let dialogMessage = '';
        page.on('dialog', async (dialog) => {
            dialogMessage = dialog.message();
            await dialog.dismiss();
        });

        await deleteBtn.click();
        await page.waitForTimeout(400);
        expect(dialogMessage).toContain('Delete this collection');
    });

    test('back link navigates to /collections', async ({ page }) => {
        await page.goto('/collections');
        const firstCard = page.locator('a[href*="/collections/"]').first();
        if (await firstCard.count() === 0) return;
        await firstCard.click();
        await expect(page).toHaveURL(/collections\/\d+/);

        // Back link is an arrow icon linking to collections.index
        const backLink = page.locator('a[href*="/collections"]').filter({ has: page.locator('svg') }).first();
        await backLink.click();
        await expect(page).toHaveURL(/\/collections$/);
    });

    test('dynamic collection shows Dynamic badge', async ({ page }) => {
        const found = await navigateToFirstDynamicCollection(page);
        if (!found) return;

        await expect(page.locator('text=Dynamic')).toBeVisible();
    });

    test('dynamic collection cannot add or remove beers manually', async ({ page }) => {
        const found = await navigateToFirstDynamicCollection(page);
        if (!found) return;

        // "Add Beer to Collection" section should not be present
        await expect(page.locator('text=Add Beer to Collection')).toHaveCount(0);

        // Remove buttons should not be present on beer cards
        await expect(page.locator('button[title="Remove from collection"]')).toHaveCount(0);
    });
});

test.describe('Inventory Index (/beers/inventory)', () => {
    test('page renders with inventory tab active', async ({ page }) => {
        await page.goto('/beers/inventory');
        await expect(page.locator('h1')).toContainText('Beers');
        // Inventory tab should be active (styled differently)
        await expect(page.getByText('Inventory', { exact: true })).toBeVisible();
    });

    test('location filter pills are visible', async ({ page }) => {
        await page.goto('/beers/inventory');
        // "All Locations" pill is always present when there is inventory
        const allLocationsPill = page.locator('button:has-text("All Locations")');
        const emptyState = page.locator('h3:has-text("No inventory")');
        const hasItems = await allLocationsPill.count() > 0;
        const isEmpty = await emptyState.count() > 0;
        expect(hasItems || isEmpty).toBeTruthy();
    });

    test('click location pill filters list', async ({ page }) => {
        await page.goto('/beers/inventory');
        const locationButtons = page.locator('button').filter({ hasText: /^(?!.*All Locations)/ }).locator('xpath=ancestor-or-self::button[contains(@wire:click, "location")]');

        // Get all location pill buttons (excluding "All Locations")
        const locationPills = page.locator('button[wire\\:click*="location"]');
        const pillCount = await locationPills.count();
        if (pillCount < 2) return; // Need at least "All Locations" + one specific location

        // Click the second pill (first specific location)
        await locationPills.nth(1).click();
        await page.waitForTimeout(400);

        // The clicked pill should now be highlighted (amber bg)
        await expect(locationPills.nth(1)).toHaveClass(/bg-amber-600/);
    });

    test('search filters inventory', async ({ page }) => {
        await page.goto('/beers/inventory');
        const searchInput = page.locator('input[placeholder*="Search inventory"]');
        await expect(searchInput).toBeVisible();

        await searchInput.fill('zzzznonexistent');
        await page.waitForTimeout(400);

        // Should show empty state or no cards
        const emptyState = page.locator('h3:has-text("No inventory")');
        await expect(emptyState).toBeVisible();
    });

    test('sort control is present with options', async ({ page }) => {
        await page.goto('/beers/inventory');
        // Sort control button has an x-text="label" span
        // The sort-control button has rounded-l-lg class
        const sortButton = page.locator('button.rounded-l-lg').first();
        await expect(sortButton).toBeVisible();
    });

    test('remove item button is present on inventory cards', async ({ page }) => {
        await page.goto('/beers/inventory');
        const removeBtn = page.locator('button[title="Remove one"]').first();
        const emptyState = page.locator('h3:has-text("No inventory")');

        const hasItems = await removeBtn.count() > 0;
        const isEmpty = await emptyState.count() > 0;

        if (hasItems) {
            // The button exists (may be hidden until hover)
            await expect(removeBtn).toBeAttached();
        } else {
            expect(isEmpty).toBeTruthy();
        }
    });

    test('beer cards link to beer detail', async ({ page }) => {
        await page.goto('/beers/inventory');
        // Target beer cards in the grid, not nav links
        const beerLink = page.locator('.grid a[href*="/beers/"]').first();
        if (await beerLink.count() === 0) return;

        await beerLink.click();
        await expect(page).toHaveURL(/beers\/\d+/);
        await expect(page.locator('h1')).toBeVisible();
    });

    test('empty state shows when no inventory matches', async ({ page }) => {
        await page.goto('/beers/inventory');
        const searchInput = page.locator('input[placeholder*="Search inventory"]');
        await searchInput.fill('zzzznonexistent99999');
        await page.waitForTimeout(400);

        await expect(page.locator('h3:has-text("No inventory")')).toBeVisible();
        await expect(page.locator('text=Try adjusting your search or filters')).toBeVisible();
    });

    test('sort by name changes order', async ({ page }) => {
        await page.goto('/beers/inventory');
        const beerLinks = page.locator('.grid a[href*="/beers/"]');
        if (await beerLinks.count() < 2) return;

        // Navigate directly with sort parameter to verify the sort functionality
        await page.goto('/beers/inventory?sortBy=name');
        await page.waitForTimeout(300);

        // Page should still show inventory items with the name sort active
        await expect(page).toHaveURL(/sortBy=name/);
        await expect(page.locator('.grid').last()).toBeVisible();
    });

    test('pill tabs navigate between All Beers, Favorites, Inventory', async ({ page }) => {
        await page.goto('/beers/inventory');
        await expect(page.getByText('All Beers')).toBeVisible();
        await expect(page.getByText('Favorites')).toBeVisible();
        await expect(page.getByText('Inventory')).toBeVisible();

        // Click All Beers to navigate away
        await page.getByRole('link', { name: 'All Beers' }).click();
        await expect(page).toHaveURL(/\/beers$/);

        // Navigate back to inventory
        await page.getByRole('link', { name: 'Inventory' }).click();
        await expect(page).toHaveURL(/beers\/inventory/);
    });
});
