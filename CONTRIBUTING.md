# Contributing to Crossroads

## Getting Started

```bash
git clone https://github.com/duanestorey/crossroads.git
cd crossroads
composer install
```

Running `composer install` automatically configures git hooks via `core.hooksPath`.

## Development Workflow

### Quality Checks

```bash
composer cs-check    # Check code style (dry run)
composer cs-fix      # Auto-fix code style
composer lint        # Run PHPStan static analysis
composer test        # Run Pest test suite
composer check       # Run all three checks
```

### Running a Single Test

```bash
vendor/bin/pest tests/Unit/MarkdownTest.php
vendor/bin/pest --filter="parses front matter"
```

### Pre-commit Hook

The git pre-commit hook runs code style checks, PHPStan, and tests automatically. If any check fails, the commit is blocked. Fix the issue and try again:

```bash
composer cs-fix      # Fix style issues
composer lint        # See PHPStan details
composer test        # See test details
```

## Code Standards

### Style

- **PSR-12** enforced by PHP CS Fixer
- Configuration: `.php-cs-fixer.dist.php`
- Run `composer cs-fix` before committing

### Static Analysis

- **PHPStan level 5** with type hint suppressions (adding full type hints is a future goal)
- Configuration: `phpstan.neon`
- Runtime constants are defined in `phpstan-bootstrap.php` for static analysis

### Namespaces

- Core classes: `CR\` namespace, located in `core/src/`
- Plugins: `CR\Plugins\` namespace, located in `core/plugins/`
- Tests: `CR\Tests\` namespace, located in `tests/`
- File names must match class names (PSR-4): e.g., `PluginManager.php` for class `PluginManager`

### File Naming

- PascalCase for class files: `TemplateEngine.php`, `ImageProcessor.php`
- Acronyms stay uppercase: `DB.php`, `MYSQL.php`, `SASS.php`, `YAML.php`

## Writing Tests

Tests use [Pest](https://pestphp.com/) and live in `tests/Unit/`. Each test file follows this pattern:

```php
<?php

use CR\YourClass;

it('describes expected behavior', function () {
    $obj = new YourClass();
    expect($obj->method())->toBe('expected');
});
```

Test bootstrap (`tests/bootstrap.php`) defines runtime constants so tests can run without the full CLI entry point.

## Writing Plugins

Extend `CR\Plugin` and override the hooks you need:

```php
<?php

namespace CR\Plugins;

use CR\Plugin;

class MyPlugin extends Plugin
{
    // Modify a single content entry
    public function processOne($entry)
    {
        // modify $entry
        return $entry;
    }

    // Modify the full entry collection
    public function processAll($entries)
    {
        // modify $entries array
        return $entries;
    }

    // Modify template rendering parameters
    public function templateParamFilter($params)
    {
        // modify $params
        return $params;
    }
}
```

Register the plugin in `Engine::run()`:

```php
$this->pluginManager->installPlugin(new MyPlugin($this->config));
```

## Project Structure

```
crossroads              # CLI entry point
core/
  src/                  # Core classes (CR\ namespace)
  plugins/              # Built-in plugins (CR\Plugins\ namespace)
  themes/               # Bundled themes
  schemas/              # SQLite schema files
  i18n/                 # Locale files (en.yaml, es.yaml)
tests/
  Unit/                 # Pest unit tests
.githooks/              # Git hooks (pre-commit)
.github/workflows/      # GitHub Actions CI
```

## CI

GitHub Actions runs on pushes to `main` and all pull requests. The pipeline runs the same three checks as the pre-commit hook: code style, PHPStan, and tests.
