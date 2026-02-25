# Rename MYSQL Class to SQLite

**Priority:** High
**Area:** Architecture / Clarity
**Files:** `core/src/MYSQL.php`, `core/src/DB.php`

## Problem

The `MYSQL` class wraps `SQLite3`, not MySQL. This is confusing and suggests an abandoned backend swap. The class name is misleading for anyone reading the code.

## Solution

- Rename `MYSQL.php` to `SQLite.php` (or `Database.php`)
- Rename the class from `MYSQL` to `SQLite` (or `Database`)
- Update `DB.php` which instantiates it
- Update any other references

## Acceptance Criteria

- Class and file renamed to accurately reflect SQLite usage
- All references updated
- PHPStan and tests pass
