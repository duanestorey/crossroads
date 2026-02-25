# Fix Command Routing Double Usage Print

**Priority:** Low
**Area:** Bug Fix
**Files:** `core/src/Engine.php`

## Problem

In `Engine::run()`, when a command is found but has the wrong argument count, `_usage()` is called but the loop continues. `$foundCommand` is never set to `true`, so after the loop, `_usage()` is called again. The user sees usage printed twice.

## Solution

After calling `_usage()` for wrong argument count, either `return` or set `$foundCommand = true` and `break`.

```php
if ($argc != ($required_params + 2)) {
    $this->_usage();
    return;
}
```

## Acceptance Criteria

- Usage is only printed once for any error condition
- Valid commands still dispatch correctly
