import { test, expect, type Page } from '@playwright/test';

/**
 * Helper: wait for Livewire to finish processing after an interaction.
 */
async function livewireWait(page: Page, ms = 500) {
    await page.waitForTimeout(ms);
}

/**
 * Helper: accept the next browser dialog (wire:confirm).
 */
function acceptNextDialog(page: Page) {
    page.once('dialog', (d) => d.accept());
}

// ============================================================================
// 1. Beer CRUD Workflow
// ============================================================================
test.describe.serial('Beer CRUD Workflow', () => {
    const beerName = `E2E Workflow Beer ${Date.now()}`;
    const beerNameUpdated = `${beerName} Updated`;
    let beerUrl: string;

    test('create a new beer', async ({ page }) => {
        await page.goto('/beers/create');
        await expect(page.locator('h1')).toContainText('Add New Beer');

        // Fill core fields
        await page.locator('#name').fill(beerName);
        await page.locator('#abv').fill('6.5');
        await page.locator('#ibu').fill('45');

        // Submit
        await page.getByRole('button', { name: 'Add Beer' }).click();
        await page.waitForURL(/beers\/\d+/, { timeout: 10_000 });

        // Verify beer detail page
        await expect(page.locator('h1')).toContainText(beerName);
        beerUrl = page.url();
    });

    test('beer appears in search on /beers', async ({ page }) => {
        await page.goto('/beers');
        await page.fill('input[placeholder*="Search beers"]', beerName);
        await livewireWait(page, 800);

        // Verify the beer card with our name appears (check h3 inside card)
        const beerHeading = page.locator('h3').filter({ hasText: beerName });
        await expect(beerHeading.first()).toBeVisible({ timeout: 5_000 });
    });

    test('edit the beer name', async ({ page }) => {
        await page.goto(beerUrl);
        await expect(page.locator('h1')).toContainText(beerName);

        // Navigate to edit
        await page.locator('a[title="Edit beer"]').click();
        await page.waitForURL(/beers\/\d+\/edit/);
        await expect(page.locator('h1')).toContainText('Edit Beer');

        // Change name
        await page.locator('#name').fill(beerNameUpdated);

        // Submit
        await page.getByRole('button', { name: 'Update Beer' }).click();
        await page.waitForURL(/beers\/\d+$/, { timeout: 10_000 });

        // Verify updated name
        await expect(page.locator('h1')).toContainText(beerNameUpdated);
    });

    test('delete the beer', async ({ page }) => {
        await page.goto(beerUrl);
        await expect(page.locator('h1')).toContainText(beerNameUpdated);

        // Delete from detail page
        acceptNextDialog(page);
        await page.locator('button[wire\\:click="deleteBeer"]').click();
        await page.waitForURL(/\/beers$/, { timeout: 10_000 });
    });

    test('deleted beer no longer appears in search', async ({ page }) => {
        await page.goto('/beers');
        await page.fill('input[placeholder*="Search beers"]', beerNameUpdated);
        await livewireWait(page, 800);

        const beerHeading = page.locator('h3').filter({ hasText: beerNameUpdated });
        await expect(beerHeading).toHaveCount(0);
    });
});

// ============================================================================
// 2. Checkin Workflow
// ============================================================================
test.describe.serial('Checkin Workflow', () => {
    test('create a checkin via /checkins/create', async ({ page }) => {
        await page.goto('/checkins/create');
        await expect(page.locator('h1')).toContainText('New Check-in');

        // Search for a beer — type a common partial term to find existing beers
        const beerSearchInput = page.locator('input[placeholder="Search for a beer..."]');
        await beerSearchInput.fill('IPA');
        await livewireWait(page, 800);

        // Wait for dropdown results and click the first local result
        const firstBeerResult = page.locator('button:has-text("In Library")').first();
        await expect(firstBeerResult).toBeVisible({ timeout: 5_000 });
        await firstBeerResult.click();
        await livewireWait(page);

        // Verify beer was selected — the clear button appears inside the beer pill
        const clearBeerButton = page.locator('button[wire\\:click="clearBeer"]');
        await expect(clearBeerButton).toBeVisible();

        // Set rating
        await page.locator('#rating').fill('4');

        // Set serving type via custom-select — click the dropdown trigger then pick "Draft"
        const servingTrigger = page.locator('button:has-text("Select...")').first();
        await servingTrigger.click();
        await livewireWait(page, 200);
        await page.locator('button:has-text("Draft")').first().click();
        await livewireWait(page);

        // Fill notes
        await page.locator('#notes').fill('Great test beer - E2E');

        // Submit
        await page.getByRole('button', { name: 'Check In' }).click();
        await livewireWait(page, 2000);

        // Should redirect to /checkins
        await expect(page).toHaveURL(/checkins/, { timeout: 10_000 });
    });

    test('new checkin appears and can be verified via edit page', async ({ page }) => {
        await page.goto('/checkins');
        await livewireWait(page);

        // Checkin cards show beer name / date, not notes. Click the first (newest) card
        // and verify notes appear on the edit page.
        const firstCard = page.locator('a[href*="/checkins/"][href*="/edit"]').first();
        await expect(firstCard).toBeVisible();
        await firstCard.click();
        await page.waitForURL(/checkins\/\d+\/edit/, { timeout: 5_000 });

        // Verify our notes text appears on the edit page
        await expect(page.locator('#notes')).toHaveValue('Great test beer - E2E');
    });

    test('clean up: delete the checkin', async ({ page }) => {
        await page.goto('/checkins');
        await livewireWait(page);

        // Click the first (most recent) checkin card to go to edit
        const firstCard = page.locator('a[href*="/checkins/"][href*="/edit"]').first();
        await firstCard.click();
        await page.waitForURL(/checkins\/\d+\/edit/, { timeout: 5_000 });

        // Verify it's our checkin by checking notes
        const notes = await page.locator('#notes').inputValue();
        if (notes !== 'Great test beer - E2E') return; // Not our checkin, skip cleanup

        // Delete
        acceptNextDialog(page);
        await page.locator('button[wire\\:click="deleteCheckin"]').click();
        await page.waitForURL(/\/checkins$/, { timeout: 10_000 });
    });
});

// ============================================================================
// 3. Inventory Workflow
// ============================================================================
test.describe.serial('Inventory Workflow', () => {
    let beerDetailUrl: string;

    test('add inventory to an existing beer', async ({ page }) => {
        // Navigate to first beer detail
        await page.goto('/beers');
        const firstCard = page.locator('a[href*="/beers/"]:not([href*="create"]):not([href*="inventory"]):not([href*="export"])').first();
        await expect(firstCard).toBeVisible();
        await firstCard.click();
        await page.waitForURL(/beers\/\d+/);
        beerDetailUrl = page.url();

        // Open inventory form
        const addToggle = page.locator('button:has-text("+ Add")');
        await expect(addToggle).toBeVisible();
        await addToggle.click();
        await livewireWait(page);

        // Fill storage location
        await page.locator('#storageLocation').fill('E2E Test Cellar');
        await livewireWait(page, 400);

        // Close any storage location dropdown by clicking elsewhere
        await page.locator('h2:has-text("Inventory")').click();
        await livewireWait(page, 200);

        // Set quantity
        await page.locator('#addQuantity').fill('3');

        // Click "Add to Inventory"
        await page.getByRole('button', { name: 'Add to Inventory' }).click();
        await livewireWait(page, 1000);

        // Verify inventory item appears — look for the storage location text
        // within the inventory card (span with specific class)
        await expect(
            page.locator('span.text-sm.font-medium').filter({ hasText: 'E2E Test Cellar' }).first()
        ).toBeVisible();
    });

    test('beer appears in /beers/inventory', async ({ page }) => {
        await page.goto('/beers/inventory');
        await livewireWait(page);

        // The inventory page should show our beer's storage location
        await expect(page.getByText('E2E Test Cellar').first()).toBeVisible();
    });

    test('clean up: remove inventory items', async ({ page }) => {
        await page.goto(beerDetailUrl);
        await livewireWait(page);

        // Remove all "E2E Test Cellar" inventory by clicking the minus button repeatedly
        // Each click decrements quantity by 1; keep going until the row disappears
        const maxAttempts = 20;
        for (let i = 0; i < maxAttempts; i++) {
            const inventoryRow = page.locator('div').filter({ hasText: 'E2E Test Cellar' }).locator('button[wire\\:click*="removeFromFridge"]').first();
            if (await inventoryRow.count() === 0) break;
            await inventoryRow.click();
            await livewireWait(page, 600);
        }

        // Verify the "E2E Test Cellar" inventory row is gone from the inventory section
        const inventoryItem = page.locator('span.text-sm.font-medium').filter({ hasText: 'E2E Test Cellar' });
        await expect(inventoryItem).toHaveCount(0);
    });
});

// ============================================================================
// 4. Collection Workflow
// ============================================================================
test.describe.serial('Collection Workflow', () => {
    const collectionName = `E2E Test Collection ${Date.now()}`;
    let collectionUrl: string;

    test('create a new collection', async ({ page }) => {
        await page.goto('/collections');
        await expect(page.locator('h1')).toContainText('Collections');

        // Click the "+New" button in the page header
        const addButton = page.locator('.group\\/add');
        await expect(addButton).toBeVisible();
        await addButton.click();
        await livewireWait(page);

        // Modal should be open
        await expect(page.locator('h3:has-text("New Collection")')).toBeVisible();

        // Fill name and description
        await page.locator('#name').fill(collectionName);
        await page.locator('#description').fill('E2E test description');

        // Submit
        await page.getByRole('button', { name: 'Create Collection' }).click();
        await livewireWait(page, 2000);

        // After creation, it may redirect to the collection detail or stay on /collections
        const currentUrl = page.url();
        if (/collections\/\d+/.test(currentUrl)) {
            // Redirected to detail page
            collectionUrl = currentUrl;
            await expect(page.locator('h1')).toContainText(collectionName);
        } else {
            // Stayed on /collections — find the collection in the list and click it
            const collectionLink = page.locator('a').filter({ hasText: collectionName }).first();
            await expect(collectionLink).toBeVisible({ timeout: 5_000 });
            await collectionLink.click();
            await page.waitForURL(/collections\/\d+/, { timeout: 10_000 });
            collectionUrl = page.url();
            await expect(page.locator('h1')).toContainText(collectionName);
        }
    });

    test('add a beer to the collection', async ({ page }) => {
        await page.goto(collectionUrl);
        await expect(page.locator('h1')).toContainText(collectionName);

        // Type in the add beer search field — uses wire:model.live.debounce + wire:keyup
        const addBeerInput = page.locator('input[placeholder="Search your beers..."]');
        await expect(addBeerInput).toBeVisible();
        await addBeerInput.fill('Ale');
        await livewireWait(page, 500);
        // Trigger keyup to invoke searchBeers() after model has updated
        await addBeerInput.press('e');
        await livewireWait(page, 1500);

        // Click the "Add" button next to the first search result
        const addBeerButton = page.getByRole('button', { name: /Add/ }).first();
        await expect(addBeerButton).toBeVisible({ timeout: 5_000 });
        await addBeerButton.click();
        await livewireWait(page, 1000);

        // Verify the beer appears in the collection grid (count should be >= 1)
        const beerCount = page.locator('text=Beers in this Collection').first();
        await expect(beerCount).toBeVisible();
        const countText = await beerCount.textContent();
        expect(countText).not.toContain('(0)');
    });

    test('remove the beer from the collection', async ({ page }) => {
        await page.goto(collectionUrl);
        await livewireWait(page);

        // Hover over the beer card to reveal the remove button, then click it
        const beerCard = page.locator('.group.relative.rounded-lg').first();
        if (await beerCard.count() > 0) {
            await beerCard.hover();
            acceptNextDialog(page);
            await beerCard.locator('button[wire\\:click*="removeBeer"]').click();
            await livewireWait(page, 1000);
        }

        // Verify collection is empty
        await expect(page.locator('text=No beers in this collection yet')).toBeVisible();
    });

    test('delete the collection', async ({ page }) => {
        await page.goto(collectionUrl);
        await livewireWait(page);

        // Click delete button
        acceptNextDialog(page);
        await page.locator('button[wire\\:click="deleteCollection"]').click();
        await page.waitForURL(/\/collections$/, { timeout: 10_000 });
    });

    test('deleted collection no longer appears', async ({ page }) => {
        await page.goto('/collections');
        await livewireWait(page);

        // Search for the collection name
        const searchInput = page.locator('input[placeholder*="Search collections"]');
        if (await searchInput.count() > 0) {
            await searchInput.fill(collectionName);
            await livewireWait(page, 800);
        }

        // Should not find it
        await expect(page.locator('h3').filter({ hasText: collectionName })).toHaveCount(0);
    });
});

// ============================================================================
// 5. Location Edit Workflow
// ============================================================================
test.describe.serial('Location Edit Workflow', () => {
    const locationName = `E2E Test Brewery Location ${Date.now()}`;
    const locationNameUpdated = `E2E Updated Brewery ${Date.now()}`;
    let locationUrl: string;

    test('create a new brewery location', async ({ page }) => {
        await page.goto('/locations/breweries');
        await expect(page.locator('h1')).toContainText('Locations');

        // Click +New button
        const newButton = page.locator('.group\\/add');
        await expect(newButton).toBeVisible();
        await newButton.click();
        await page.waitForURL(/locations\/breweries\/\d+/, { timeout: 10_000 });

        // Should be in edit mode — Save button visible
        await expect(page.getByRole('button', { name: 'Save' })).toBeVisible();

        locationUrl = page.url();

        // Fill name — the name input uses live debounce which may trigger API search
        const nameInput = page.locator('input[wire\\:model\\.live\\.debounce\\.400ms="name"]');
        await nameInput.fill(locationName);
        await livewireWait(page, 600);

        // Close any name search dropdown by clicking elsewhere
        await page.locator('label:has-text("City")').click();
        await livewireWait(page, 200);

        // Fill city, state, country
        await page.locator('input[wire\\:model="city"]').fill('Chicago');
        await page.locator('input[wire\\:model="state"]').fill('Illinois');
        await page.locator('input[wire\\:model="country"]').fill('United States');

        // Fill website
        await page.locator('input[wire\\:model="website"]').fill('https://example.com');

        // Save
        await page.getByRole('button', { name: 'Save' }).click();
        await livewireWait(page, 1500);

        // Verify view mode — name in h1
        await expect(page.locator('h1')).toContainText(locationName, { timeout: 5_000 });

        // Verify city/state shows
        await expect(page.getByText('Chicago')).toBeVisible();
    });

    test('edit the location name', async ({ page }) => {
        await page.goto(locationUrl);
        await livewireWait(page, 1000);

        // Might load in edit mode or view mode — check for h1 or name input
        const h1 = page.locator('h1');
        const saveButton = page.getByRole('button', { name: 'Save' });

        // If we see the Save button, we are in edit mode already — go to view first
        if (await saveButton.isVisible()) {
            // Click Cancel to exit edit mode
            await page.getByRole('button', { name: 'Cancel' }).click();
            await livewireWait(page, 1000);
        }

        await expect(h1).toContainText(locationName, { timeout: 5_000 });

        // Click Edit button
        await page.locator('button[wire\\:click="edit"]').click();
        await livewireWait(page);

        // Change name
        const nameInput = page.locator('input[wire\\:model\\.live\\.debounce\\.400ms="name"]');
        await nameInput.fill(locationNameUpdated);
        await livewireWait(page, 600);

        // Close any name search dropdown
        await page.locator('label:has-text("City")').click();
        await livewireWait(page, 200);

        // Save
        await page.getByRole('button', { name: 'Save' }).click();
        await livewireWait(page, 1500);

        // Verify updated name
        await expect(page.locator('h1')).toContainText(locationNameUpdated, { timeout: 5_000 });
    });

    test('delete the location', async ({ page }) => {
        await page.goto(locationUrl);
        await livewireWait(page, 1000);

        // Check if in view mode or edit mode
        const saveButton = page.getByRole('button', { name: 'Save' });
        if (!(await saveButton.isVisible())) {
            // Enter edit mode to get delete button
            await page.locator('button[wire\\:click="edit"]').click();
            await livewireWait(page);
        }

        // Delete
        acceptNextDialog(page);
        await page.locator('button[wire\\:click="delete"]').click();
        await page.waitForURL(/\/locations\/breweries$/, { timeout: 10_000 });
    });
});

// ============================================================================
// 6. Location Autocomplete Full Flow
// ============================================================================
test.describe.serial('Location Autocomplete Full Flow', () => {
    test('store autocomplete on beer create form', async ({ page }) => {
        await page.goto('/beers/create');
        await expect(page.locator('h1')).toContainText('Add New Beer');

        // Enable "Add to inventory" checkbox
        await page.getByText('Add to inventory').click();
        await livewireWait(page);

        // Store autocomplete input should be visible
        const storeInput = page.locator('input[placeholder*="Total Wine"]');
        await expect(storeInput).toBeVisible();

        // Type in store search
        await storeInput.fill('Total');
        await livewireWait(page, 800);

        // If a store appears in dropdown, select it
        const storeResult = page.locator('button').filter({ hasText: /Total/ }).first();
        if (await storeResult.count() > 0 && await storeResult.isVisible()) {
            await storeResult.click();
            await livewireWait(page);

            // Verify pill shows — selected location shows with clear button
            const clearButton = page.locator('button[wire\\:click="clearLocation(\'store\')"]');
            await expect(clearButton).toBeVisible();

            // Click clear — should return to search input
            await clearButton.click();
            await livewireWait(page);
            await expect(storeInput).toBeVisible();
        }
    });

    test('venue autocomplete on beer create form', async ({ page }) => {
        await page.goto('/beers/create');

        // Enable "Check in this beer" checkbox
        await page.getByText('Check in this beer').click();
        await livewireWait(page);

        // Venue autocomplete input should be visible
        const venueInput = page.locator('input[placeholder*="Hop Lot"]');
        await expect(venueInput).toBeVisible();

        // Type in venue search
        await venueInput.fill('Home');
        await livewireWait(page, 800);

        // If a venue appears in dropdown, select it
        const venueResult = page.locator('button').filter({ hasText: /Home/ }).first();
        if (await venueResult.count() > 0 && await venueResult.isVisible()) {
            await venueResult.click();
            await livewireWait(page);

            // Verify pill shows — selected location shows with clear button
            const clearButton = page.locator('button[wire\\:click="clearLocation(\'venue\')"]');
            await expect(clearButton).toBeVisible();

            // Click clear — should return to search input
            await clearButton.click();
            await livewireWait(page);
            await expect(venueInput).toBeVisible();
        }
    });
});
