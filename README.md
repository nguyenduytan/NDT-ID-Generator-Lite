# NDT ID Generator — Lite

> Single‑file, dependency‑free PHP library for generating **UUID v4/v7**, **ULID**, **NanoID**, **Mongo ObjectId**, **ShortUUID (Base58)**, **KSUID (Base62)**, and **Snowflake**.  
> Namespace: `ndtan` · PHP 8.1+ · PSR‑4 autoload

<p align="left">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white">
  <img alt="License" src="https://img.shields.io/badge/License-MIT-green">
  <img alt="Type" src="https://img.shields.io/badge/Single%20file-ID%20Generators-blue">
</p>

---

## Table of Contents
- [Features](#features)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [API Reference](#api-reference)
- [Snowflake Configuration (optional)](#snowflake-configuration-optional)
- [Which ID should I use?](#which-id-should-i-use)
- [Notes & Guarantees](#notes--guarantees)
- [Testing](#testing)
- [Changelog](#changelog)
- [License](#license)

---

## Features
- ✅ **Single file** core: `src/ID.php`
- ✅ **No external dependencies** (pure PHP)
- ✅ **UUID v4** (random), **UUID v7** (time‑ordered; RFC 9562)
- ✅ **ULID** (Crockford Base32) + **monotonic** variant
- ✅ **NanoID** (URL‑safe; customizable length/alphabet)
- ✅ **Mongo ObjectId** (24 hex chars)
- ✅ **ShortUUID** (UUID → Base58)
- ✅ **KSUID** (27 chars, Base62, time‑ordered)
- ✅ **Snowflake** (64‑bit; string return for 32‑bit safety)
- ✅ Works with **any framework** (Composer autoload) and plain PHP

---

## Installation
```bash
composer require ndtan/id-generator-lite
```

> Requires PHP **8.1+** and the `random_bytes()` function available by default.

---

## Quick Start
```php
<?php
require __DIR__.'/vendor/autoload.php';

use ndtan\ID;

$u4  = ID::uuid4();           // 4xxx-...
$u7  = ID::uuid7();           // 7xxx-...
$ul  = ID::ulid();            // 26-char ULID
$ulm = ID::ulidMonotonic();   // ULID monotonic (same ms)
$na  = ID::nanoid();          // default 21 chars, URL-safe
$oid = ID::objectId();        // 24 hex (Mongo-like)
$suid= ID::shortUuid();       // UUID -> Base58 (short)
$ks  = ID::ksuid();           // 27 Base62, time-ordered
$sf  = ID::snowflake();       // 64-bit Snowflake (as string)
```

---

## API Reference

```php
// UUID
ID::uuid4(): string;
ID::uuid7(): string;

// ULID
ID::ulid(): string;
ID::ulidMonotonic(): string;

// NanoID
ID::nanoid(int $size = 21, ?string $alphabet = null): string;

// Mongo ObjectId
ID::objectId(): string; // 24 lowercase hex

// ShortUUID (Base58)
ID::shortUuid(?string $uuid = null): string; // if null, uses uuid4()

// KSUID (Base62)
ID::ksuid(): string; // 27 chars

// Snowflake (64-bit layout)
ID::configureSnowflake(array $opts): void; // optional, see below
ID::snowflake(?array $cfg = null): string; // returns string
```

---

## Snowflake Configuration (optional)
```php
// global, call once (e.g., at bootstrap)
ID::configureSnowflake([
  'epoch'         => '2020-01-01T00:00:00Z', // or epoch milliseconds
  'worker_id'     => 1,     // 0..31
  'datacenter_id' => 1      // 0..31
]);

// or per-call:
$sf = ID::snowflake(['worker_id' => 2]);
```

**Layout (Twitter‑like):** `timestamp(41) | datacenter(5) | worker(5) | sequence(12)`  
IDs are returned as **string** to remain safe on 32‑bit platforms.

---

## Which ID should I use?

| Use case | Recommended |
|---|---|
| Write‑heavy DB, good index locality | **UUID v7**, **ULID**, or **KSUID** |
| Human/URL‑friendly short slugs | **NanoID** or **ShortUUID(uuid7())** |
| Interop with Mongo ecosystem | **Mongo ObjectId** |
| Need sortable UUID but keep UUID format | **UUID v7** (or **ULID**) |
| Coordinated multi‑node generation | **Snowflake** (configure worker/datacenter) |

> Rule of thumb: pick **UUID v7** or **ULID** for most DB keys; **NanoID** for slugs; **Snowflake** when you control worker IDs across instances.

---

## Notes & Guarantees
- Uses **`random_bytes()`** for cryptographic randomness where applicable.
- **UUID v7**, **ULID**, **KSUID** are **time‑ordered**, improving B‑Tree locality.
- `ulidMonotonic()` bumps randomness if multiple IDs are generated within the **same millisecond**.
- `snowflake()` maintains an internal per‑ms **sequence**; returns string to avoid integer overflow.
- `shortUuid()` encodes the UUID bytes using **Base58** (no ambiguous characters).

---

## Testing
```bash
composer install
composer test
```
Includes PHPUnit tests & a GitHub Actions workflow.

---

## Changelog
See **CHANGELOG.md** for release notes.

---

## License
MIT © Tony Nguyen
