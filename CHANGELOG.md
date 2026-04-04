# Changelog

All notable changes to Logr will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/), and this project adheres to [Semantic Versioning](https://semver.org/).

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

[0.1.4]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.4
[0.1.3]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.3
[0.1.2]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.2
[0.1.1]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.1
[0.1.0]: https://github.com/logr-beer/logr-app/releases/tag/v0.1.0
