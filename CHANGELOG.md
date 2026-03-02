# Changelog

All notable changes to Crossroads will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Two-package distribution architecture (`duanestorey/crossroads` skeleton + `duanestorey/crossroads-core` engine)
- `core/composer.json` defining the core engine as a standalone Composer library package
- `CROSSROADS_IS_COMPOSER` constant to detect Composer-managed installations
- Smart core auto-detection in entry script (supports `core/` dev layout and `vendor/` installed layout)
- Composer-aware upgrade command — runs `composer update` when installed via Composer
- Skeleton template directory (`skeleton/`) for the `composer create-project` workflow
- GitHub Actions release workflow (`.github/workflows/release.yml`) triggered on version tags
- New i18n strings for Composer upgrade flow

### Changed

- Root `composer.json` type changed from implicit to `project` (enables `composer create-project`)
- Root `composer.json` now requires `duanestorey/crossroads-core` via path repository (dev symlink)
- Upgrade command now fetches tagged releases via GitHub Releases API instead of raw `main` branch
- All hardcoded `core/` paths replaced with `CROSSROADS_CORE_DIR`, `CROSSROADS_SRC_DIR`, `CROSSROADS_CONTENT_DIR`, and `CROSSROADS_CONFIG_DIR` constants

### Removed

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
