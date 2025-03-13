<?php

declare(strict_types=1);

namespace Ace\Helpers;

class Str
{
    public static function plural(string $value) {
        // Basic pluralization, consider using a better implementation
        if (str_ends_with($value, 'y')) {
            return substr($value, 0, -1) . 'ies';
        }
        return $value . 's';
    }

    public static function snakeCase(string $value) {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }
}