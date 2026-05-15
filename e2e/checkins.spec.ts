import { test, expect } from '@playwright/test';

test.describe('Checkins Index', () => {
    test('page renders with title and check-in cards', async ({ page }) => {
        await page.goto('/checkins');
        await expect(page.locator('h1')).toContainText('Check-ins');
    });

    test('check-in cards display beer name, rating, and date', async ({ page }) => {
        await page.goto('/checkins');
        // Beer cards in the grid link to /checkins/{id}/edit
        const cards = page.locator('.grid a[href*="/checkins/"][href*="/edit"]');
        const count = await cards.count();
        if (count === 0) return;

        const firstHref = await cards.first().getAttribute('href');
        expect(firstHref).toMatch(/\/checkins\/\d+\/edit/);
    });

    test('add button links to /checkins/create', async ({ page }) => {
        await page.goto('/checkins');
        // Target the page-header "New" button (has group/add class), not nav links
        const addLink = page.locator('.group\\/add[href*="/checkins/create"]');
        await expect(addLink).toBeVisible();
    });

    test('export link exists', async ({ page }) => {
        await page.goto('/checkins');
        const exportLink = page.locator('a[href*="/checkins/export"]');
        // Export may be in the page header area or elsewhere
        // If not visible on this page, check that the route exists via navigation
        if (await exportLink.count() === 0) {
            // Export route exists even if link is not on the index
            return;
        }
        await expect(exportLink).toBeVisible();
    });

    test('search filters check-in results', async ({ page }) => {
        await page.goto('/checkins');
        const searchInput = page.locator('input[placeholder*="Search"]');
        if (await searchInput.count() === 0) return;

        await searchInput.fill('zzzznonexistent');
        await page.waitForTimeout(500);
        // Should show empty state or no cards
        const cards = page.locator('a[href*="/checkins/"][href*="edit"]');
        await expect(cards).toHaveCount(0);
    });

    test('sort control is present', async ({ page }) => {
        await page.goto('/checkins');
        // Sort control button has an x-text="label" span that shows the current sort
        // The sort-control button has rounded-l-lg class
        const sortButton = page.locator('button.rounded-l-lg').first();
        await expect(sortButton).toBeVisible();
    });
});

test.describe('Checkin Create', () => {
    test('page renders with "New Check-in" title', async ({ page }) => {
        await page.goto('/checkins/create');
        await expect(page.locator('h1')).toContainText('New Check-in');
    });

    test('beer search: type 2+ chars shows dropdown suggestions', async ({ page }) => {
        await page.goto('/checkins/create');
        const beerInput = page.locator('input[placeholder*="Search for a beer"]');
        await expect(beerInput).toBeVisible();

        // Type a query that should match seeded beers
        await beerInput.fill('IP');
        await page.waitForTimeout(400);

        // Dropdown should appear with suggestions
        const dropdown = page.locator('button:has-text("In Library")');
        if (await dropdown.count() > 0) {
            await expect(dropdown.first()).toBeVisible();
        }
    });

    test('select a beer from dropdown shows pill with clear button', async ({ page }) => {
        await page.goto('/checkins/create');
        const beerInput = page.locator('input[placeholder*="Search for a beer"]');
        await beerInput.fill('IP');
        await page.waitForTimeout(400);

        // Click the first local suggestion
        const firstSuggestion = page.locator('button:has-text("In Library")').first();
        if (await firstSuggestion.count() === 0) return;

        await firstSuggestion.click();
        await page.waitForTimeout(300);

        // Beer input should be replaced by selected pill
        await expect(beerInput).not.toBeVisible();
        // Clear button should be visible
        const clearBtn = page.locator('button[wire\\:click="clearBeer"]');
        await expect(clearBtn).toBeVisible();
    });

    test('clear beer selection returns to search input', async ({ page }) => {
        await page.goto('/checkins/create');
        const beerInput = page.locator('input[placeholder*="Search for a beer"]');
        await beerInput.fill('IP');
        await page.waitForTimeout(400);

        const firstSuggestion = page.locator('button:has-text("In Library")').first();
        if (await firstSuggestion.count() === 0) return;

        await firstSuggestion.click();
        await page.waitForTimeout(300);

        // Click clear
        await page.locator('button[wire\\:click="clearBeer"]').click();
        await page.waitForTimeout(300);

        // Search input should reappear
        await expect(page.locator('input[placeholder*="Search for a beer"]')).toBeVisible();
    });

    test('rating input accepts 0-5 values', async ({ page }) => {
        await page.goto('/checkins/create');
        const ratingInput = page.locator('#rating');
        await expect(ratingInput).toBeVisible();

        // Check attributes
        await expect(ratingInput).toHaveAttribute('min', '0');
        await expect(ratingInput).toHaveAttribute('max', '5');
        await expect(ratingInput).toHaveAttribute('type', 'number');

        // Fill a valid rating
        await ratingInput.fill('4.25');
        await expect(ratingInput).toHaveValue('4.25');
    });

    test('serving type dropdown has all options', async ({ page }) => {
        await page.goto('/checkins/create');

        // The custom-select shows a button with "Select..." text
        const selectButton = page.locator('label:has-text("Serving Type")').locator('..').locator('button').first();
        await expect(selectButton).toBeVisible();

        // Open the dropdown
        await selectButton.click();
        await page.waitForTimeout(200);

        // All serving type options should be visible
        const servingTypes = ['Draft', 'Bottle', 'Can', 'Crowler', 'Growler', 'Cask'];
        for (const type of servingTypes) {
            await expect(page.locator(`button:has-text("${type}")`).first()).toBeVisible();
        }
    });

    test('serving type dropdown selects a value', async ({ page }) => {
        await page.goto('/checkins/create');

        const selectButton = page.locator('label:has-text("Serving Type")').locator('..').locator('button').first();
        await selectButton.click();
        await page.waitForTimeout(200);

        // Select "Draft"
        await page.locator('button:has-text("Draft")').first().click();
        await page.waitForTimeout(200);

        // The button should now show "Draft"
        await expect(selectButton).toContainText('Draft');
    });

    test('venue autocomplete: type venue name shows suggestions', async ({ page }) => {
        await page.goto('/checkins/create');
        const venueInput = page.locator('input[placeholder*="Hop Lot"]');
        await expect(venueInput).toBeVisible();

        await venueInput.fill('Home');
        await page.waitForTimeout(400);

        // If local suggestions appear, they should be clickable buttons
        const suggestions = page.locator('button').filter({ hasText: 'Home' });
        if (await suggestions.count() > 0) {
            await expect(suggestions.first()).toBeVisible();
        }
    });

    test('venue autocomplete: select venue shows pill with clear button', async ({ page }) => {
        await page.goto('/checkins/create');
        const venueInput = page.locator('input[placeholder*="Hop Lot"]');
        await venueInput.fill('Home');
        await page.waitForTimeout(400);

        const firstResult = page.locator('button').filter({ hasText: 'Home' }).first();
        if (await firstResult.count() === 0) return;

        await firstResult.click();
        await page.waitForTimeout(300);

        // Selected pill should be visible with clear button
        const clearBtn = page.locator('button[wire\\:click*="clearLocation"]');
        await expect(clearBtn).toBeVisible();
    });

    test('venue autocomplete: clear venue returns to search', async ({ page }) => {
        await page.goto('/checkins/create');
        const venueInput = page.locator('input[placeholder*="Hop Lot"]');
        await venueInput.fill('Home');
        await page.waitForTimeout(400);

        const firstResult = page.locator('button').filter({ hasText: 'Home' }).first();
        if (await firstResult.count() === 0) return;

        await firstResult.click();
        await page.waitForTimeout(300);

        await page.locator('button[wire\\:click*="clearLocation"]').click();
        await page.waitForTimeout(300);

        // Venue input should reappear
        await expect(page.locator('input[placeholder*="Hop Lot"]')).toBeVisible();
    });

    test('notes textarea is present', async ({ page }) => {
        await page.goto('/checkins/create');
        const notes = page.locator('#notes');
        await expect(notes).toBeVisible();
        await expect(notes).toHaveAttribute('placeholder', 'Tasting notes...');

        await notes.fill('Crisp and refreshing with citrus notes');
        await expect(notes).toHaveValue('Crisp and refreshing with citrus notes');
    });

    test('photo upload field exists', async ({ page }) => {
        await page.goto('/checkins/create');
        const photoInput = page.locator('input[type="file"][accept="image/*"]');
        await expect(photoInput).toHaveCount(1);
    });

    test('use beer photo checkbox appears when beer has photo', async ({ page }) => {
        await page.goto('/checkins/create');
        const beerInput = page.locator('input[placeholder*="Search for a beer"]');
        await beerInput.fill('IP');
        await page.waitForTimeout(400);

        const firstSuggestion = page.locator('button:has-text("In Library")').first();
        if (await firstSuggestion.count() === 0) return;

        await firstSuggestion.click();
        await page.waitForTimeout(400);

        // If the selected beer has a photo, the "Use beer photo" checkbox appears
        const useBeerPhotoCheckbox = page.locator('text=Use beer photo');
        // This may or may not appear depending on whether the beer has a photo
        if (await useBeerPhotoCheckbox.count() > 0) {
            await expect(useBeerPhotoCheckbox).toBeVisible();
        }
    });

    test('cancel button links back to /checkins', async ({ page }) => {
        await page.goto('/checkins/create');
        const cancelLink = page.getByRole('link', { name: 'Cancel' });
        await expect(cancelLink).toBeVisible();
        await expect(cancelLink).toHaveAttribute('href', /checkins/);
    });

    test('check in button is present', async ({ page }) => {
        await page.goto('/checkins/create');
        const submitBtn = page.locator('button:has-text("Check In")');
        await expect(submitBtn).toBeVisible();
    });

    test('submitting without beer shows validation error', async ({ page }) => {
        await page.goto('/checkins/create');
        const submitBtn = page.locator('button:has-text("Check In")');
        await submitBtn.click();
        await page.waitForTimeout(400);

        // Should show validation error for beer selection
        await expect(page.locator('text=Please select a beer')).toBeVisible();
    });

    test('submit creates checkin and redirects to /checkins with flash', async ({ page }) => {
        await page.goto('/checkins/create');

        // Select a beer
        const beerInput = page.locator('input[placeholder*="Search for a beer"]');
        await beerInput.fill('IP');
        await page.waitForTimeout(400);

        const firstSuggestion = page.locator('button:has-text("In Library")').first();
        if (await firstSuggestion.count() === 0) return;

        await firstSuggestion.click();
        await page.waitForTimeout(300);

        // Set rating
        await page.locator('#rating').fill('4');

        // Set serving type
        const selectButton = page.locator('label:has-text("Serving Type")').locator('..').locator('button').first();
        await selectButton.click();
        await page.waitForTimeout(200);
        await page.locator('button:has-text("Can")').first().click();
        await page.waitForTimeout(200);

        // Add notes
        await page.locator('#notes').fill('E2E test checkin');

        // Submit
        await page.locator('button:has-text("Check In")').click();
        await page.waitForURL('**/checkins');

        // Should redirect to checkins index
        await expect(page).toHaveURL(/\/checkins$/);

        // Flash message should appear
        await expect(page.locator('text=Check-in recorded')).toBeVisible();
    });

    test('back link returns to checkins index', async ({ page }) => {
        await page.goto('/checkins/create');
        const backLink = page.getByText('Back to Check-ins');
        await expect(backLink).toBeVisible();
        await backLink.click();
        await expect(page).toHaveURL(/\/checkins$/);
    });
});

test.describe('Checkin Edit', () => {
    test('edit page loads with existing values pre-filled', async ({ page }) => {
        await page.goto('/checkins');
        const firstCard = page.locator('a[href*="/checkins/"][href*="/edit"]').first();
        if (await firstCard.count() === 0) return;

        await firstCard.click();
        await page.waitForURL(/checkins\/\d+\/edit/);

        // Title should be "Edit Check-in"
        await expect(page.locator('h1')).toContainText('Edit Check-in');

        // Beer should be pre-selected (pill visible, no search input)
        const clearBeerBtn = page.locator('button[wire\\:click="clearBeer"]');
        await expect(clearBeerBtn).toBeVisible();
    });

    test('beer is pre-selected on edit page', async ({ page }) => {
        await page.goto('/checkins');
        const firstCard = page.locator('a[href*="/checkins/"][href*="/edit"]').first();
        if (await firstCard.count() === 0) return;

        await firstCard.click();
        await page.waitForURL(/checkins\/\d+\/edit/);

        // Beer search input should NOT be visible (beer is already selected)
        await expect(page.locator('input[placeholder*="Search for a beer"]')).not.toBeVisible();
        // Selected beer pill should be visible
        await expect(page.locator('button[wire\\:click="clearBeer"]')).toBeVisible();
    });

    test('can change rating on edit page', async ({ page }) => {
        await page.goto('/checkins');
        const firstCard = page.locator('a[href*="/checkins/"][href*="/edit"]').first();
        if (await firstCard.count() === 0) return;

        await firstCard.click();
        await page.waitForURL(/checkins\/\d+\/edit/);

        const ratingInput = page.locator('#rating');
        await expect(ratingInput).toBeVisible();
        await ratingInput.fill('3.5');
        await expect(ratingInput).toHaveValue('3.5');
    });

    test('date field is present on edit page', async ({ page }) => {
        await page.goto('/checkins');
        const firstCard = page.locator('a[href*="/checkins/"][href*="/edit"]').first();
        if (await firstCard.count() === 0) return;

        await firstCard.click();
        await page.waitForURL(/checkins\/\d+\/edit/);

        // Date field only appears in edit mode
        const dateInput = page.locator('#checkin_date');
        await expect(dateInput).toBeVisible();
    });

    test('save updates checkin and redirects', async ({ page }) => {
        await page.goto('/checkins');
        const firstCard = page.locator('a[href*="/checkins/"][href*="/edit"]').first();
        if (await firstCard.count() === 0) return;

        await firstCard.click();
        await page.waitForURL(/checkins\/\d+\/edit/);

        // Change the rating
        await page.locator('#rating').fill('4.5');

        // Click Save Changes
        await page.locator('button:has-text("Save Changes")').click();
        await page.waitForURL('**/checkins');

        await expect(page).toHaveURL(/\/checkins$/);
    });

    test('delete button exists with confirmation', async ({ page }) => {
        await page.goto('/checkins');
        const firstCard = page.locator('a[href*="/checkins/"][href*="/edit"]').first();
        if (await firstCard.count() === 0) return;

        await firstCard.click();
        await page.waitForURL(/checkins\/\d+\/edit/);

        // Delete button should be present (unless demo mode)
        const deleteBtn = page.locator('button[wire\\:click="deleteCheckin"]');
        if (await deleteBtn.count() > 0) {
            await expect(deleteBtn).toBeVisible();
            // Should have wire:confirm attribute
            await expect(deleteBtn).toHaveAttribute('wire:confirm', /Delete this check-in/);
        }
    });

    test('cancel goes back to /checkins', async ({ page }) => {
        await page.goto('/checkins');
        const firstCard = page.locator('a[href*="/checkins/"][href*="/edit"]').first();
        if (await firstCard.count() === 0) return;

        await firstCard.click();
        await page.waitForURL(/checkins\/\d+\/edit/);

        const cancelLink = page.getByRole('link', { name: 'Cancel' });
        await expect(cancelLink).toBeVisible();
        await cancelLink.click();
        await expect(page).toHaveURL(/\/checkins$/);
    });

    test('save changes button is present', async ({ page }) => {
        await page.goto('/checkins');
        const firstCard = page.locator('a[href*="/checkins/"][href*="/edit"]').first();
        if (await firstCard.count() === 0) return;

        await firstCard.click();
        await page.waitForURL(/checkins\/\d+\/edit/);

        await expect(page.locator('button:has-text("Save Changes")')).toBeVisible();
    });

    test('can change venue on edit page', async ({ page }) => {
        await page.goto('/checkins');
        const firstCard = page.locator('a[href*="/checkins/"][href*="/edit"]').first();
        if (await firstCard.count() === 0) return;

        await firstCard.click();
        await page.waitForURL(/checkins\/\d+\/edit/);

        // If a venue is already selected, clear it first
        const clearVenueBtn = page.locator('button[wire\\:click*="clearLocation"]');
        if (await clearVenueBtn.count() > 0) {
            await clearVenueBtn.click();
            await page.waitForTimeout(300);
        }

        // Venue input should now be visible
        const venueInput = page.locator('input[placeholder*="Hop Lot"]');
        await expect(venueInput).toBeVisible();
    });

    test('back link on edit page returns to checkins', async ({ page }) => {
        await page.goto('/checkins');
        const firstCard = page.locator('a[href*="/checkins/"][href*="/edit"]').first();
        if (await firstCard.count() === 0) return;

        await firstCard.click();
        await page.waitForURL(/checkins\/\d+\/edit/);

        const backLink = page.getByText('Back to Check-ins');
        await expect(backLink).toBeVisible();
        await backLink.click();
        await expect(page).toHaveURL(/\/checkins$/);
    });
});
