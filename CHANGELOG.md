# Changelog

All notable changes to Logr will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/), and this project adheres to [Semantic Versioning](https://semver.org/).

## [0.1.13] - 2026-05-05

### Added
- Optional Google Analytics tracking via `GOOGLE_ANALYTICS_ID` environment variable

### Changed
- Demo mode banner shows reset interval instead of credentials on authenticated pages
- Settings pages are read-only in demo mode — profile, API credentials, Discord webhooks/bots, and preferences inputs are all disabled
- All delete buttons and danger zone hidden in demo mode (beer, checkin, collection, venue, bulk delete, admin purge/reset)
- Replace personal placeholder with generic "username" in API settings

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

[0.1.13]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.13
[0.1.8]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.8
[0.1.7]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.7
[0.1.6]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.6
[0.1.5]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.5
[0.1.4]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.4
[0.1.3]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.3
[0.1.2]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.2
[0.1.1]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.1
[0.1.0]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.0
