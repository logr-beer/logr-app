# Logr

[![Docker Pulls](https://img.shields.io/docker/pulls/ajpenninga/logr-app)](https://hub.docker.com/r/ajpenninga/logr-app)

A self-hosted beer logging app. Track your beer library, check-ins, inventory, collections, and more.

Built with Laravel 12, Livewire 3, and Tailwind CSS.

---

## Features

- **Beer Library** -- Add beers with brewery, style, ABV, IBU, photos. Search Untappd, Catalog.beer, and Logr DB to populate entries.
- **Check-ins** -- Log tastings with ratings (0-5), serving type, venue, companions, notes, and photos.
- **Inventory** -- Track what's in your fridge or cellar with quantities, storage locations, and purchase details.
- **Collections** -- Static beer lists or dynamic collections using rule-based filters (year, style, rating, favorites, etc.).
- **Venues & Locations** -- Associate check-ins with venues. Auto-geocode breweries and venues for map views.
- **Rankings & Stats** -- Top-rated beers, check-in trends, and personal tasting stats.
- **CSV Import/Export** -- Bulk import from Untappd format. Export beers and check-ins.
- **Tags & Companions** -- Color-coded tags and companion tracking for check-ins.
- **Integrations** -- Untappd (search, scrape, RSS sync), Discord (webhooks + bot), Logr Hub, Logr DB, Catalog.beer, Open Brewery DB.
- **Demo Mode** -- `DEMO_MODE=true` runs a showcase instance with scheduled data resets and read-only settings.

---

## Quick Start

```yaml
# docker-compose.yml
services:
  app:
    image: ajpenninga/logr-app:latest
    ports:
      - "8337:8337"
    volumes:
      - logr-data:/data
    restart: unless-stopped

volumes:
  logr-data:
```

```bash
docker compose up -d
```

Open **http://localhost:8337**. The app auto-generates an `APP_KEY`, runs migrations, and seeds default data on first boot.

### Configuration

Create a `.env` file next to `docker-compose.yml`. All variables are optional — the app works out of the box with sensible defaults.

```env
APP_URL=http://localhost:8337

# Beer database integrations
CATALOG_BEER_API_KEY=          # catalog.beer API key for beer search
UNTAPPD_API_KEY=               # Untappd API client ID
UNTAPPD_API_SECRET=            # Untappd API client secret

# Discord bot (via pub.logr.beer)
LOGR_DISCORD_BOT_URL=          # Logr Discord bot URL (default: https://pub.logr.beer)
LOGR_PUB_URL=                  # Pub API URL for anonymous stats (default: https://pub.logr.beer)

# Analytics & modes
GOOGLE_ANALYTICS_ID=           # GA4 measurement ID (optional, no tracking if unset)
DEMO_MODE=false                # Enable demo mode with scheduled resets and read-only settings
```

Per-user settings (Untappd credentials, Discord webhooks, geocoding) can also be configured through the in-app settings page.

### Updating

```bash
docker compose pull && docker compose up -d
```

### Persistent Data

All state lives in a single Docker volume (`logr-data`): SQLite database at `/data/database/` and uploaded photos at `/data/storage/`.

---

## Development

Requires PHP 8.4, Composer, Node.js 20+ and npm. The app uses SQLite at `database/database.sqlite`; no other services are needed.

```bash
git clone https://github.com/logr-beer/logr-app.git && cd logr-app
composer setup                                # Install deps, create .env, generate key, migrate, build assets
php artisan db:seed --class=DemoSeeder        # Optional: demo account (demo / password) + sample data
composer dev                                  # Dev server (http://127.0.0.1:8000), queue, log tailing, Vite HMR
composer test                                 # PHPUnit
```

`.env.example` defaults to `SESSION_SECURE_COOKIE=false` so you can log in over plain `http://`. Set it to `true` when serving over HTTPS.

### E2E tests (Playwright)

The specs in `e2e/` log in as the demo account (`demo` / `password`), so seed it first with `php artisan db:seed --class=DemoSeeder`. The base URL comes from `PLAYWRIGHT_BASE_URL`, then `APP_URL`, then defaults to `https://logr.ddev.site`.

```bash
npm run test:e2e:install                                  # Once: install Chromium
PLAYWRIGHT_BASE_URL=http://127.0.0.1:8000 npm run test:e2e
composer test:all                                         # PHPUnit + Playwright
```

### With DDEV (optional)

[DDEV](https://ddev.com) works too. Prefix the same commands with `ddev` (`ddev composer ...`, `ddev artisan ...`, `ddev npm ...`):

```bash
ddev start
ddev composer setup
ddev artisan db:seed --class=DemoSeeder
ddev composer dev        # Dev server + queue + logs + Vite HMR
ddev composer test
```

Site available at **https://logr.ddev.site** (the Playwright default). Under DDEV the SQLite file is symlinked to a shared `/data` volume, and `SESSION_SECURE_COOKIE=true` is fine since the site is HTTPS.

---

## Releases

Pushing a `v*` tag triggers the release workflow which builds and pushes Docker images to [Docker Hub](https://hub.docker.com/r/ajpenninga/logr-app) and [GHCR](https://github.com/logr-beer/logr-app/pkgs/container/logr-app), then creates a GitHub Release.

---

## License

Logr is open-source software licensed under the [GNU Affero General Public License v3.0 (AGPL-3.0)](LICENSE).

---

<details>
<summary>&uarr; &uarr; &darr; &darr; &larr; &rarr; &larr; &rarr; B A</summary>

You found it. Rawrrrr.
</details>
