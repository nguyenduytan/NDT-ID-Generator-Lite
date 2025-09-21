# Usage & Reference

## Methods
- `ID::uuid4(): string` — random-based UUID.
- `ID::uuid7(): string` — time-ordered UUID (RFC 9562).
- `ID::ulid(): string` — Crockford Base32 (26 chars), time-ordered.
- `ID::ulidMonotonic(): string` — ensures lexicographic monotonicity within same millisecond.
- `ID::nanoid(int $size = 21, ?string $alphabet = null): string` — short random ID.
- `ID::objectId(): string` — 24-hex Mongo-like ObjectId.
- `ID::shortUuid(?string $uuid = null): string` — Base58-encode a UUID (uses UUID v4 if omitted).
- `ID::ksuid(): string` — 27-char Base62 KSUID (time since 2014-05-13 + random).
- `ID::snowflake(?array $cfg = null): string` — Twitter-like 64-bit Snowflake. Returns string for 32-bit safety.

## Tips
- Prefer **UUID v7**, **ULID**, or **KSUID** for time-ordered DB keys.
- Use **NanoID** or **ShortUUID** for human/URL friendly slugs.
- **Snowflake** is stateful and uses a sequence per millisecond; configure `worker_id/datacenter_id` if you run many instances.
