<?php

namespace App\Middlewares;

class SecurityHeadersMiddleware extends BaseMiddleware
{
    /**
     * Inject standard security response headers to secure application pages
     */
    protected function run(): void
    {
        // 1. Prevent Clickjacking
        header('X-Frame-Options: SAMEORIGIN');

        // 2. Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');

        // 3. Prevent XSS attacks in legacy browsers
        header('X-XSS-Protection: 1; mode=block');

        // 4. Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // 5. Enable Strict Transport Security (HSTS) if HTTPS is enabled
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        // 6. Content Security Policy (CSP)
        // Restricts where scripts, styles, fonts, and images can be loaded from.
        // We whitelist popular CDNs and image hosts to avoid blocking legitimate resources.
        $cspHeader = "Content-Security-Policy: " .
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com https://code.jquery.com; " .
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://maxcdn.bootstrapcdn.com https://unpkg.com; " .
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://maxcdn.bootstrapcdn.com; " .
            "img-src 'self' data: https: blob:; " .
            "connect-src 'self'; " .
            "frame-ancestors 'none';";

        header($cspHeader);
    }
}

