# Logr

A self-hosted beer logging app. Track your beer library, check-ins, inventory, collections, and more.

Built with Laravel 12, Livewire 3, and Tailwind CSS.

---

## Features

### Beer Library
Add and manage beers with rich detail -- brewery, style, ABV, IBU, release year, description, and photos. Search across multiple external databases to quickly populate entries.

### Check-ins
Log tastings with ratings (0-5, half-star support), serving type, venue, companions, notes, and photos. Each check-in becomes part of your personal beer history.

### Inventory
Track what's in your fridge, cellar, or any custom storage location. Record purchase details, quantities, and mark gifts.

### Collections
Organize beers into custom lists. Create **static** collections by hand or **dynamic** collections using rule-based filters (by year, style, rating, favorites, storage location, and more).

### Venues
Associate check-ins with specific locations. Venues support coordinates for mapping and can be enriched from Untappd.

### Rankings & Statistics
View your top-rated beers, check-in trends, and personal tasting stats at a glance.

### CSV Import
Bulk import beers and check-ins from CSV files. Supports Untappd export format out of the box.

### Tags & Companions
Apply custom color-coded tags to beers and check-ins. Track who you shared a beer with via companions.

### Integrations
- **Untappd** -- Search beers, import profiles, sync RSS feeds, and enrich beer/venue data
- **Discord** -- Share check-ins and purchases to Discord channels via webhooks or bots
- **Logr Hub** -- OAuth-based connection to the Logr Hub community
- **Logr DB** -- Search and import from the Logr Database
- **Catalog.beer** -- Search and import from the Catalog.beer API
- **Open Brewery DB** -- Brewery lookup and enrichment
- **Geocoding** -- Optional auto-geocoding for brewery and venue locations

### Demo Mode
Enable `DEMO_MODE=true` to run Logr as a showcase instance with scheduled data resets.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.4 / Laravel 12 |
| Frontend | Livewire 3 + Volt, Tailwind CSS, Alpine.js |
| Database | SQLite |
| Build | Vite |
| Queue | Laravel Queue (database driver) |
| Server | Nginx + PHP-FPM + Supervisor |

---

## Quick Start with Docker Compose

The fastest way to run Logr. No PHP, Node, or database setup needed.

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) and [Docker Compose](https://docs.docker.com/compose/install/)

### docker-compose.yml

Create a `docker-compose.yml` file:

```yaml
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

### Start the app

```bash
docker compose up -d
```

The app will be available at **http://localhost:8337**.

### What happens on first boot

The entrypoint script automatically:
1. Generates an `APP_KEY` if one isn't set
2. Creates the storage symlink
3. Runs database migrations
4. Seeds default data

### Configuration

Optionally create a `.env` file next to `docker-compose.yml` to customize settings:

```env
# App settings (all optional -- sensible defaults are provided)
APP_URL=http://localhost:8337

# Integrations
CATALOG_BEER_API_KEY=
LOGR_DB_URL=
LOGR_DISCORD_URL=

# Set to true to enable demo mode with scheduled data resets
DEMO_MODE=false
```

Then restart:

```bash
docker compose up -d
```

### Persistent data

All persistent state lives in a single Docker volume:

| Path in container | Purpose |
|-------------------|---------|
| `/data/database/` | SQLite database |
| `/data/storage/` | Uploaded photos and app storage |

The volume is named `logr-data` and persists across container restarts and rebuilds.

### Updating

```bash
docker compose pull
docker compose up -d
```

---

## Local Development with DDEV

[DDEV](https://ddev.com) provides a consistent, containerized development environment with PHP, Composer, Node.js, and npm out of the box. This is the recommended setup for contributing.

### Prerequisites

- [DDEV](https://ddev.com/get-started/)

### Setup

```bash
git clone https://github.com/logr-beer/logr-app.git
cd logr-app
ddev start
ddev composer setup
```

The site will be available at **https://logr.ddev.site**.

### What DDEV handles

- PHP 8.4 with all required extensions
- Node.js 20
- Nginx
- Persistent SQLite database at `/data/database/database.sqlite`
- Queue worker starts automatically on `ddev start`
- Database migrations run automatically on start

### Day-to-day development

```bash
# Start DDEV (queue worker starts automatically)
ddev start

# Run the dev server with hot reload, queue, and log tailing
ddev composer dev

# Run tests
ddev composer test

# SSH into the container
ddev ssh

# Stop DDEV
ddev stop
```

### Composer scripts

| Command | Description |
|---------|-------------|
| `composer setup` | Full install: dependencies, `.env`, key generation, migrations, asset build |
| `composer dev` | Start dev server, queue worker, log tailing, and Vite in parallel |
| `composer test` | Clear config cache and run PHPUnit tests |

---

## Local Development without DDEV

If you prefer a manual setup:

### Prerequisites

- PHP 8.2+ with extensions: pdo_sqlite, mbstring, exif, pcntl, bcmath, gd
- Composer
- Node.js 20+
- npm

### Setup

```bash
git clone https://github.com/logr-beer/logr-app.git
cd logr-app

composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build

# Start the development server
composer dev
```

This runs four processes concurrently:
- `php artisan serve` -- Laravel dev server
- `php artisan queue:listen` -- Queue worker
- `php artisan pail` -- Real-time log tailing
- `npm run dev` -- Vite with hot module replacement

### Running tests

```bash
composer test
```

---

## Environment Variables

Copy `.env.example` to `.env` and configure as needed. Most variables have sensible defaults.

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_NAME` | Application name | `Logr` |
| `APP_URL` | Base URL for the app | `http://localhost` |
| `DB_CONNECTION` | Database driver | `sqlite` |
| `QUEUE_CONNECTION` | Queue driver | `database` |
| `CATALOG_BEER_API_KEY` | API key for Catalog.beer service | -- |
| `LOGR_DB_URL` | URL for the Logr DB API | -- |
| `LOGR_HUB_URL` | URL for the Logr Hub (OAuth) | -- |
| `LOGR_DISCORD_URL` | URL for the Logr Discord bot | -- |
| `DEMO_MODE` | Enable demo mode with scheduled data resets | `false` |

Additional integration credentials (Untappd, Discord webhooks, geocoding) are configured per-user through the in-app settings UI.

---

## Releases

This project uses [semantic versioning](https://semver.org/) and [conventional commits](https://www.conventionalcommits.org/).

To create a release:

```bash
git tag -a v1.0.0 -m "v1.0.0 - Initial release"
git push origin v1.0.0
```

Pushing a `v*` tag triggers the release workflow which:

1. Builds the Docker image
2. Pushes it to GitHub Container Registry (`ghcr.io/logr-beer/logr-app`)
3. Creates a GitHub Release with auto-generated release notes

---

## Project Structure

```
app/
  Models/         Beer, Checkin, Brewery, Collection, Inventory, Venue, Tag, Companion...
  Livewire/       Page components (BeerIndex, CheckinForm, Dashboard, Rankings...)
  Services/       External integrations (Untappd, Discord, LogrDb, CatalogBeer...)
  Jobs/           Background jobs (geocoding, scraping, RSS sync)
  Console/        Artisan commands (user management, data enrichment, demo reset)

resources/
  views/          Blade templates and Livewire component views

database/
  migrations/     Schema definitions
  seeders/        Database seeders (default data, demo data)

docker/           Docker config (entrypoint, nginx, supervisord)
.ddev/            DDEV configuration
tests/            PHPUnit tests
```

---

## Contributors

<!-- Add your name here when you contribute! -->

| Name | Github | Role |
|------|--------|------|
| AJ Penninga | https://github.com/ajp | Creator & maintainer |

### Contributing

Contributions are welcome! To get started:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-feature`)
3. Set up local development with [DDEV](#local-development-with-ddev) or [Docker](#local-development-without-ddev)
4. Make your changes and add tests where appropriate
5. Run the test suite (`composer test`)
6. Commit your changes using [conventional commits](https://www.conventionalcommits.org/)
7. Open a pull request against `main`

---

## License

Logr is open-source software licensed under the [GNU Affero General Public License v3.0 (AGPL-3.0)](LICENSE).

You are free to fork, modify, and self-host Logr. If you distribute a modified version or run it as a network service, you must make your source code available under the same license.
