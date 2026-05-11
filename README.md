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

Create a `.env` file next to `docker-compose.yml`:

```env
APP_URL=http://localhost:8337
CATALOG_BEER_API_KEY=
LOGR_DB_URL=
LOGR_DISCORD_URL=
DEMO_MODE=false
```

Additional integration credentials (Untappd, Discord webhooks, geocoding) are configured per-user through the in-app settings.

### Updating

```bash
docker compose pull && docker compose up -d
```

### Persistent Data

All state lives in a single Docker volume (`logr-data`): SQLite database at `/data/database/` and uploaded photos at `/data/storage/`.

---

## Development

### With DDEV (recommended)

```bash
git clone https://github.com/logr-beer/logr-app.git && cd logr-app
ddev start
ddev composer setup
ddev composer dev        # Dev server + queue + logs + Vite HMR
ddev composer test       # Run tests
```

Site available at **https://logr.ddev.site**.

### Without DDEV

Requires PHP 8.2+, Composer, Node.js 20+, npm.

```bash
git clone https://github.com/logr-beer/logr-app.git && cd logr-app
composer setup           # Install deps, generate key, migrate, build assets
composer dev             # Start dev server, queue, log tailing, Vite
composer test            # Run tests
```

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
