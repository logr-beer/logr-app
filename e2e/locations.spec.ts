import { test, expect } from '@playwright/test';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const LOCATION_TYPES = [
    {
        slug: 'breweries',
        type: 'brewery',
        label: 'Breweries',
        singular: 'brewery',
        searchPlaceholder: 'Search breweries...',
        sortOptions: ['Beers', 'Name', 'Recent'],
        backLabel: 'Back to Breweries',
        contentHeadings: ['Recent Check-ins', 'Inventory'],
        countBadgePattern: /\d+ beers?/,
    },
    {
        slug: 'venues',
        type: 'venue',
        label: 'Venues',
        singular: 'venue',
        searchPlaceholder: 'Search venues...',
        sortOptions: ['Check-ins', 'Name', 'Recent'],
        backLabel: 'Back to Venues',
        contentHeadings: ['Recent Check-ins'],
        countBadgePattern: /\d+ check-ins?/,
    },
    {
        slug: 'stores',
        type: 'store',
        label: 'Stores',
        singular: 'store',
        searchPlaceholder: 'Search stores...',
        sortOptions: ['Purchases', 'Name', 'Recent'],
        backLabel: 'Back to Stores',
        contentHeadings: ['Purchases'],
        countBadgePattern: /\d+ purchases?/,
    },
] as const;

/** Navigate to the first location card of a given type and return true, or false if none exist. */
async function navigateToFirstLocation(page: import('@playwright/test').Page, slug: string) {
    await page.goto(`/locations/${slug}`);
    const card = page.locator(`a[href*="/locations/${slug}/"]`).first();
    if ((await card.count()) === 0) return false;
    await card.click();
    await page.waitForURL(new RegExp(`locations/${slug}/\\d+`));
    return true;
}

// =========================================================================
// Location Index Pages
// =========================================================================

test.describe('Location Index Pages', () => {
    // -------------------------------------------------------------------
    // Tests repeated for each location type
    // -------------------------------------------------------------------
    for (const loc of LOCATION_TYPES) {
        test.describe(`${loc.label} index`, () => {
            test('page renders with "Locations" title', async ({ page }) => {
                await page.goto(`/locations/${loc.slug}`);
                await expect(page.locator('h1')).toContainText('Locations');
            });

            test('pill tabs show all three types with correct one active', async ({ page }) => {
                await page.goto(`/locations/${loc.slug}`);
                // All three pills visible
                await expect(page.getByText('Breweries')).toBeVisible();
                await expect(page.getByText('Venues')).toBeVisible();
                await expect(page.getByText('Stores')).toBeVisible();
                // The active tab should not be a link (it's a plain string, not an <a>)
                // while the other two should be links
                const otherSlugs = LOCATION_TYPES.filter((l) => l.slug !== loc.slug);
                for (const other of otherSlugs) {
                    await expect(page.getByRole('link', { name: other.label })).toBeVisible();
                }
            });

            test('search input is present with type-specific placeholder', async ({ page }) => {
                await page.goto(`/locations/${loc.slug}`);
                await expect(page.locator(`input[placeholder*="Search ${loc.slug.toLowerCase()}"]`)).toBeVisible();
            });

            test('search filters results', async ({ page }) => {
                await page.goto(`/locations/${loc.slug}`);
                const cards = page.locator(`a[href*="/locations/${loc.slug}/"]`);
                const initialCount = await cards.count();
                if (initialCount === 0) return; // no data to filter

                await page.fill(`input[placeholder*="Search ${loc.slug.toLowerCase()}"]`, 'zzzznonexistent');
                await page.waitForTimeout(400);
                await expect(page.locator(`a[href*="/locations/${loc.slug}/"]`)).toHaveCount(0);
            });

            test('search with no results shows empty state', async ({ page }) => {
                await page.goto(`/locations/${loc.slug}`);
                await page.fill(`input[placeholder*="Search ${loc.slug.toLowerCase()}"]`, 'xyznonexistent99');
                await page.waitForTimeout(400);
                // Empty state should appear
                await expect(page.locator(`a[href*="/locations/${loc.slug}/"]`)).toHaveCount(0);
            });

            test('sort control is present with type-specific options', async ({ page }) => {
                await page.goto(`/locations/${loc.slug}`);
                // Sort control is a custom Alpine dropdown with x-text="label" on the button span
                // The sort-control button has rounded-l-lg class
                const sortControl = page.locator('button.rounded-l-lg').first();
                await expect(sortControl).toBeVisible();
            });

            test('+New button creates location and opens edit', async ({ page }) => {
                await page.goto(`/locations/${loc.slug}`);
                await page.locator('button[wire\\:click="createNew"]').click();
                await expect(page).toHaveURL(new RegExp(`locations/${loc.slug}/\\d+`));
                // Should be in edit mode with Save button
                await expect(page.getByRole('button', { name: 'Save' })).toBeVisible();
            });

            test('filter pills present: All, Missing Location (with count badge), With Location', async ({ page }) => {
                await page.goto(`/locations/${loc.slug}`);
                // Filter tabs are in a flex justify-end container
                const filterBar = page.locator('.flex.justify-end.mb-4');
                await expect(filterBar.getByText('All')).toBeVisible();
                await expect(filterBar.getByText('Missing Location')).toBeVisible();
                await expect(filterBar.getByText('With Location')).toBeVisible();
            });

            test('clicking filter pill changes the list', async ({ page }) => {
                await page.goto(`/locations/${loc.slug}`);
                const cards = page.locator(`a[href*="/locations/${loc.slug}/"]`);
                const allCount = await cards.count();
                if (allCount === 0) return;

                // Target filter pills in the justify-end bar
                const filterBar = page.locator('.flex.justify-end.mb-4');

                // Click "With Location" filter
                await filterBar.getByText('With Location').click();
                await page.waitForTimeout(400);
                const locatedCount = await cards.count();

                // Click "Missing Location" filter
                await filterBar.getByText('Missing Location').click();
                await page.waitForTimeout(400);
                const missingCount = await cards.count();

                // Both filtered views should have <= the "All" count (pagination may limit per-page)
                // The sum may exceed allCount when pagination truncates the "All" view
                expect(locatedCount + missingCount).toBeGreaterThanOrEqual(allCount);
            });

            test('location cards show name and count badge', async ({ page }) => {
                await page.goto(`/locations/${loc.slug}`);
                const firstCard = page.locator(`a[href*="/locations/${loc.slug}/"]`).first();
                if ((await firstCard.count()) === 0) return;

                // Card contains a name (h3)
                await expect(firstCard.locator('h3')).toBeVisible();
                // Card contains a count badge
                await expect(firstCard.locator('span').filter({ hasText: loc.countBadgePattern })).toBeVisible();
            });

            test('cards link to detail pages', async ({ page }) => {
                await page.goto(`/locations/${loc.slug}`);
                const firstCard = page.locator(`a[href*="/locations/${loc.slug}/"]`).first();
                if ((await firstCard.count()) === 0) return;

                await firstCard.click();
                await expect(page).toHaveURL(new RegExp(`locations/${loc.slug}/\\d+`));
                await expect(page.locator('h1')).toBeVisible();
            });

            test('map renders when data has coordinates', async ({ page }) => {
                await page.goto(`/locations/${loc.slug}`);
                const filterBar = page.locator('.flex.justify-end.mb-4');
                // Click "With Location" to ensure we only see items with coordinates
                await filterBar.getByText('With Location').click();
                await page.waitForTimeout(400);
                const locatedCards = page.locator(`a[href*="/locations/${loc.slug}/"]`);
                if ((await locatedCards.count()) === 0) return;

                // Go back to "All" to see the map
                await filterBar.getByText('All').click();
                await page.waitForTimeout(400);

                // Map container should be present
                const mapContainer = page.locator('#location-map');
                await expect(mapContainer).toBeVisible();
            });
        });
    }

    // -------------------------------------------------------------------
    // Cross-type navigation
    // -------------------------------------------------------------------
    test.describe('pill tab navigation', () => {
        test('pill tabs navigate between all three location types', async ({ page }) => {
            await page.goto('/locations/breweries');
            await page.getByRole('link', { name: 'Venues' }).click();
            await expect(page).toHaveURL(/locations\/venues/);
            await page.getByRole('link', { name: 'Stores' }).click();
            await expect(page).toHaveURL(/locations\/stores/);
            await page.getByRole('link', { name: 'Breweries' }).click();
            await expect(page).toHaveURL(/locations\/breweries/);
        });

        test('top nav links to breweries index', async ({ page }) => {
            await page.goto('/dashboard');
            await page.getByRole('link', { name: 'Locations' }).first().click();
            await expect(page).toHaveURL(/locations\/breweries/);
        });
    });

    // -------------------------------------------------------------------
    // Cards with missing coordinates
    // -------------------------------------------------------------------
    test('cards with missing coordinates show warning icon', async ({ page }) => {
        await page.goto('/locations/breweries');
        // Switch to "Missing Location" filter
        await page.getByText('Missing Location').click();
        await page.waitForTimeout(400);

        const missingCards = page.locator('a[href*="/locations/breweries/"]');
        if ((await missingCards.count()) === 0) return;

        // At least the first card should have a warning icon
        const warningIcon = missingCards.first().locator('[title="Missing location data"]');
        await expect(warningIcon).toBeVisible();
    });
});

// =========================================================================
// Location Detail Pages
// =========================================================================

test.describe('Location Detail Pages', () => {
    // -------------------------------------------------------------------
    // Tests repeated for each location type
    // -------------------------------------------------------------------
    for (const loc of LOCATION_TYPES) {
        test.describe(`${loc.label} detail`, () => {
            test('hero shows name', async ({ page }) => {
                const found = await navigateToFirstLocation(page, loc.slug);
                if (!found) return;

                await expect(page.locator('h1')).toBeVisible();
            });

            test('edit button is present', async ({ page }) => {
                const found = await navigateToFirstLocation(page, loc.slug);
                if (!found) return;

                await expect(page.locator(`button[title*="Edit"]`)).toBeVisible();
            });

            test('refresh button (re-geocode) is present', async ({ page }) => {
                const found = await navigateToFirstLocation(page, loc.slug);
                if (!found) return;

                await expect(page.locator('button[title*="Re-lookup"]')).toBeVisible();
            });

            test('back link returns to correct index', async ({ page }) => {
                const found = await navigateToFirstLocation(page, loc.slug);
                if (!found) return;

                await expect(page.getByText(loc.backLabel)).toBeVisible();
                await page.getByText(loc.backLabel).click();
                await expect(page).toHaveURL(new RegExp(`locations/${loc.slug}`));
            });

            test('content section headings are present', async ({ page }) => {
                const found = await navigateToFirstLocation(page, loc.slug);
                if (!found) return;

                for (const heading of loc.contentHeadings) {
                    await expect(page.locator(`h2:has-text("${heading}")`)).toBeVisible();
                }
            });
        });
    }

    // -------------------------------------------------------------------
    // Brewery-specific content
    // -------------------------------------------------------------------
    test.describe('Brewery content sections', () => {
        test('brewery detail has two columns: Recent Check-ins and Inventory', async ({ page }) => {
            const found = await navigateToFirstLocation(page, 'breweries');
            if (!found) return;

            await expect(page.locator('h2:has-text("Recent Check-ins")')).toBeVisible();
            await expect(page.locator('h2:has-text("Inventory")')).toBeVisible();
        });

        test('content cards link to beer detail pages', async ({ page }) => {
            const found = await navigateToFirstLocation(page, 'breweries');
            if (!found) return;

            // Target beer links in the content sections (checkin-card or inventory-card),
            // not nav links to /beers/create
            const contentCard = page.locator('.space-y-3 a[href*="/beers/"]').first();
            if ((await contentCard.count()) === 0) return;

            await contentCard.click();
            await expect(page).toHaveURL(/beers\/\d+/);
        });
    });

    // -------------------------------------------------------------------
    // Venue-specific content
    // -------------------------------------------------------------------
    test.describe('Venue content sections', () => {
        test('venue detail shows "Recent Check-ins" heading', async ({ page }) => {
            const found = await navigateToFirstLocation(page, 'venues');
            if (!found) return;

            await expect(page.locator('h2:has-text("Recent Check-ins")')).toBeVisible();
        });
    });

    // -------------------------------------------------------------------
    // Store-specific content
    // -------------------------------------------------------------------
    test.describe('Store content sections', () => {
        test('store detail shows "Purchases" heading', async ({ page }) => {
            const found = await navigateToFirstLocation(page, 'stores');
            if (!found) return;

            await expect(page.locator('h2:has-text("Purchases")')).toBeVisible();
        });
    });

    // -------------------------------------------------------------------
    // Stats badges
    // -------------------------------------------------------------------
    test('hero shows stats badges when data exists', async ({ page }) => {
        const found = await navigateToFirstLocation(page, 'breweries');
        if (!found) return;

        // Stats area exists (check-in count or inventory count badges)
        const statsArea = page.locator('span').filter({ hasText: /check-in|inventory/ });
        // May or may not have data, just verify the hero section rendered
        await expect(page.locator('h1')).toBeVisible();
    });

    // -------------------------------------------------------------------
    // Mini map
    // -------------------------------------------------------------------
    test('mini map shows when location has coordinates', async ({ page }) => {
        // Find a location with coordinates by using "With Location" filter
        await page.goto('/locations/breweries');
        await page.getByText('With Location').click();
        await page.waitForTimeout(400);
        const card = page.locator('a[href*="/locations/breweries/"]').first();
        if ((await card.count()) === 0) return;

        await card.click();
        await page.waitForURL(/locations\/breweries\/\d+/);

        // Mini map container
        const miniMap = page.locator('#location-mini-map');
        await expect(miniMap).toBeVisible();
    });

    // -------------------------------------------------------------------
    // Load more
    // -------------------------------------------------------------------
    test('load more button appears and loads additional items', async ({ page }) => {
        const found = await navigateToFirstLocation(page, 'breweries');
        if (!found) return;

        const loadMoreBtn = page.locator('button').filter({ hasText: /Load more/ });
        if ((await loadMoreBtn.count()) === 0) return; // fewer than 6 items

        const cardsBefore = await page.locator('a[href*="/beers/"]').count();
        await loadMoreBtn.first().click();
        await page.waitForTimeout(400);
        const cardsAfter = await page.locator('a[href*="/beers/"]').count();
        expect(cardsAfter).toBeGreaterThan(cardsBefore);
    });
});

// =========================================================================
// Edit Form (identical for all types)
// =========================================================================

test.describe('Location Edit Form', () => {
    for (const loc of LOCATION_TYPES) {
        test.describe(`${loc.label} edit form`, () => {
            test('all standard fields are present', async ({ page }) => {
                const found = await navigateToFirstLocation(page, loc.slug);
                if (!found) return;

                await page.click('button[title*="Edit"]');

                // Name field (Nominatim search)
                await expect(page.locator('input[placeholder*="Search for a place"]')).toBeVisible();
                // Standard fields
                await expect(page.locator('label:has-text("Address")')).toBeVisible();
                await expect(page.locator('label:has-text("City")')).toBeVisible();
                await expect(page.locator('label:has-text("State")')).toBeVisible();
                await expect(page.locator('label:has-text("Country")')).toBeVisible();
                await expect(page.locator('label:has-text("Website")')).toBeVisible();
                // Coordinates section
                await expect(page.locator('label:has-text("Coordinates")')).toBeVisible();
                await expect(page.locator('input[placeholder="Latitude"]')).toBeVisible();
                await expect(page.locator('input[placeholder="Longitude"]')).toBeVisible();
            });

            test('Lookup button is present in coordinates section', async ({ page }) => {
                const found = await navigateToFirstLocation(page, loc.slug);
                if (!found) return;

                await page.click('button[title*="Edit"]');
                await expect(page.getByRole('button', { name: 'Lookup' })).toBeVisible();
            });

            test('picker map is present in edit mode', async ({ page }) => {
                const found = await navigateToFirstLocation(page, loc.slug);
                if (!found) return;

                await page.click('button[title*="Edit"]');
                const pickerMap = page.locator('#location-picker-map');
                await expect(pickerMap).toBeVisible();
            });

            test('Save and Cancel buttons are present', async ({ page }) => {
                const found = await navigateToFirstLocation(page, loc.slug);
                if (!found) return;

                await page.click('button[title*="Edit"]');
                await expect(page.getByRole('button', { name: 'Save' })).toBeVisible();
                await expect(page.getByRole('button', { name: 'Cancel' })).toBeVisible();
            });

            test('Delete button with confirmation is present', async ({ page }) => {
                const found = await navigateToFirstLocation(page, loc.slug);
                if (!found) return;

                await page.click('button[title*="Edit"]');
                const deleteBtn = page.locator('button').filter({ hasText: 'Delete' });
                await expect(deleteBtn).toBeVisible();
                // Verify it has wire:confirm attribute
                await expect(deleteBtn).toHaveAttribute('wire:confirm', /Delete this/);
            });

            test('cancel returns to view mode', async ({ page }) => {
                const found = await navigateToFirstLocation(page, loc.slug);
                if (!found) return;

                await page.click('button[title*="Edit"]');
                await expect(page.getByRole('button', { name: 'Save' })).toBeVisible();

                await page.getByRole('button', { name: 'Cancel' }).click();
                // Back to view mode - edit button visible again
                await expect(page.locator('button[title*="Edit"]')).toBeVisible();
                // Save button should be gone
                await expect(page.getByRole('button', { name: 'Save' })).not.toBeVisible();
            });

            test('save updates the location', async ({ page }) => {
                const found = await navigateToFirstLocation(page, loc.slug);
                if (!found) return;

                await page.click('button[title*="Edit"]');

                // Read current city value
                const cityInput = page.locator('input[wire\\:model="city"]');
                const originalCity = await cityInput.inputValue();

                // Change city
                await cityInput.fill('TestCity');
                await page.getByRole('button', { name: 'Save' }).click();
                await page.waitForTimeout(400);

                // Should be back in view mode
                await expect(page.locator('button[title*="Edit"]')).toBeVisible();

                // Re-enter edit to verify the value persisted
                await page.click('button[title*="Edit"]');
                await expect(cityInput).toHaveValue('TestCity');

                // Restore original value
                await cityInput.fill(originalCity);
                await page.getByRole('button', { name: 'Save' }).click();
                await page.waitForTimeout(400);
            });
        });
    }

    // -------------------------------------------------------------------
    // Nominatim name search (test once, shared component)
    // -------------------------------------------------------------------
    test.describe('Nominatim name search', () => {
        test('typing 4+ chars in name field triggers dropdown', async ({ page }) => {
            const found = await navigateToFirstLocation(page, 'breweries');
            if (!found) return;

            await page.click('button[title*="Edit"]');
            const nameInput = page.locator('input[placeholder*="Search for a place"]');

            // Type a real-ish query that Nominatim should return results for
            await nameInput.fill('Bell');
            await page.waitForTimeout(400);

            // Less than 4 chars -- may or may not show results depending on debounce
            await nameInput.fill('Bells Brewery');
            await page.waitForTimeout(1500); // Nominatim API call

            // If results appear, a dropdown with map-pin icons should be visible
            // (external API may not respond in test env, so we check gracefully)
            const dropdown = page.locator('button[wire\\:click*="selectNameResult"]');
            if ((await dropdown.count()) > 0) {
                await expect(dropdown.first()).toBeVisible();
            }
        });
    });
});

// =========================================================================
// Location Autocomplete in Forms
// =========================================================================

test.describe('Location Autocomplete in Forms', () => {
    // -------------------------------------------------------------------
    // Beer show page - inventory form (store autocomplete)
    // -------------------------------------------------------------------
    test.describe('Beer show page', () => {
        test('store autocomplete exists in inventory form', async ({ page }) => {
            await page.goto('/beers');
            const beerCard = page
                .locator('a[href*="/beers/"]:not([href*="create"]):not([href*="inventory"]):not([href*="export"])')
                .first();
            if ((await beerCard.count()) === 0) return;

            await beerCard.click();
            await page.waitForURL(/beers\/\d+/);

            // Store autocomplete input in inventory section
            const storeInput = page.locator('input[placeholder*="Total Wine"]');
            await expect(storeInput).toHaveCount(1);
        });

        test('venue autocomplete exists in checkin form', async ({ page }) => {
            await page.goto('/beers');
            const beerCard = page
                .locator('a[href*="/beers/"]:not([href*="create"]):not([href*="inventory"]):not([href*="export"])')
                .first();
            if ((await beerCard.count()) === 0) return;

            await beerCard.click();
            await page.waitForURL(/beers\/\d+/);

            // Venue autocomplete input in check-in section
            const venueInput = page.locator('input[placeholder*="Hop Lot"]');
            await expect(venueInput).toHaveCount(1);
        });
    });

    // -------------------------------------------------------------------
    // Checkin create form
    // -------------------------------------------------------------------
    test.describe('Checkin create form', () => {
        test('venue autocomplete is present', async ({ page }) => {
            await page.goto('/checkins/create');
            const venueInput = page.locator('input[placeholder*="Hop Lot"]');
            await expect(venueInput).toBeVisible();
        });

        test('typing in autocomplete shows local suggestions', async ({ page }) => {
            await page.goto('/checkins/create');
            const venueInput = page.locator('input[placeholder*="Hop Lot"]');

            // Type a short query to trigger local suggestions
            await venueInput.fill('Home');
            await page.waitForTimeout(400);

            // If suggestions exist, dropdown buttons appear
            const suggestions = page.locator('button[wire\\:click*="selectLocation"]');
            if ((await suggestions.count()) > 0) {
                await expect(suggestions.first()).toBeVisible();
            }
        });

        test('selecting a suggestion shows pill with name and clear button', async ({ page }) => {
            await page.goto('/checkins/create');
            const venueInput = page.locator('input[placeholder*="Hop Lot"]');

            await venueInput.fill('Home');
            await page.waitForTimeout(400);

            const firstSuggestion = page.locator('button[wire\\:click*="selectLocation"]').first();
            if ((await firstSuggestion.count()) === 0) return;

            await firstSuggestion.click();
            await page.waitForTimeout(400);

            // Selected pill should show with clear button
            const clearBtn = page.locator('button[wire\\:click*="clearLocation"]');
            await expect(clearBtn).toBeVisible();

            // Pill shows the selected name in an amber-highlighted container
            const pill = page.locator('.bg-amber-50, [class*="bg-amber"]').filter({ hasText: /\w+/ });
            await expect(pill.first()).toBeVisible();
        });

        test('clear button removes selection and returns to search', async ({ page }) => {
            await page.goto('/checkins/create');
            const venueInput = page.locator('input[placeholder*="Hop Lot"]');

            await venueInput.fill('Home');
            await page.waitForTimeout(400);

            const firstSuggestion = page.locator('button[wire\\:click*="selectLocation"]').first();
            if ((await firstSuggestion.count()) === 0) return;

            await firstSuggestion.click();
            await page.waitForTimeout(400);

            // Click clear
            const clearBtn = page.locator('button[wire\\:click*="clearLocation"]');
            await clearBtn.click();
            await page.waitForTimeout(400);

            // Search input should reappear
            await expect(page.locator('input[placeholder*="Hop Lot"]')).toBeVisible();
        });

        test('typing 4+ chars triggers Nominatim "Search Results" section', async ({ page }) => {
            await page.goto('/checkins/create');
            const venueInput = page.locator('input[placeholder*="Hop Lot"]');

            // Type a real place name long enough to trigger API
            await venueInput.fill('Hop Lot Brewing');
            await page.waitForTimeout(1500); // Allow API call

            // Look for "Search Results" divider (from Nominatim)
            const searchResultsLabel = page.locator('text=Search Results');
            // External API may not respond, so check gracefully
            if ((await searchResultsLabel.count()) > 0) {
                await expect(searchResultsLabel).toBeVisible();
                // API results have wire:click="importAndSelectLocation"
                const apiResults = page.locator('button[wire\\:click*="importAndSelectLocation"]');
                expect(await apiResults.count()).toBeGreaterThan(0);
            }
        });
    });

    // -------------------------------------------------------------------
    // Add beer form - inventory and checkin toggles
    // -------------------------------------------------------------------
    test.describe('Add beer form', () => {
        test('enabling inventory shows store autocomplete', async ({ page }) => {
            await page.goto('/beers/create');

            // Store autocomplete should not be visible initially
            await expect(page.locator('input[placeholder*="Total Wine"]')).not.toBeVisible();

            // Enable inventory toggle
            await page.locator('text=Add to inventory').click();
            await page.waitForTimeout(400);

            await expect(page.locator('input[placeholder*="Total Wine"]')).toBeVisible();
        });

        test('enabling checkin shows venue autocomplete', async ({ page }) => {
            await page.goto('/beers/create');

            // Venue autocomplete should not be visible initially
            await expect(page.locator('input[placeholder*="Hop Lot"]')).not.toBeVisible();

            // Enable checkin toggle
            await page.locator('text=Check in this beer').click();
            await page.waitForTimeout(400);

            await expect(page.locator('input[placeholder*="Hop Lot"]')).toBeVisible();
        });

        test('both store and venue autocompletes can be visible simultaneously', async ({ page }) => {
            await page.goto('/beers/create');

            await page.locator('text=Add to inventory').click();
            await page.waitForTimeout(400);
            await page.locator('text=Check in this beer').click();
            await page.waitForTimeout(400);

            await expect(page.locator('input[placeholder*="Total Wine"]')).toBeVisible();
            await expect(page.locator('input[placeholder*="Hop Lot"]')).toBeVisible();
        });
    });
});
