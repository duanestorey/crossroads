# Fix Methods Returning false Instead of Empty Arrays

**Priority:** Low
**Area:** Type Consistency
**Files:** `core/src/Entries.php`

## Problem

`Entries::getTaxTypes()` and `Entries::getTaxTerms()` return `false` when empty instead of `[]`. Callers use `count()` on the result — `count(false)` returns 0 but triggers a deprecation warning in PHP 8.x and is semantically wrong.

## Solution

Change default returns from `false` to `[]`:

```php
public function getTaxTypes()
{
    if (!$this->taxonomy) {
        return [];
    }
    // ...
}
```

## Acceptance Criteria

- Both methods return `[]` instead of `false` when empty
- Callers don't need changes (they already use `count()`)
- Add tests for the empty-state returns
- PHPStan passes
