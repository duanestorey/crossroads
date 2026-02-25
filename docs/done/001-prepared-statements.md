# Replace Manual SQL Escaping with Prepared Statements

**Priority:** High
**Area:** Security / Correctness
**Files:** `core/src/DB.php`, `core/src/MYSQL.php`

## Problem

`DB.php` builds queries using `sprintf()` with `MYSQL::escapeWithTicks()`, which wraps `SQLite3::escapeString()`. This is not equivalent to prepared statements and is insufficient for all injection vectors (numeric contexts, LIKE patterns, Unicode edge cases).

## Solution

Replace all queries in `DB.php` that use `escapeWithTicks()` with `SQLite3::prepare()` / `bindParam()` calls. Remove `escapeWithTicks()` from `MYSQL.php` once no callers remain.

## Acceptance Criteria

- All INSERT/UPDATE/SELECT queries in `DB.php` use prepared statements
- `escapeWithTicks()` method removed from `MYSQL.php`
- Existing tests still pass
- Add a test that verifies content with special characters (quotes, backslashes) round-trips through the database correctly
