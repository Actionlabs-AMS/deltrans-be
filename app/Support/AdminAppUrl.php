<?php

namespace App\Support;

/**
 * Builds absolute URLs for the admin SPA (sign-in, activation, etc.).
 * Email clients require a full scheme + host; empty or invalid ADMIN_APP_URL
 * would otherwise produce relative paths like "/login" and broken redirects.
 */
class AdminAppUrl
{
    private const DEFAULT_APP_URL = 'http://localhost';

    /**
     * Resolved APP_URL: never empty — env may set APP_URL= which makes config() return ''
     * and would otherwise yield relative email links like "/login".
     */
    private static function resolvedAppUrl(): string
    {
        $url = config('app.url');
        if (! is_string($url) || trim($url) === '') {
            $url = self::DEFAULT_APP_URL;
        }
        $trimmed = rtrim($url, '/');
        if (! filter_var($trimmed, FILTER_VALIDATE_URL)) {
            return self::DEFAULT_APP_URL;
        }

        return $trimmed;
    }

    /**
     * Base URL of the admin frontend, no trailing slash.
     */
    public static function base(): string
    {
        $fallback = self::resolvedAppUrl();
        // Use config(), not env(), so values resolve when config is cached (php artisan config:cache).
        $candidates = [config('app.admin_app_url'), $fallback];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }
            $trimmed = rtrim(trim($candidate), '/');
            if (filter_var($trimmed, FILTER_VALIDATE_URL)) {
                return $trimmed;
            }
        }

        return $fallback;
    }

    /**
     * Absolute URL with path (leading slashes on $path are ignored).
     */
    public static function to(string $path = ''): string
    {
        $path = ltrim($path, '/');

        return $path === '' ? self::base() : self::base().'/'.$path;
    }
}
