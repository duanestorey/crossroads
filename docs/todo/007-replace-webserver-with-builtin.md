# Replace Hand-Rolled WebServer with PHP Built-in Server

**Priority:** Medium
**Area:** Simplification
**Files:** `core/src/WebServer.php`, `core/src/Engine.php`

## Problem

The `WebServer` class is a hand-rolled HTTP/1.1 server on raw PHP sockets. Issues:
- 8192-byte read limit truncates large requests
- Uses `\n` instead of `\r\n` for HTTP line endings (spec violation)
- Only handles GET
- Comment says "// 400 Ok" but sends 404
- Missing MIME types (e.g., SVG)
- No concurrent request handling

## Solution

Replace with PHP's built-in dev server:

```php
$command = sprintf('php -S 127.0.0.1:%d -t %s', $port, CROSSROADS_PUBLIC_DIR);
passthru($command);
```

This gives correct HTTP semantics, all MIME types, concurrent connections, and zero maintenance.

## Acceptance Criteria

- `php crossroads serve` uses PHP's built-in server
- WebServer.php removed or reduced to a thin wrapper
- Serve command still works with configurable port
