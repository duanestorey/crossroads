# Fix Unicode Handling in Content Excerpt

**Priority:** Low
**Area:** Bug Fix / i18n
**Files:** `core/src/Content.php`

## Problem

`Content::excerpt()` uses `strlen()` and `substr()` which count bytes, not characters. Non-ASCII content (accented characters, CJK, emoji) produces incorrect excerpt lengths — cutting mid-character and potentially generating broken UTF-8.

## Solution

Replace `strlen()` with `mb_strlen()` and `substr()` with `mb_substr()`:

```php
if (mb_strlen($stripped) > $length) {
    return mb_substr($stripped, 0, $length) . '...';
}
```

## Acceptance Criteria

- Excerpts handle multi-byte characters correctly
- Add a test with accented characters (e.g., "Café résumé") and emoji
- Existing excerpt tests still pass
