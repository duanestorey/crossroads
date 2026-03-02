# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Crossroads is a PHP CLI static site generator. Markdown files with YAML front matter are processed into a complete static HTML website.

## Commands

```bash
# Install dependencies
composer install

# Build the static site (output goes to _public/)
php crossroads build

# Run local dev server (serves _public/)
php crossroads serve

# Create new content
php crossroads new <content-type>   # e.g., php crossroads new post

# Clean generated output
php crossroads clean

# Import from WordPress
php crossroads import wordpress <url>

# Database operations
php crossroads db import|export|sync

# Self-upgrade from GitHub
php crossroads upgrade

# Show content statistics
php crossroads stats
```

## Quality Checks

```bash
# Run all checks (code style + static analysis + tests)
composer check

# Auto-fix code style (PSR-12)
composer cs-fix

# Check code style without fixing (CI mode)
composer cs-check

# Run PHPStan static analysis (level 5)
composer lint

# Run Pest test suite
composer test
```

## Distribution Architecture

Crossroads uses a two-package split (Laravel-style):

- **`duanestorey/crossroads`** (this repo) — skeleton/project template with user content, config, CLI script
- **`duanestorey/crossroads-core`** (separate repo, installed via Composer) — engine library with src, plugins, themes, schemas, i18n

### Core Engine Location

The core engine always lives at `vendor/duanestorey/crossroads-core/` (installed from Packagist). There is no local `core/` directory — to develop the engine, work in the `duanestorey/crossroads-core` repo directly.

- `CROSSROADS_CORE_DIR` → `vendor/duanestorey/crossroads-core/`
- `CROSSROADS_IS_COMPOSER` → `true`

End users install with `composer create-project duanestorey/crossroads my-blog` and upgrade core via `composer update`. Dev tool configs (PHPStan, CS Fixer, PHPUnit) all point to the vendor path.

### Key Constants

| Constant | Purpose |
|----------|---------|
| `CROSSROADS_BASE_DIR` | Project root directory |
| `CROSSROADS_CORE_DIR` | Core engine directory (auto-detected) |
| `CROSSROADS_SRC_DIR` | Core source (`CORE_DIR/src`) |
| `CROSSROADS_CONTENT_DIR` | User content (`BASE_DIR/_content`) |
| `CROSSROADS_CONFIG_DIR` | User config (`BASE_DIR/_config`) |
| `CROSSROADS_IS_COMPOSER` | Whether this is a Composer-managed installation |

## Architecture

### Build Pipeline

`Engine` (`core/src/Engine.php`) is the CLI entry point and command router. It dispatches to:

`Builder` (`core/src/Builder.php`) which orchestrates the full build:
1. Sets up theme and loads menus from `_config/menus.yaml`
2. Initializes Latte template engine
3. `Entries` loads all `.md` files from `_content/<type>/`, parses YAML front matter via `Markdown`, creates `Content` objects
4. `Content::calculate()` derives URLs, word count, reading time, taxonomy links
5. `Content::processImages()` finds images, generates responsive variants, converts to WebP
6. `PluginManager` runs all plugins against each entry
7. `Renderer` generates single pages, index/paginated pages, taxonomy pages, and home page
8. Writes `sitemap.xml` and `robots.txt`

### Content Model

`Content` (`core/src/Content.php`) holds a single entry's data: title, slug, dates, HTML, taxonomy, image info, reading time. Created from markdown front matter by `Entries`.

`Entries` (`core/src/Entries.php`) manages the full collection — loading, taxonomy organization, and retrieval by type/taxonomy/term.

### Configuration

`_config/site.yaml` is loaded by `Config` (`core/src/Config.php`) and accessed via dot notation: `$config->get('site.name')`, `$config->get('content.posts.taxonomy')`.

Key config sections: `site.*` (metadata, theme), `content.*` (content type definitions with taxonomy mappings), `options.*` (debug, pagination, image settings).

### Theme System

Themes live in `core/themes/` (bundled) or `_themes/` (local). Each theme has:
- `theme.yaml` — name, author, asset bundles (CSS/SCSS/JS lists), images
- `.latte` templates — `index.latte`, `*-single.latte`, `header.latte`, `footer.latte`, etc.
- `assets/` — CSS, SCSS, JS, images

`Theme` (`core/src/Theme.php`) handles asset compilation (SCSS→CSS via scssphp), concatenation, and copying. Supports parent/child theme inheritance.

`TemplateEngine` (`core/src/TemplateEngine.php`) wraps Latte with a custom `LatteFileLoader` that resolves templates across multiple theme directories.

### Plugin System

`Plugin` base class (`core/src/Plugin.php`) provides three hooks:
- `processOne($entry)` — modify a single Content entry
- `processAll($entries)` — modify the full entry collection
- `templateParamFilter($params)` — modify template rendering parameters

`PluginManager` (`core/src/PluginManager.php`) chains installed plugins. Built-in plugins in `core/plugins/` (`CR\Plugins\` namespace): `SeoPlugin`, `WordPressPlugin`.

### Rendering

`Renderer` (`core/src/Renderer.php`) takes Content objects and template engine, generates HTML pages. Supports page types: HOME, TAXONOMY, CONTENT, AUTHOR. Handles pagination and provides standard template variables (`$site`, `$page`, `$menu`, `$content`, `$pagination`, `$isSingle`, `$isHome`).

### Supporting Systems

- **Database**: SQLite (`core/src/DB.php`) — content/taxonomy storage and caching, schemas in `core/schemas/`
- **Logging**: Singleton `Log` class with shell and file listeners, global `LOG()` function
- **i18n**: YAML locale files in `core/i18n/` (en, es), accessed via `_i18n('key')`
- **Image Processing**: `ImageProcessor` generates responsive sizes, WebP/AVIF conversion via GD
- **WordPress Import**: `Importers\WordPress` fetches via REST API, converts HTML→Markdown

## Code Conventions

- All core classes are in the `CR\` namespace, plugins in `CR\Plugins\`
- PSR-4 autoloading via Composer — file names match class names (PascalCase)
- Singleton pattern used for `Log`, `International`
- Exceptions: `ThemeException`, `SassException`, `BuildException`, `CommandException`
- Engine routes CLI commands to private methods named `_<command>()` (e.g., `_build()`, `_serve()`)
- Version constant `CROSSROADS_VERSION` defined in the `crossroads` entry script

## Release Process

When cutting a new version release:

1. **Update version** — Set `CROSSROADS_VERSION` in the `crossroads` entry script to the new version number
2. **Update changelog** — In `CHANGELOG.md`, replace `[Unreleased]` with `[X.Y.Z] - YYYY-MM-DD` using today's date. Add a new `## [Unreleased]` section above it for future changes
3. **Run checks** — `composer check` must pass clean (code style, PHPStan, Pest tests)
4. **Commit** — Commit with a message like `Release vX.Y.Z`
5. **Tag** — `git tag vX.Y.Z` and push tag; GitHub Actions release workflow creates the GitHub Release automatically

The changelog follows [Keep a Changelog](https://keepachangelog.com/) format with these categories: Security, Added, Changed, Fixed, Removed. During development, new entries go under `[Unreleased]`.
