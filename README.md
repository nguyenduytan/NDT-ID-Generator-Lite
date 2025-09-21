# NDT ID Generator — Lite

**Single-file**, dependency-free PHP library providing **UUID v4** and **UUID v7 (RFC 9562)**.  
Namespace: `ndtan` · PHP 8.1+

---

## Features
- ✅ UUID v4 (random)
- ✅ UUID v7 (time-ordered)
- ✅ One file only: `src/ID.php`
- ✅ No configuration · No dependencies
- ✅ PHPUnit tests + GitHub Actions CI

---

## Installation
```bash
composer require ndtan/id-generator-lite
```

---

## Quick Start
```php
<?php
require __DIR__.'/vendor/autoload.php';

use ndtan\ID;

$u4 = ID::uuid4(); // 4xxx-...
$u7 = ID::uuid7(); // 7xxx-...
```
**Tip:** UUID v7 is time-ordered → better for DB indexes and logs.

---

## API
```php
ID::uuid4(): string;
ID::uuid7(): string;
```

---

## Step-by-step: Create repo with GitHub Desktop

1. Open **GitHub Desktop** → **File → New repository…**
2. Name: `NDT-ID-Generator-Lite`  
   Description: *NDT ID Generator — Lite: a single-file, dependency-free PHP library providing UUID v4 and UUID v7 (RFC 9562).*
3. Choose local path → Create repository.
4. Add all files from this package into the repo folder.
5. Terminal:
   ```bash
   composer install
   composer test
   ```
6. Back to GitHub Desktop → **Commit** → **Publish repository**.
7. Create a release `v0.1.0`.

---

## Versioning
- Initial release: **v0.1.0**
- Only bugfixes/features for **uuid4/uuid7** (keep it minimal).

---

## License
MIT © Tony Nguyen
