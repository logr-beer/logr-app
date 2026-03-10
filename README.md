# Logr

A self-hosted beer logging app built with Laravel 12, Livewire 3, and Tailwind CSS. Track your beer library, check-ins, inventory, collections, and more.

## Features

- **Beer Library** — Add and manage beers with details like brewery, style, ABV, IBU, and photos
- **Check-ins** — Log tastings with ratings, serving type, venue, notes, and photos
- **Inventory** — Track what's in your fridge, cellar, or any storage location
- **Collections** — Organize beers into custom lists
- **Venues** — Associate check-ins with locations
- **Rankings** — View your top-rated beers
- **CSV Import** — Bulk import beers and check-ins (supports Untappd exports)
- **Discord Integration** — Share check-ins and purchases to Discord via webhooks or bots

## Tech Stack

- PHP 8.2+ / Laravel 12
- Livewire 3 + Volt
- Tailwind CSS
- SQLite
- Vite

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 20+
- npm

### Quick Setup

```bash
git clone <repo-url> logr-app
cd logr-app
composer setup
```

This runs `composer install`, copies `.env.example`, generates an app key, runs migrations, installs npm dependencies, and builds assets.

### Development Server

```bash
composer dev
```

Starts the Laravel dev server, queue worker, log tail, and Vite in parallel.

### Running Tests

```bash
composer test
```

## Docker Compose (Production)

The included `docker-compose.yml` runs Logr as a self-contained production deployment with SQLite.

### Quick Start

```bash
docker compose up -d
```

The app will be available at `http://localhost:8080` (configurable via `APP_PORT`).

### Services

| Service | Description |
|---------|-------------|
| `app` | PHP-FPM + Nginx serving the application |
| `queue` | Background queue worker for async jobs |

### Volumes

| Volume | Purpose |
|--------|---------|
| `db-data` | Persists the SQLite database |
| `app-storage` | Persists uploaded photos and app storage |

### Configuration

Set environment variables in a `.env` file or pass them directly:

```bash
APP_PORT=3000 docker compose up -d
```

The Docker image handles migrations automatically on startup via the entrypoint script.

### Rebuilding

```bash
docker compose build --no-cache
docker compose up -d
```

## DDEV (Local Development)

A `.ddev/` configuration is included for local development with [DDEV](https://ddev.com).

```bash
ddev start
ddev exec composer setup
```

The site will be available at `https://logr.ddev.site`. DDEV automatically starts a queue worker on launch.

## Environment Variables

Copy `.env.example` to `.env` and configure:

| Variable | Description |
|---|---|
| `CATALOG_BEER_API_KEY` | API key for Catalog.beer service |
| `LOGR_DB_URL` | URL for the Logr DB API |
| `LOGR_HUB_URL` | URL for the Logr Hub (OAuth) |
| `OPENBREWDB_URL` | URL for the Open Brewery DB API |
| `DEMO_MODE` | Set to `true` to enable demo mode with scheduled data resets |

## License

MIT
