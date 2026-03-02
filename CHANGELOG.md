# Changelog

All notable changes to Crossroads will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-03-02

### Added

- Two-package distribution architecture (`duanestorey/crossroads` skeleton + `duanestorey/crossroads-core` engine)
- `CROSSROADS_IS_COMPOSER` constant to detect Composer-managed installations
- Composer-aware upgrade command — runs `composer update` when installed via Composer
- GitHub Actions release workflow (`.github/workflows/release.yml`) triggered on version tags
- New i18n strings for Composer upgrade flow

### Changed

- Root `composer.json` type changed from implicit to `project` (enables `composer create-project`)
- Root `composer.json` now requires `duanestorey/crossroads-core` from Packagist
- Upgrade command now fetches tagged releases via GitHub Releases API instead of raw `main` branch
- All hardcoded `core/` paths replaced with `CROSSROADS_CORE_DIR`, `CROSSROADS_SRC_DIR`, `CROSSROADS_CONTENT_DIR`, and `CROSSROADS_CONFIG_DIR` constants
- Dev tool configs (PHPStan, CS Fixer, PHPUnit) now point to `vendor/duanestorey/crossroads-core/`

### Removed

- `core/` directory — engine code now lives exclusively in the `duanestorey/crossroads-core` package
- `skeleton/` directory — root `composer.json` now serves directly as the project template
- `dirs.core_themes` config key — theme paths now derived from `CROSSROADS_CORE_DIR`

## [1.0.0] - 2026-02-25

### Security

- Enable SSL certificate verification for cURL requests (previously disabled)
- Convert all database queries to prepared statements with bound parameters

### Fixed

- Fix taxonomy schema foreign key (was a `FOREIGH` typo, now proper `REFERENCES content(id)`)
- Enable SQLite foreign key enforcement via `PRAGMA foreign_keys = ON`
- Fix double usage output when CLI receives wrong argument count
- Fix Unicode excerpt truncation — use `mb_strlen()` instead of `strlen()`
- Return empty arrays instead of `false` from `Entries::get()`, `getTaxTypes()`, `getTaxTerms()`, `getTax()`

### Changed

- Rename `MYSQL` class to `SQLite` to reflect actual database driver
- Remove hardcoded personal domains from WordPress importer

### Removed

- Remove dead `ImagePlugin` (never registered; image processing uses `Content::processImages()`)
- Remove `escape()` and `escapeWithTicks()` from database layer (replaced by prepared statements)
