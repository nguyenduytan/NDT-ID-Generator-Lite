<?php
declare(strict_types=1);

namespace ndtan;

/**
 * NDT ID Generator — Lite
 * Single-file, dependency-free. Basic UUID only (v4 & v7).
 * PHP 8.1+
 */
final class ID
{
    /** UUID v4 (random) */
    public static function uuid4(): string
    {
        $b = random_bytes(16);
        // version 4
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        // RFC 4122 variant
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        $h = bin2hex($b);
        return sprintf('%s-%s-%s-%s-%s',
            substr($h,0,8), substr($h,8,4), substr($h,12,4),
            substr($h,16,4), substr($h,20,12)
        );
    }

    /** UUID v7 (time-ordered, RFC 9562) */
    public static function uuid7(): string
    {
        $ts = (int) floor(microtime(true) * 1000); // 48-bit unix ms
        $b = random_bytes(16);

        // 48-bit timestamp big-endian into bytes[0..5]
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

        $h = bin2hex($b);
        return sprintf('%s-%s-%s-%s-%s',
            substr($h,0,8), substr($h,8,4), substr($h,12,4),
            substr($h,16,4), substr($h,20,12)
        );
    }
}
