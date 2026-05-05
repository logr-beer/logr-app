# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Logr is a self-hosted beer logging and collection management app. Built with Laravel 12, Livewire 3 + Volt, Tailwind CSS, and SQLite. Dockerized for deployment.

## Commands

```bash
# Development (DDEV)
ddev start
ddev composer setup    # Full install (composer, env, key, migrate, npm)
ddev composer dev      # Dev server with HMR (serve + queue + pail + vite)
ddev composer test     # Run tests

# Development (manual)
composer setup         # Full install
composer dev           # Dev server (artisan serve, queue:listen, pail, vite via concurrently)
composer test          # Clear config cache + run PHPUnit

# Single test
php artisan test --filter=TestClassName
php artisan test tests/Feature/SomeTest.php

# Lint (Laravel Pint - PSR-12 style)
./vendor/bin/pint

# Artisan utilities
php artisan migrate
php artisan tinker
```

## Architecture

**Stack**: PHP 8.4 / Laravel 12 / Livewire 3 + Volt / Alpine.js / Tailwind / SQLite

**UI is Livewire-driven** — almost no traditional controllers. Routes in `routes/web.php` point to Blade views that render Livewire components (`app/Livewire/`). Forms, search, filtering, and reactivity are all handled by Livewire, not API endpoints.

**Key directories**:
- `app/Livewire/` — Page-level components (BeerForm, CheckinForm, BeerIndex, etc.)
- `app/Services/` — External integrations (Untappd, Discord, CatalogBeer, Hub, LogrDb)
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

## Testing

Tests use in-memory SQLite (configured in `phpunit.xml`). Test suite is in `tests/Feature/` and `tests/Unit/`.
