# Fix Taxonomy SQL Schema

**Priority:** High
**Area:** Correctness
**Files:** `core/schemas/taxonomy.sql`, `core/src/MYSQL.php`

## Problem

`taxonomy.sql` line 8 has a typo: `FOREIGH` instead of `FOREIGN`, and the full `KEY (content_id) REFERENCES content(id)` clause is missing. SQLite silently ignores the malformed constraint, so there is no referential integrity between taxonomy and content rows.

Also, SQLite does not enforce foreign keys by default — `PRAGMA foreign_keys = ON` must be set per connection.

## Solution

1. Fix the schema: `"content_id" INTEGER FOREIGN KEY REFERENCES content(id)`
2. Add `PRAGMA foreign_keys = ON` in `MYSQL::__construct()` (or its renamed successor)
3. Verify existing data doesn't have orphaned taxonomy rows

## Acceptance Criteria

- Schema has correct foreign key syntax
- Foreign key enforcement enabled on connection
- Add a test verifying the schema creates successfully
