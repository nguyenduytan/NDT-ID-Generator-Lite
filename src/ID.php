<?php
declare(strict_types=1);

namespace ndtan;

/**
 * NDT ID Generator — Lite+ (single file, no external deps)
 * 
 * Provides simple static methods:
 *   - ID::uuid4()                      // RFC 4122 v4 (random)
 *   - ID::uuid7()                      // RFC 9562 v7 (time-ordered)
 *   - ID::ulid()                       // 26-char ULID (Crockford Base32)
 *   - ID::ulidMonotonic()              // ULID with monotonic bump in same ms
 *   - ID::nanoid(int $size=21, ?string $alphabet=null)
 *   - ID::objectId()                   // 24-char hex (Mongo-like ObjectId)
 *   - ID::shortUuid(?string $uuid=null)// Base58-encoded UUID (v4 by default)
 *   - ID::ksuid()                      // 27-char Base62 KSUID (time-ordered)
 *   - ID::snowflake(?array $cfg=null)  // 64-bit Snowflake, returns string
 * 
 * Optional configuration for Snowflake:
 *   ID::configureSnowflake([
 *     'epoch'        => '2020-01-01T00:00:00Z'|int, // ms since 1970 or ISO8601
 *     'worker_id'    => 1,   // 0..31
 *     'datacenter_id'=> 1    // 0..31
 *   ]);
 * 
 * PHP 8.1+
 * MIT License © 2025 Tony Nguyen
 */
final class ID
{
    /* ============================ UUID v4 ============================ */
    public static function uuid4(): string
    {
        $b = random_bytes(16);
        // version 4
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        // RFC 4122 variant
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return self::fmtUuid($b);
    }

    /* ============================ UUID v7 ============================ */
    public static function uuid7(): string
    {
        $ts = (int) floor(microtime(true) * 1000); // 48-bit unix ms
        $b = random_bytes(16);

        // timestamp big-endian into bytes[0..5]
        $b[0] = chr(($ts >> 40) & 0xff);
        $b[1] = chr(($ts >> 32) & 0xff);
        $b[2] = chr(($ts >> 24) & 0xff);
        $b[3] = chr(($ts >> 16) & 0xff);
        $b[4] = chr(($ts >>  8) & 0xff);
        $b[5] = chr(($ts >>  0) & 0xff);

        // version 7
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x70);
        // RFC 4122 variant
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);

        return self::fmtUuid($b);
    }

    private static function fmtUuid(string $bytes16): string
    {
        $h = bin2hex($bytes16);
        return sprintf('%s-%s-%s-%s-%s',
            substr($h,0,8), substr($h,8,4), substr($h,12,4),
            substr($h,16,4), substr($h,20,12)
        );
    }

    /* ============================ ULID ============================ */
    private const ULID_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public static function ulid(): string
    {
        $time = (int) floor(microtime(true) * 1000);
        $timePart = self::ulidEncInt($time, 10);
        $rand80 = substr(random_bytes(16), 0, 10);
        $randPart = self::ulidEncBytes($rand80, 16);
        return $timePart . $randPart;
    }

    private static int $ulidLastTime = -1;
    private static string $ulidLastRand = '';

    public static function ulidMonotonic(): string
    {
        $time = (int) floor(microtime(true) * 1000);
        $timePart = self::ulidEncInt($time, 10);
        $rand80 = substr(random_bytes(16), 0, 10);
        if ($time === self::$ulidLastTime) {
            // bump last random to keep lexical ordering
            $rand80 = self::bumpBytes(self::$ulidLastRand ?: $rand80);
        }
        self::$ulidLastTime = $time;
        self::$ulidLastRand = $rand80;
        $randPart = self::ulidEncBytes($rand80, 16);
        return $timePart . $randPart;
    }

    private static function ulidEncInt(int $v, int $len): string
    {
        $res='';
        for($i=0;$i<$len;$i++){
            $res = self::ULID_ALPHABET[$v % 32] . $res;
            $v = intdiv($v, 32);
        }
        return $res;
    }

    private static function ulidEncBytes(string $bytes, int $length): string
    {
        $bits='';
        foreach (str_split($bytes) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $out='';
        for ($i=0;$i<$length;$i++) {
            $chunk = substr($bits, $i*5, 5);
            if ($chunk === '') $chunk = '00000';
            $out .= self::ULID_ALPHABET[bindec($chunk)];
        }
        return $out;
    }

    private static function bumpBytes(string $bytes): string
    {
        $arr = array_values(unpack('C*', $bytes));
        for ($i = count($arr)-1; $i >= 0; $i--) {
            $arr[$i] = ($arr[$i] + 1) & 0xff;
            if ($arr[$i] !== 0) break;
        }
        return pack('C*', ...$arr);
    }

    /* ============================ NanoID ============================ */
    public static function nanoid(int $size = 21, ?string $alphabet = null): string
    {
        $alphabet = $alphabet ?? '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz-';
        $mask = (2 << (int)floor(log(strlen($alphabet) - 1, 2))) - 1;
        $step = (int)ceil(1.6 * $mask * $size / strlen($alphabet));

        $id = '';
        while (strlen($id) < $size) {
            $bytes = random_bytes($step);
            $len = strlen($bytes);
            for ($i = 0; $i < $len; $i++) {
                $idx = ord($bytes[$i]) & $mask;
                if (isset($alphabet[$idx])) {
                    $id .= $alphabet[$idx];
                    if (strlen($id) === $size) break 2;
                }
            }
        }
        return $id;
    }

    /* ============================ Mongo ObjectId ============================ */
    private static int $oidCounter = -1;

    public static function objectId(): string
    {
        $time = pack('N', time()); // 4 bytes seconds
        $host = gethostname();
        if ($host === false || $host === '') { $host = uniqid('', true); }
        $machine = substr(md5($host), 0, 6); // 3 bytes hex
        $pid = getmypid();
        if (!is_int($pid)) { $pid = random_int(0, 0xffff); }
        $pid2 = pack('n', $pid & 0xffff); // 2 bytes
        if (self::$oidCounter < 0) {
            self::$oidCounter = random_int(0, 0xffffff);
        }
        self::$oidCounter = (self::$oidCounter + 1) % 0x1000000;
        $cnt = pack('N', self::$oidCounter);
        $bin = $time . hex2bin($machine) . $pid2 . substr($cnt, 1); // 12 bytes
        return bin2hex($bin); // 24 hex chars
    }

    /* ============================ ShortUUID (Base58(UUID)) ============================ */
    public static function shortUuid(?string $uuid = null): string
    {
        if (!is_string($uuid) || $uuid === '') {
            $uuid = self::uuid4();
        }
        $hex = str_replace('-', '', $uuid);
        $bin = hex2bin($hex);
        if ($bin === false) {
            throw new \InvalidArgumentException('Invalid UUID string');
        }
        return self::base58Encode($bin);
    }

    private static function base58Encode(string $bin): string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        return self::baseXEncode($bin, $alphabet);
    }

    private static function base62Encode(string $bin): string
    {
        $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        return self::baseXEncode($bin, $alphabet);
    }

    /**
     * Base-N encode without GMP/BCMath. Works on arbitrary binary using
     * repeated division in base-256.
     */
    private static function baseXEncode(string $bin, string $alphabet): string
    {
        if ($bin === '') return '';

        $bytes = array_values(unpack('C*', $bin)); // big-endian
        $base = strlen($alphabet);

        // Count leading zero bytes -> leading alphabet[0] chars
        $zeros = 0;
        for ($i=0; $i<count($bytes) && $bytes[$i] === 0; $i++) { $zeros++; }
        $encoded = str_repeat($alphabet[0], $zeros);

        $start = $zeros;
        $digits = array_slice($bytes, $start);

        $out = '';
        while (count($digits)) {
            $remainder = 0;
            $newDigits = [];
            foreach ($digits as $d) {
                $acc = ($remainder << 8) + $d; // remainder*256 + d
                $q = intdiv($acc, $base);
                $remainder = $acc % $base;
                if (count($newDigits) || $q !== 0) {
                    $newDigits.append($q);
                }
            }
            $out .= $alphabet[$remainder];
            $digits = $newDigits;
        }
        return $encoded . strrev($out);
    }

    /* ============================ KSUID (Base62) ============================ */
    public static function ksuid(): string
    {
        // KSUID = 20 bytes: 4 bytes time (secs since custom epoch) + 16 bytes random
        // Commonly used epoch around 2014-05-13
        $KSUID_EPOCH = 1400000000; // seconds
        $t = time() - $KSUID_EPOCH;
        if ($t < 0) { $t = 0; }
        $time = pack('N', $t & 0xffffffff);
        $payload = $time . random_bytes(16);
        // 20 bytes -> 27 base62 characters
        // Left-pad to 27 if needed (rare for small t)
        $b62 = self::base62Encode($payload);
        return str_pad($b62, 27, '0', STR_PAD_LEFT);
    }

    /* ============================ Snowflake ============================ */
    private static int $sfEpoch = 1577836800000; // 2020-01-01T00:00:00Z in ms
    private static int $sfWorker = 1;
    private static int $sfDatacenter = 1;
    private static int $sfSeq = 0;
    private static int $sfLast = -1;

    /**
     * Optional one-time configuration for Snowflake.
     * @param array{epoch?:int|string,worker_id?:int,datacenter_id?:int} $opts
     */
    public static function configureSnowflake(array $opts): void
    {
        if (isset($opts['epoch'])) {
            self::$sfEpoch = is_int($opts['epoch']) ? $opts['epoch'] : self::toEpochMs((string)$opts['epoch']);
        }
        if (isset($opts['worker_id'])) {
            $w = (int)$opts['worker_id']; if ($w < 0 || $w > 31) throw new \InvalidArgumentException('worker_id must be 0..31');
            self::$sfWorker = $w;
        }
        if (isset($opts['datacenter_id'])) {
            $d = (int)$opts['datacenter_id']; if ($d < 0 || $d > 31) throw new \InvalidArgumentException('datacenter_id must be 0..31');
            self::$sfDatacenter = $d;
        }
    }

    /**
     * Generate a Snowflake ID (64-bit layout). Returns string for 32-bit safety.
     * You can pass config inline once: ID::snowflake(['worker_id'=>2])
     * @param array{epoch?:int|string,worker_id?:int,datacenter_id?:int}|null $cfg
     */
    public static function snowflake(?array $cfg = null): string
    {
        if (is_array($cfg)) {
            self::configureSnowflake($cfg);
        }
        $ts = self::nowMs();
        if ($ts < self::$sfLast) {
            $ts = self::waitUntil(self::$sfLast);
        }
        if ($ts === self::$sfLast) {
            self::$sfSeq = (self::$sfSeq + 1) & 0xFFF;
            if (self::$sfSeq === 0) {
                $ts = self::waitUntil(self::$sfLast + 1);
            }
        } else {
            self::$sfSeq = 0;
        }
        self::$sfLast = $ts;

        $timestamp = ($ts - self::$sfEpoch) & 0x1FFFFFFFFFF; // 41 bits
        $id = ($timestamp << 22)
            | ((self::$sfDatacenter & 0x1F) << 17)
            | ((self::$sfWorker & 0x1F) << 12)
            | (self::$sfSeq & 0xFFF);

        return (string)$id;
    }

    private static function nowMs(): int { return (int) floor(microtime(true) * 1000); }
    private static function waitUntil(int $target): int { $t=self::nowMs(); while ($t < $target) { usleep(1000); $t=self::nowMs(); } return $t; }

    private static function toEpochMs(string $v): int
    {
        if (ctype_digit($v)) return (int)$v;
        $ts = strtotime($v);
        if ($ts === false) throw new \InvalidArgumentException('Invalid epoch format');
        return $ts * 1000;
    }
}
