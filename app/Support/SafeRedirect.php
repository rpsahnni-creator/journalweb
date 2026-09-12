<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SafeRedirect
{
    public static function intended(Request $request, string $fallback): RedirectResponse
    {
        $intended = $request->session()->pull('url.intended');

        return redirect()->to(self::target(is_string($intended) ? $intended : null, $fallback));
    }

    public static function target(?string $url, string $fallback): string
    {
        if ($url === null || $url === '') {
            return $fallback;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! str_contains($url, '\\')) {
            return $url;
        }

        $app = parse_url((string) config('app.url'));
        $target = parse_url($url);

        if (! is_array($app) || ! is_array($target) || ! isset($target['host'])) {
            return $fallback;
        }

        $appHost = strtolower((string) ($app['host'] ?? ''));
        $targetHost = strtolower((string) $target['host']);
        $targetScheme = strtolower((string) ($target['scheme'] ?? ''));

        if ($appHost === '' || $targetHost !== $appHost || ! in_array($targetScheme, ['http', 'https'], true)) {
            return $fallback;
        }

        return $url;
    }
}
