<?php

declare(strict_types=1);

if(!function_exists('assets')) {
    function asset($path) {
        return '/assets/' . $path;
    }
}

if (!function_exists('config_path')) {
    function config_path(string $path = ''): string
    {
        return base_path('config' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return dirname(__DIR__, 3) . ($path ? DIRECTORY_SEPARATOR . $path : $path);
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

if(!function_exists('singularize')) {
    function singularize($word)
    {
        // If the word ends with 'ies', change to 'y'
        if (preg_match('/([^aeiou])ies$/i', $word)) {
            return preg_replace('/ies$/i', 'y', $word);
        }

        // If the word ends with 'es', check for special cases
        if (preg_match('/es$/i', $word)) {
            // Words ending with sh, ch, s, x, z
            if (preg_match('/(sh|ch|s|x|z)es$/i', $word)) {
                return preg_replace('/es$/i', '', $word);
            }
            // Default case for 'es' endings
            return preg_replace('/es$/i', '', $word);
        }

        // Default rule: remove trailing 's' if it exists
        if (preg_match('/s$/i', $word) && !preg_match('/(ss|us)$/i', $word)) {
            return preg_replace('/s$/i', '', $word);
        }

        return $word;
    }
}

if(!function_exists('extract_links')) {
    function extract_links(string $links): array
    {
        // Split the comma-separated links
        $linkArray = array_map('trim', explode(',', $links));

        $result = [
            'tiktok' => null,
            'x' => null,
            'instagram' => null,
            'facebook' => null,
            'others' => [],
        ];

        foreach ($linkArray as $url) {
            if (stripos($url, 'tiktok.com') !== false) {
                $result['tiktok'] = $url;
            } elseif (stripos($url, 'x.com') !== false || stripos($url, 'twitter.com') !== false) {
                $result['x'] = $url;
            } elseif (stripos($url, 'instagram.com') !== false) {
                $result['instagram'] = $url;
            } elseif (stripos($url, 'facebook.com') !== false) {
                $result['facebook'] = $url;
            } else {
                $result['others'][] = $url;
            }
        }

        return $result;
    }
}
