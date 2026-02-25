# Fix Exception Class Hierarchy

**Priority:** Medium
**Area:** Correctness
**Files:** `core/src/Exception.php`, `core/src/BuildException.php`, `core/src/CommandException.php`, `core/src/SassException.php`, `core/src/ThemeException.php`, `core/src/Engine.php`

## Problem

1. `CR\Exception` shadows `\Exception`. In `Engine::_build()`, catching `Exception` only catches `CR\Exception`, not `\RuntimeException`, `\InvalidArgumentException`, etc. Unexpected errors go completely unhandled.

2. Subclasses don't call `parent::__construct()` with a message, so `getMessage()` always returns an empty string. They store messages in a custom `$msg` property instead.

## Solution

1. Make the catch block in `Engine::_build()` catch `\Exception` (or `\Throwable`) to handle all errors
2. Have custom exception constructors call `parent::__construct($message)` so standard exception methods work
3. Consider whether the custom `$name` / `$msg` properties are still needed once `getMessage()` works

## Acceptance Criteria

- `Engine::_build()` catches all exceptions, not just `CR\Exception`
- Exception subclasses pass messages to `parent::__construct()`
- `getMessage()` returns meaningful messages
- Add tests for exception construction
