# Remove Hardcoded Personal Domains from WordPress Importer

**Priority:** High
**Area:** Correctness
**Files:** `core/src/importers/wordpress.php`

## Problem

The WordPress importer has hardcoded domain replacements for personal sites:

```php
$new_image = str_replace('www.duanestorey.com', 'old.duanestorey.com', $image);
$new_image = str_replace('www.migratorynerd.com/wordpress/', 'old.duanestorey.com/', $new_image);
```

Any user importing from a different WordPress site would have this code silently run and potentially corrupt image URLs.

## Solution

Remove all hardcoded domain replacement lines. If domain rewriting is needed, add it as a configurable option in `_config/site.yaml` (e.g., `import.domain_rewrites`).

## Acceptance Criteria

- No hardcoded domains in the importer
- Import from any WordPress site works without silent URL rewriting
- If domain rewriting is kept, it's config-driven
