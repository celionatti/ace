<?php

declare(strict_types=1);

if (!function_exists('config_path')) {
    function config_path(string $path = ''): string
    {
        return base_path('config' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return dirname(__DIR__) . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if(!function_exists('env')) {
    function env(string $key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }
}

if(!function_exists('pluralize')) {
    function pluralize($word)
    {
        // List of singular words that end in "s" but require "es" to form the plural.
        $singularExceptions = ['bus', 'kiss', 'class', 'glass', 'quiz'];

        // If the word ends with 's' and is not in the exceptions,
        // assume it's already plural.
        if (strtolower(substr($word, -1)) === 's' && !in_array(strtolower($word), $singularExceptions)) {
            return $word;
        }

        // If the word ends with a consonant followed by 'y', change 'y' to 'ies'
        if (preg_match('/([^aeiou])y$/i', $word)) {
            return preg_replace('/y$/i', 'ies', $word);
        }

        // If the word ends with s, x, z, ch, or sh, or is one of our singular exceptions, append 'es'
        if (preg_match('/(s|x|z|ch|sh)$/i', $word) || in_array(strtolower($word), $singularExceptions)) {
            return $word . 'es';
        }

        // Default rule: append 's'
        return $word . 's';
    }
}