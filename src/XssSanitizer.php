<?php

namespace Ace;

class XssSanitizer
{
    /**
     * Clean input value recursively from XSS vectors
     *
     * @param mixed $value Input value
     * @return mixed Cleaned value
     */
    public static function clean(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([self::class, 'clean'], $value);
        }

        if (!is_string($value)) {
            return $value;
        }

        // Remove null bytes
        $value = str_replace("\0", '', $value);

        // Remove script tags and their contents
        $value = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $value);

        // Remove inline event handlers (e.g. onload, onerror, onclick)
        $value = preg_replace('/on\w+\s*=\s*(["\'])(.*?)\1/is', '', $value);
        $value = preg_replace('/on\w+\s*=\s*([^\s>]+)/is', '', $value);

        // Remove javascript:, vbscript:, data: URIs
        $value = preg_replace('/href\s*=\s*(["\'])(?:javascript|vbscript|data):.*?\1/is', '', $value);
        $value = preg_replace('/src\s*=\s*(["\'])(?:javascript|vbscript|data):.*?\1/is', '', $value);

        // Remove style expressions
        $value = preg_replace('/expression\s*\((.*?)\)/is', '', $value);

        return $value;
    }
}

