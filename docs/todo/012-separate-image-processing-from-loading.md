# Separate Image Processing from Content Loading

**Priority:** Medium
**Area:** Performance / Architecture
**Files:** `core/src/Entries.php`, `core/src/Builder.php`

## Problem

`Content::processImages()` is called inside `Entries::loadAll()`, interleaving image I/O (GD decode/encode, `getimagesize`, file writes) with content loading. This makes the loading phase slow and hard to profile or parallelize.

## Solution

Move image processing into a separate pass in `Builder::run()`:

```php
// Current: images processed during loading
$this->entries->loadAll($config);

// Proposed: separate passes
$this->entries->loadAll($config);
$this->entries->processAllImages($config);  // new method
```

This makes each build phase independently measurable and opens the door for parallel image processing.

## Acceptance Criteria

- `Entries::loadAll()` no longer triggers image processing
- Image processing runs as a separate build phase
- Build output is identical before and after
- Performance can be measured per phase
