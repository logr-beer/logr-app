# Logr

A personal beer logging app built with Laravel 12, Livewire 3, and Volt.

Track beers, check-ins, breweries, venues, collections, and rankings. Supports importing from Untappd and connecting to the Logr ecosystem (Logr DB, Logr Hub).

## Requirements

- PHP 8.2+
- Node.js & npm
- SQLite (default) or another Laravel-supported database

## Setup

```bash
composer setup
```

This runs `composer install`, copies `.env.example`, generates an app key, runs migrations, and builds frontend assets.

## Development

```bash
composer dev
```

Starts the Laravel dev server, queue worker, log tail, and Vite dev server concurrently.

## Environment Variables

Copy `.env.example` to `.env` and configure:

| Variable | Description |
|---|---|
| `CATALOG_BEER_API_KEY` | API key for Catalog.beer service |
| `LOGR_DB_URL` | URL for the Logr DB API |
| `LOGR_HUB_URL` | URL for the Logr Hub (OAuth) |
| `OPENBREWDB_URL` | URL for the Open Brewery DB API |
| `DEMO_MODE` | Set to `true` to enable demo mode with scheduled data resets |

## Testing

```bash
composer test
```
