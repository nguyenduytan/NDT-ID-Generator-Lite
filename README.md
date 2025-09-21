# NDT ID Generator — Lite (v0.1.0)

**Single-file**, dependency-free PHP ID generator.  
Namespace: `ndtan` · PHP 8.1+

Supports:
- UUID **v4**, UUID **v7** (RFC 9562)
- **ULID** (+ **monotonic** variant)
- **NanoID**
- **Mongo ObjectId**
- **ShortUUID** (Base58-encoded UUID)
- **KSUID** (Base62)
- **Snowflake** (64-bit, returns string)

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

$u4  = ID::uuid4();
$u7  = ID::uuid7();
$ul  = ID::ulid();
$ulm = ID::ulidMonotonic();
$na  = ID::nanoid();          // default 21 chars
$oid = ID::objectId();        // 24 hex
$suid= ID::shortUuid();       // UUID -> Base58
$ks  = ID::ksuid();           // 27 Base62
$sf  = ID::snowflake();       // string
```

### Snowflake config (optional)
```php
ID::configureSnowflake([
  'epoch' => '2020-01-01T00:00:00Z', // or epoch ms
  'worker_id' => 1, 'datacenter_id' => 1
]);
// or per-call:
$sf = ID::snowflake(['worker_id'=>2]);
```

---

## Docs
See **docs/USAGE.md** for method-by-method details and tips.

---

## Testing
```bash
composer install
composer test
```

---

## License
MIT © Tony Nguyen
