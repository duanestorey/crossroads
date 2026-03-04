# Changelog

All notable changes to Crossroads will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.2.0] - 2026-03-04

### Added
- 14 new test files covering all previously untested core classes: Builder, ContentDiscoverImages, DevServer, Engine, FileWatcher, ImageProcessor, International, LatteFileLoader, Renderer, SASS, SQLite, TemplateEngine, Theme, Upgrade
- Bootstrap assertion for `CROSSROADS_CORE_DIR` existence for fast failure diagnostics
- Test count: 116 → 241 (+125 tests, +184 assertions)

### Changed
- Refactor `LlmsTxtTest` to test real `Builder::sanitizeDescription()` method instead of duplicating inline logic

### Fixed
- Move completed TODO docs (005, 007, 009) from `docs/todo/` to `docs/done/`

## [2.1.1] - 2026-03-03

### Fixed
- Remove invalid `LLMsTxt` directive from robots.txt
- Add visually-hidden post titles to "read more" links for SEO
- Add preconnect for cdn.jsdelivr.net and use minified Nerd Fonts CSS
- Align index page entry dates to top of title text
- Uppercase date formatting on index pages

### Changed
- Add Nerd Font icons to sidebar, post meta, tags, pagination, footer, and 404 page

## [2.1.0] - 2026-03-02

### Added
- Load i18n locale in test bootstrap so `_i18n()` returns real strings during tests
- New test coverage: YAML, Menu, Log, SeoPlugin, WordPressPlugin, LlmsTxt, DB (8 new test files)
- Extended test coverage: Config `set()` tests, Utils `findAllFilesWithExtension()` and `titleToSlug()` edge cases

### Changed
- Rewrite EntriesTest with real behavior tests (populated data, taxonomy queries)
- Rewrite PluginManagerTest with cumulative processAll chaining and contentFilter tests
- Fix ContentTest readingTime assertion to verify actual i18n content
- Fix MarkdownTest strippedMarkdown test to use inline HTML tags
- Merge redundant SchemaTest tests into single test with file guard

### Fixed
- Add `_archive/` and `_public/` to `.gitignore`

## [2.0.0] - 2026-03-02

### Changed

- Redirect build output from `_public/` to `_site/public/` for local-build-and-deploy workflow
- Drafts are always built with `<meta name="crossroads-draft">` tag and visual banner (no more `--drafts` flag)
- Replace CI build-and-deploy GitHub Action with draft-guard workflow that fails if draft HTML is present
- Sync `composer.dev.json` memory limit with `composer.json`

### Removed

- `_themes/phosphor/` local theme (now bundled in core at `themes/phosphor/`)
- `--drafts` CLI flag (drafts are always included)

## [1.3.0] - 2026-03-02

### Changed

- Consolidate `_content/` and `_config/` under `_site/` directory (`_site/content/`, `_site/config/`)
- Add `CROSSROADS_SITE_SLUG` and `CROSSROADS_SITE_DIR` constants
- Increase PHPStan memory limit to 512M in `composer lint` script

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
