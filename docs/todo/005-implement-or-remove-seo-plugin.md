# Implement or Remove Empty SeoPlugin

**Priority:** Medium
**Area:** Dead Code
**Files:** `core/plugins/SeoPlugin.php`, `core/src/Engine.php`

## Problem

`SeoPlugin` overrides `processOne()` but does nothing — it returns the content unchanged. It is registered in `Engine::run()` and runs on every entry, adding overhead for no benefit.

## Options

**Option A — Remove it:** Delete the file, remove the `installPlugin` call from Engine, and add SEO when there's an actual implementation.

**Option B — Implement it:** Add useful SEO processing:
- Generate meta description from excerpt if not set in front matter
- Validate that title and slug exist
- Set canonical URL
- Add Open Graph / Twitter Card metadata to template params via `templateParamFilter()`

## Acceptance Criteria

- Either removed entirely or has meaningful functionality
- PHPStan and tests pass
- If implemented, add tests for the new behavior
