# Remove Dead ImagePlugin Code

**Priority:** Medium
**Area:** Dead Code / Maintenance
**Files:** `core/plugins/ImagePlugin.php`

## Problem

`ImagePlugin` is a near-complete copy of `ImageProcessor` (~250 lines duplicated). It is never registered in `Engine::run()` — only `SeoPlugin` and `WordPressPlugin` are installed. The actual image processing runs through `Content::processImages()` calling `ImageProcessor` directly.

Bugs fixed in one copy won't be fixed in the other.

## Solution

Delete `core/plugins/ImagePlugin.php` entirely. If image processing as a plugin is desired later, refactor `ImageProcessor` into that role rather than maintaining two copies.

## Acceptance Criteria

- `ImagePlugin.php` deleted
- No references to `ImagePlugin` remain in the codebase
- PHPStan and tests pass
