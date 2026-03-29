# Logr

A self-hosted beer logging app. Track your beer library, check-ins, inventory, collections, and more.
Built with Laravel 12, Livewire 3, and Tailwind CSS.

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

## Local Development

### Why DDEV?

[DDEV](https://ddev.com) provides a consistent, containerized development environment with PHP, Composer, Node.js, and npm out of the box.

### Prerequisites

- [DDEV](https://ddev.com)

### Quick Setup

```bash
git clone <repo-url> logr-app
cd logr-app
ddev start
ddev composer setup
```

The site will be available at `https://logr.ddev.site`. DDEV automatically starts a queue worker on launch.

### Running Tests

```bash
ddev composer test
```

## Docker Compose

The included `docker-compose.yml` runs Logr as a self-contained deployment with SQLite.

### Quick Start

```bash
docker compose up -d
```

The app will be available at `http://localhost:8080` (configurable via `APP_PORT`).

### Volumes

| Volume | Purpose |
|--------|---------|
| `db-data` | Persists the SQLite database |
| `app-storage` | Persists uploaded photos and app storage |

### Configuration

Optionally create a `.env` file next to `docker-compose.yml` to customize settings:

```env
# App settings (all optional — sensible defaults are provided)
APP_NAME=Logr
APP_URL=http://localhost:8080
APP_PORT=8080

# Integrations
CATALOG_BEER_API_KEY=
LOGR_DB_URL=
LOGR_DISCORD_URL=

# Set to true to enable demo mode with scheduled data resets
DEMO_MODE=false
```

Then start the app:

```bash
docker compose up -d
```

On first boot, the entrypoint automatically:
- Generates an `APP_KEY` if one isn't set
- Creates the storage symlink
- Runs database migrations
- Seeds default data

### Rebuilding

```bash
docker compose build --no-cache
docker compose up -d
```

## Environment Variables

Copy `.env.example` to `.env` and configure:

| Variable | Description |
|---|---|
| `CATALOG_BEER_API_KEY` | API key for Catalog.beer service |
| `LOGR_DB_URL` | URL for the Logr DB API |
| `LOGR_HUB_URL` | URL for the Logr Hub (OAuth) |
| `LOGRDB_URL` | URL for the LogrDB API |
| `DEMO_MODE` | Set to `true` to enable demo mode with scheduled data resets |

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

To pull the published image instead of building locally:

```bash
docker pull ghcr.io/logr-beer/logr-app:latest
```

## License

Logr is open-source software licensed under the [GNU Affero General Public License v3.0 (AGPL-3.0)](LICENSE).

You are free to fork, modify, and self-host Logr. If you distribute a modified version or run it as a network service, you must make your source code available under the same license.
