import { test, expect } from '@playwright/test';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const authFile = join(__dirname, '..', 'playwright', '.auth', 'user.json');

// ---------------------------------------------------------------------------
// Auth (unauthenticated)
// ---------------------------------------------------------------------------
test.describe('Auth', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test('unauthenticated visit to /dashboard redirects to /login', async ({ page }) => {
        await page.goto('/dashboard');
        await expect(page).toHaveURL(/\/login/);
    });

    test('login page renders with username and password fields', async ({ page }) => {
        await page.goto('/login');
        await expect(page.locator('#username')).toBeVisible();
        await expect(page.locator('#password')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Log in' })).toBeVisible();
    });

    test('login with wrong credentials shows error', async ({ page }) => {
        await page.goto('/login');
        await page.fill('#username', 'demo');
        await page.fill('#password', 'wrongpassword');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(500);
        await expect(page.locator('[class*="text-red"]')).toBeVisible();
    });

    test('login with demo/password redirects to dashboard', async ({ page }) => {
        await page.goto('/login');
        await page.fill('#username', 'demo');
        await page.fill('#password', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard');
        await expect(page).toHaveURL(/\/dashboard/);
    });
});

// ---------------------------------------------------------------------------
// Dashboard (/dashboard) — authenticated via setup project
// ---------------------------------------------------------------------------
test.describe('Dashboard', () => {
    test('page renders with stat cards', async ({ page }) => {
        await page.goto('/dashboard');
        // Stat cards are rendered as <a> elements with bg-white class inside a grid
        const statCards = page.locator('.grid a.bg-white').first();
        await expect(statCards).toBeVisible();

        // Verify labels for the four stat cards
        await expect(page.getByText('Check-ins').first()).toBeVisible();
        await expect(page.getByText('In Library')).toBeVisible();
        await expect(page.getByText('In Stock')).toBeVisible();
        await expect(page.getByText('Avg Rating')).toBeVisible();
    });

    test('stat cards are clickable links', async ({ page }) => {
        await page.goto('/dashboard');

        // Stat cards are <a> elements in the top grid
        const statsGrid = page.locator('.grid').first();

        const checkinsStat = statsGrid.locator('a').filter({ hasText: 'Check-ins' });
        await expect(checkinsStat).toBeVisible();
        await expect(checkinsStat).toHaveAttribute('href', /checkins/);

        const libraryStat = statsGrid.locator('a').filter({ hasText: 'In Library' });
        await expect(libraryStat).toBeVisible();
        await expect(libraryStat).toHaveAttribute('href', /beers/);

        const stockStat = statsGrid.locator('a').filter({ hasText: 'In Stock' });
        await expect(stockStat).toBeVisible();
        await expect(stockStat).toHaveAttribute('href', /inventory/);

        const ratingStat = statsGrid.locator('a').filter({ hasText: 'Avg Rating' });
        await expect(ratingStat).toBeVisible();
        await expect(ratingStat).toHaveAttribute('href', /stats/);
    });

    test('"Recently Added" section shows beer cards (if data exists)', async ({ page }) => {
        await page.goto('/dashboard');
        const section = page.locator('h2').filter({ hasText: 'Recently Added' });
        const sectionCount = await section.count();
        if (sectionCount === 0) return; // no data seeded

        await expect(section).toBeVisible();
        // Beer cards should be rendered in the grid following the heading
        const sectionContainer = section.locator('..');
        const beerCards = sectionContainer.locator('a[href*="/beers/"]');
        const count = await beerCards.count();
        expect(count).toBeGreaterThan(0);
    });

    test('"Recently Checked In" section shows beer cards', async ({ page }) => {
        await page.goto('/dashboard');
        const section = page.locator('h2').filter({ hasText: 'Recently Checked In' });
        const sectionCount = await section.count();
        if (sectionCount === 0) return;

        await expect(section).toBeVisible();
        const sectionContainer = section.locator('..');
        const beerCards = sectionContainer.locator('a[href*="/beers/"]');
        const count = await beerCards.count();
        expect(count).toBeGreaterThan(0);
    });

    test('"Favorites" section shows beer cards (if favorites exist)', async ({ page }) => {
        await page.goto('/dashboard');
        const section = page.locator('h2').filter({ hasText: 'Favorites' });
        const sectionCount = await section.count();
        if (sectionCount === 0) return; // no favorites

        await expect(section).toBeVisible();
        const sectionContainer = section.locator('..');
        const beerCards = sectionContainer.locator('a[href*="/beers/"]');
        const count = await beerCards.count();
        expect(count).toBeGreaterThan(0);
    });

    test('"By Year" collections section (if year collections exist)', async ({ page }) => {
        await page.goto('/dashboard');
        const section = page.locator('h2').filter({ hasText: 'By Year' });
        const sectionCount = await section.count();
        if (sectionCount === 0) return; // no year collections

        await expect(section).toBeVisible();
        const sectionContainer = section.locator('..');
        const collectionCards = sectionContainer.locator('a[href*="/collections/"]');
        const count = await collectionCards.count();
        expect(count).toBeGreaterThan(0);
    });

    test('beer cards link to beer detail pages', async ({ page }) => {
        await page.goto('/dashboard');
        // Target actual beer detail links (with numeric IDs), excluding stat cards and nav
        const beerCard = page.locator('a[href*="/beers/"]:not([href*="create"]):not([href*="inventory"]):not([href*="export"])').first();
        if (await beerCard.count() === 0) return;

        await beerCard.click();
        await expect(page).toHaveURL(/\/beers\/\d+/);
    });

    test('navigation bar has all links: Beers, Check-ins, Locations, Stats', async ({ page }) => {
        await page.goto('/dashboard');
        const nav = page.locator('nav');

        await expect(nav.getByRole('link', { name: 'Beers', exact: true })).toBeVisible();
        await expect(nav.getByRole('link', { name: 'Check-ins', exact: true })).toBeVisible();
        await expect(nav.getByRole('link', { name: 'Locations', exact: true })).toBeVisible();
        await expect(nav.getByRole('link', { name: 'Stats', exact: true })).toBeVisible();
    });
});

// ---------------------------------------------------------------------------
// Navigation — authenticated via setup project
// ---------------------------------------------------------------------------
test.describe('Navigation', () => {
    test('Beers link navigates to /beers', async ({ page }) => {
        await page.goto('/dashboard');
        await page.locator('nav').getByRole('link', { name: 'Beers', exact: true }).click();
        await expect(page).toHaveURL(/\/beers$/);
    });

    test('Check-ins link navigates to /checkins', async ({ page }) => {
        await page.goto('/dashboard');
        await page.locator('nav').getByRole('link', { name: 'Check-ins', exact: true }).click();
        await expect(page).toHaveURL(/\/checkins$/);
    });

    test('Locations link navigates to /locations/breweries', async ({ page }) => {
        await page.goto('/dashboard');
        await page.locator('nav').getByRole('link', { name: 'Locations', exact: true }).click();
        await expect(page).toHaveURL(/\/locations\/breweries/);
    });

    test('Stats link navigates to /stats', async ({ page }) => {
        await page.goto('/dashboard');
        await page.locator('nav').getByRole('link', { name: 'Stats', exact: true }).click();
        await expect(page).toHaveURL(/\/stats/);
    });

    test('mobile nav toggle works', async ({ page }) => {
        // Set mobile viewport
        await page.setViewportSize({ width: 375, height: 667 });
        await page.goto('/dashboard');

        // Desktop nav links should be hidden
        const desktopNav = page.locator('nav .hidden.sm\\:flex a').first();
        await expect(desktopNav).not.toBeVisible();

        // Hamburger button should be visible
        const hamburger = page.locator('nav button').filter({ has: page.locator('svg') }).last();
        await expect(hamburger).toBeVisible();

        // Click hamburger to open responsive menu
        await hamburger.click();
        await page.waitForTimeout(300);

        // Responsive nav links should now be visible
        const responsiveBeers = page.locator('nav').getByRole('link', { name: 'Beers', exact: true });
        await expect(responsiveBeers).toBeVisible();
        const responsiveCheckins = page.locator('nav').getByRole('link', { name: 'Check-ins', exact: true });
        await expect(responsiveCheckins).toBeVisible();
        const responsiveLocations = page.locator('nav').getByRole('link', { name: 'Locations', exact: true });
        await expect(responsiveLocations).toBeVisible();
        const responsiveStats = page.locator('nav').getByRole('link', { name: 'Stats', exact: true });
        await expect(responsiveStats).toBeVisible();
    });

    test('profile link in user dropdown', async ({ page }) => {
        await page.goto('/dashboard');

        // Open the user dropdown
        const dropdownTrigger = page.locator('nav button').filter({ hasText: /demo/i });
        await dropdownTrigger.click();
        await page.waitForTimeout(300);

        // Profile link should be visible
        const profileLink = page.getByRole('link', { name: 'Profile' });
        await expect(profileLink).toBeVisible();
        await profileLink.click();
        await expect(page).toHaveURL(/\/profile/);
    });

    test('logout works via dropdown', async ({ page }) => {
        await page.goto('/dashboard');

        // Open the user dropdown
        const dropdownTrigger = page.locator('nav button').filter({ hasText: /demo/i });
        await dropdownTrigger.click();
        await page.waitForTimeout(300);

        // Click Log Out — visible in the dropdown (first is desktop, second is mobile)
        const logoutButton = page.getByText('Log Out', { exact: true }).first();
        await logoutButton.click();

        // Should redirect to login page or home
        await expect(page).toHaveURL(/\/login|\/$/);

        // Re-login and save new session to storage state for subsequent test files
        await page.goto('/login');
        await page.fill('#username', 'demo');
        await page.fill('#password', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/dashboard');

        // Update the stored auth state with the new session cookie
        await page.context().storageState({ path: authFile });
    });
});
