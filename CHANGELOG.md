# Changelog

All notable changes to Crossroads will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
