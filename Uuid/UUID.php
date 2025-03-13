<?php

declare(strict_types=1);

/**
 * ================================
 * UUID ===========================
 * ================================
 */

namespace Ace\Uuid;

class UUID
{
    /**
     * Generate a MongoDB-like ID with "bv_" prefix
     */
    public static function generate(): string
    {
        // 4-byte timestamp (seconds since Unix epoch)
        $timestamp = pack('N', time());

        // 5-byte combination of machine/process identifiers
        $machinePid = substr(hash('sha1', gethostname() . php_uname('n'), true), 0, 5);

        // 3-byte counter/random value
        $random = random_bytes(3);

        // Combine all components
        $binary = $timestamp . $machinePid . $random;

        // Format with "bv_" prefix and hexadecimal encoding
        return 'ace_' . bin2hex($binary);
    }

    /**
     * Validate UUID format and structure
     */
    public static function validate(string $uuid): bool
    {
        // Basic format validation
        if (!preg_match('/^bv_[a-f0-9]{24}$/i', $uuid)) {
            return false;
        }

        // Extract timestamp for additional validation
        $hex = substr($uuid, 3, 8);
        $timestamp = unpack('N', hex2bin($hex))[1];

        // Validate timestamp range (1970-2106)
        return $timestamp > 0 && $timestamp < 0xFFFFFFFF;
    }

    /**
     * Parse UUID components
     */
    public static function parse(string $uuid): array
    {
        if (!self::validate($uuid)) {
            throw new \InvalidArgumentException('Invalid UUID format');
        }

        $binary = hex2bin(substr($uuid, 3));

        return [
            'timestamp' => unpack('N', substr($binary, 0, 4))[1],
            'machine_id' => bin2hex(substr($binary, 4, 5)),
            'random' => bin2hex(substr($binary, 9, 3))
        ];
    }

    /**
     * Generate ordered UUID (for database indexing benefits)
     */
    public static function orderedGenerate(): string
    {
        // 4-byte timestamp (seconds since epoch)
        $timestamp = pack('N', time());

        // 8-byte random data
        $random = random_bytes(8);

        // Combine and format
        return 'ace_' . bin2hex($timestamp . $random);
    }

    /**
     * Get timestamp from ordered UUID
     */
    public static function getTimestamp(string $uuid): int
    {
        if (!self::validate($uuid)) {
            throw new \InvalidArgumentException('Invalid UUID format');
        }

        $hex = substr($uuid, 3, 8);
        return unpack('N', hex2bin($hex))[1];
    }

    /**
     * Generate short ID (16 characters) for URLs
     */
    public static function shortGenerate(): string
    {
        // 8-byte timestamp (milliseconds) + 4-byte random
        $binary = pack('J', (int)(microtime(true) * 1000)) . random_bytes(4);
        return 'ace_' . bin2hex($binary);
    }
}