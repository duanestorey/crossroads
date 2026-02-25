# Enable SSL Verification in cURL Downloads

**Priority:** High
**Area:** Security
**Files:** `core/src/Utils.php`

## Problem

`Utils::curlDownloadFile()` disables SSL peer verification:

```php
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
```

This makes all HTTPS downloads — including the self-upgrade mechanism — vulnerable to man-in-the-middle attacks.

## Solution

Remove the `CURLOPT_SSL_VERIFYPEER` override (defaults to `true`). If there are environments with missing CA bundles, use `CURLOPT_CAINFO` to point to a known bundle rather than disabling verification entirely.

## Acceptance Criteria

- `CURLOPT_SSL_VERIFYPEER` set to `true` (or line removed)
- Upgrade and import commands still work over HTTPS
- Add a note in CONTRIBUTING.md if a CA bundle is needed for certain environments
