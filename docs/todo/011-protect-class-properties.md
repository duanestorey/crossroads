# Make Class Properties Protected

**Priority:** Low
**Area:** Encapsulation
**Files:** `core/src/Builder.php`, `core/src/Content.php`, `core/src/Entries.php`

## Problem

`Builder`, `Content`, and `Entries` have all properties as `public`. This was originally `var` (PHP 4 style), auto-fixed to `public` by CS Fixer. There's no encapsulation — any code can modify internal state without validation.

## Solution

This is a larger refactor best done incrementally:

1. **Builder** — straightforward. Make all properties `protected`. They're only used within the class.
2. **Content** — more involved. Properties are accessed directly throughout the codebase (Renderer, Entries, plugins). Add getters for read access, keep setters where mutation is needed.
3. **Entries** — similar to Content.

Start with `Builder` as it has the smallest blast radius.

## Acceptance Criteria

- `Builder` properties are `protected`
- `Content` has at minimum documented which properties are public API
- No existing functionality broken
- PHPStan and tests pass
