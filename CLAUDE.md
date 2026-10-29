# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Logr is a self-hosted beer logging and collection management app. Built with Laravel 12, Livewire 3 + Volt, Tailwind CSS, and SQLite. Dockerized for deployment.

## Commands

Use `php artisan` / `composer` / `npm` directly. Prefix with `ddev` / `ddev exec` only when working inside DDEV (optional; see below).

```bash
# Setup
composer setup                               # Full install (composer, .env, key, migrate, npm build)
php artisan db:seed --class=DemoSeeder       # Demo account demo / password (needed for E2E)

# Development
composer dev           # Dev server (artisan serve, queue:listen, pail, vite via concurrently)
php artisan serve      # Just the app, at http://127.0.0.1:8000

# Tests
composer test          # Clear config cache + run PHPUnit
php artisan test --filter=TestClassName
php artisan test tests/Feature/SomeTest.php
PLAYWRIGHT_BASE_URL=http://127.0.0.1:8000 npm run test:e2e   # Playwright E2E

# Lint (Laravel Pint - PSR-12 style)
./vendor/bin/pint

# Artisan utilities
php artisan migrate
php artisan tinker
```

**DDEV (optional)**: `ddev start`, then the same commands prefixed: `ddev composer setup`, `ddev composer dev`, `ddev composer test`, `ddev artisan ...`, `ddev exec ...`. The site is at `https://logr.ddev.site`; DDEV's post-start hook symlinks `database/database.sqlite` to a shared `/data` volume. Outside DDEV the app just uses `database/database.sqlite`.

**Agent worker recipe**: `.agent/` holds the orchestrator's setup recipe (`job.sh`) and environment notes (`prompt.md`) for this repo. Update them in the same PR when setup changes.

## Architecture

**Stack**: PHP 8.4 / Laravel 12 / Livewire 3 + Volt / Alpine.js / Tailwind / SQLite

**UI is Livewire-driven** — almost no traditional controllers. Routes in `routes/web.php` point to Blade views that render Livewire components (`app/Livewire/`). Forms, search, filtering, and reactivity are all handled by Livewire, not API endpoints.

**Key directories**:
- `app/Livewire/` — Page-level components (BeerForm, CheckinForm, BeerIndex, etc.)
- `app/Services/` — External integrations (Untappd, Discord, CatalogBeer, Pub, PubBeerDb)
- `app/Jobs/` — Background queue jobs (geocoding, scraping, RSS sync)
- `app/Models/` — Eloquent models with relationships
- `app/Events/` + `app/Listeners/` — CheckinCreated fires Discord/Hub notifications

**Data model**: Beers belong to Breweries. Checkins (tastings) reference a Beer and optionally a Venue. Inventory tracks beer stock. Collections can be static (manual beer list) or dynamic (rule-based filters on year/style/rating/favorites). Tags are polymorphic (beers + checkins).

**User credentials**: Integration credentials (Untappd, Discord webhooks, API keys) are stored in an encrypted JSON `data` column on the User model, not in `.env`.

**Collections with dynamic rules**: The `Collection` model has a `resolveBeers()` method that applies filter rules (year, style, min_rating, favorites, storage_location) to build dynamic beer lists.

**Queue**: Database-backed queue. Jobs dispatched for geocoding, Untappd scraping, and RSS sync.

**Docker deployment**: Nginx + PHP-FPM + Supervisord. `docker/entrypoint.sh` handles first-boot setup (migrations, APP_KEY generation, symlinks to `/data` volume). Port 8337.

## Version & Release

Version lives in `config/logr.php`. Bump patch level only (e.g., 0.1.12 -> 0.1.13). Pushing a `v*` tag triggers GitHub Actions to build and push Docker images to Docker Hub + GHCR.

**Release process**: Run `composer test:all` (PHPUnit + Playwright E2E) before tagging. If tests fail, fix before release. Push version bump directly to main, then tag.

## Testing

Tests use in-memory SQLite (configured in `phpunit.xml`). Test suite is in `tests/Feature/` and `tests/Unit/`.

```bash
composer test          # PHPUnit only
npm run test:e2e       # Playwright E2E only (headless, requires a running app + demo user)
composer test:all      # Both PHPUnit + Playwright (run before release)
npm run test:e2e:headed  # Playwright with visible browser
npm run test:e2e:ui    # Playwright interactive debugger
```

Playwright tests require Chromium installed locally (`npm run test:e2e:install`), a running app, and the seeded demo user (`php artisan db:seed --class=DemoSeeder`; account `demo` / `password`). The base URL comes from `PLAYWRIGHT_BASE_URL`, then `APP_URL`, then defaults to `https://logr.ddev.site` (DDEV).
