# Changelog

All notable changes to Logr will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/), and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.2.0] - 2026-05-11

### Added
- 18 shared Blade components: icon (36 icons), primary-button, search-input, empty-state, back-link, floating-action-bar, stat, card, section-heading, form-field, badge, flash-message, size-toggle, ranked-list, photo-upload, page-header
- Check-in form now searches external APIs (LogrDB/Untappd/catalog.beer) in addition to local library
- Heart favorite animation on beer cards (expand on favorite, shrink on unfavorite)
- Batch favorite via multi-select floating action bar
- Keyboard shortcuts on beer cards: F to favorite, S to select
- Expandable plus button on page headers (Beers, Check-ins, Collections)
- Beer logo animates on keyboard focus
- Amber focus rings on all interactive elements (nav, cards, dropdowns, tabs, buttons, links)
- Konami code easter egg

### Changed
- All indigo/blurple colors replaced with amber brand color
- Button contrast improved: amber-500 to amber-600 (WCAG AA compliant)
- Muted text contrast improved: gray-400 to gray-500 for readable content
- Discord logo updated to Clyde mascot
- All amber buttons consolidated into shared primary-button component
- All file input buttons use solid amber style
- Empty states use animated beer glass logo
- 120+ inline SVGs replaced with shared icon component
- Centralized beer styles, serving types, and user agent to config
- Maps skip keyboard navigation (aria-hidden)
- Dropdowns and selects close on tab-out
- Nav links use compact spacing with inset focus ring
- Photo upload max standardized to 10MB across all forms
- Dark mode date picker icons now visible

### Fixed
- Mass assignment vulnerability: removed is_admin from User $fillable
- Data leakage: beer export now filtered by authenticated user
- Open redirect: Discord OAuth redirect URL validated
- Admin routes (api, notifications, logr, discord) now behind admin middleware
- OpenBreweryDb handles non-array API responses
- PurgeData deletes correct storage directories (beers, checkin-photos)
- Duplicate ungeocoded query in Locations render removed
- API settings disabled attribute syntax on Blade components

### Removed
- Unused collections/create route and view
- Unused BeerMap component and view
- Unused service configs (postmark, resend, ses, slack)

## [0.1.15] - 2026-05-10

### Added
- Reusable pill-tabs component used across Beers, Collections, Locations, and Venues
- Custom Alpine dropdown component replacing native selects (with sm/lg size variants)
- Configurable badge system on beer cards (position, style, icons)
- Flask icon for ABV badges and stats page
- Glass icon for serving type on stats page
- Favorites filter on beer index
- Location filter tabs (All / Missing Location / With Location) for breweries and venues
- Missing location warning icon on brewery and venue cards
- Venue autocomplete and photo upload on beer form's inline check-in
- Cancel button on check-in form

### Changed
- Card grid switched to 4 columns with 4:3 aspect ratio images
- Beer card text sizes increased for larger cards
- Beer card layout: ABV + serving bottom-left, rating bottom-right, heart top-right (amber)
- Dashboard collections split into "By Year" and "By Location" rows sorted by beer count
- Dashboard shows accurate counts for dynamic collections with "Smart" badge
- Recently Checked In cards show check-in date instead of import date
- Check-in form field order: Beer, Venue, Rating/Serving, Notes, Photos
- Beer detail hero tags unified to consistent amber style
- Beer detail pills reordered: ABV, IBU, then styles
- Collections button uses amber style, "New" button moved next to title
- Beer show displays dynamic collections with badge (no remove button)
- Inventory and collections icons changed to amber
- Sort control uses custom dropdown with separate arrow button
- Locations pages use shared pill-tabs for Venues/Breweries navigation
- Forms constrained to max-w-4xl, submit buttons aligned to bottom-right
- Renamed "Library" to "All Beers"
- Default map center set to Chicago, IL

## [0.1.14] - 2026-05-09

### Added
- Inline check-in option when creating a new beer (rating, serving, venue, notes in one form)
- Google Analytics tracking via `GOOGLE_ANALYTICS_ID` environment variable
- Demo mode countdown banner with hourly data reset
- Admin middleware protecting system info routes
- Comprehensive test suite (64 tests covering CRUD, authorization, and service integrations)
- Database indexes on checkins and inventory tables
- ABV badge overlay on beer cards
- Beer logo with fill animation and rising bubbles

### Changed
- Demo mode locks down settings pages, hides import/export, masks API credentials, disables all delete/purge actions
- Card grids use 6 columns (was 5) with improved text wrapping
- Beer card text uses line-clamp instead of hard truncate
- Discord embeds use image priority chain: checkin photo > beer label > brewery logo
- Discord webhook posts no longer send deprecated username/avatar_url
- Hub bot posts include idempotency keys to prevent duplicate Discord messages
- Event listeners renamed for clarity (SendDiscordCheckinViaWebhook/Bot)
- Rankings and BeerShow queries optimized to eliminate N+1 queries
- Setting model uses request-scoped caching
- Queue jobs have retry limits and error handling
- Docker entrypoint checks migration exit code

### Fixed
- Beer bulk delete now scoped — can't delete beers with other users' checkins
- Collection detail page verifies user ownership
- Venue delete blocked when other users have checkins
- Setup wizard redirects to login when users already exist
- Discord OAuth callback validates input parameters
- User model no longer silently saves null on decryption failure
- Console purge/reset commands blocked in demo mode
- CI workflow updated to Node 24-compatible action versions

## [0.1.13] - 2026-05-05

### Added
- Optional Google Analytics tracking via `GOOGLE_ANALYTICS_ID` environment variable

### Changed
- Demo mode banner shows reset interval instead of credentials on authenticated pages
- Settings pages are read-only in demo mode — profile, API credentials, Discord webhooks/bots, and preferences inputs are all disabled
- All delete buttons and danger zone hidden in demo mode (beer, checkin, collection, venue, bulk delete, admin purge/reset)
- Replace personal placeholder with generic "username" in API settings

## [0.1.12] - 2026-04-10

### Added
- CI workflow for automated testing on pull requests

## [0.1.11] - 2026-04-07

### Changed
- Discord bot posts now use the user's verified Discord identity instead of app-provided names
- Removed ability to set custom display names for bot posts

## [0.1.10] - 2026-04-06

### Changed
- Moved Discord OAuth flow to Logr Hub — users no longer need `DISCORD_CLIENT_ID` / `DISCORD_CLIENT_SECRET` in their environment

## [0.1.9] - 2026-04-04

### Added
- Multi-server Discord bot support — connect the Logr bot to multiple Discord servers simultaneously

## [0.1.8] - 2026-04-04

### Fixed
- Setup integrations banner dismissal now persists permanently instead of resetting each session

## [0.1.7] - 2026-04-04

### Fixed
- APP_KEY with base64 trailing `=` no longer stripped from .env during container startup

## [0.1.6] - 2026-04-04

### Fixed
- APP_KEY generated via openssl and persisted to data volume on first run
- APP_KEY no longer lost on container rebuilds

## [0.1.5] - 2026-04-03

### Added
- Manual "Check for Updates" button in footer and System Info page
- Refresh icon spins while checking for updates

### Changed
- Version check cache reduced from 12 hours to 4 hours

## [0.1.4] - 2026-04-03

### Fixed
- APP_KEY now persisted to data volume, preventing key loss on container rebuilds
- Graceful handling of encrypted data when APP_KEY changes (settings reset instead of 500 error)

## [0.1.3] - 2026-04-02

### Changed
- Version bump

## [0.1.2] - 2026-04-02

### Fixed
- Asset compilation in Docker build (styles now compiled directly)

## [0.1.1] - 2026-04-01

### Changed
- Default port changed from 8080 to 8337 (BEER on a phone keypad)
- Updated Docker Compose instructions to use GHCR image directly

## [0.1.0] - 2026-04-01

### Added
- Beer library with brewery, style, ABV, IBU tracking
- Check-in system with ratings, notes, serving types, and venues
- Inventory management with storage locations and purchase tracking
- Collections (static and dynamic/rule-based)
- Tags and companions for organizing check-ins and beers
- Untappd integration (RSS sync, profile scraping, data enrichment)
- Catalog.beer API integration for beer search
- Discord webhook notifications for check-ins and inventory
- Location tracking with venue management and brewery geocoding
- CSV export for beers and check-ins
- Import tools for external data
- Setup wizard for first-run configuration
- Demo data seeder for trying out the app
- Docker image with single-container deployment
- Database purge and reset tools
- System info page with version and dependency details

[0.2.0]: https://github.com/logr-beer/logr-app/releases/tag/v0.2.0
[0.1.15]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.15
[0.1.14]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.14
[0.1.13]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.13
[0.1.12]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.12
[0.1.11]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.11
[0.1.10]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.10
[0.1.9]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.9
[0.1.8]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.8
[0.1.7]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.7
[0.1.6]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.6
[0.1.5]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.5
[0.1.4]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.4
[0.1.3]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.3
[0.1.2]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.2
[0.1.1]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.1
[0.1.0]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.0
