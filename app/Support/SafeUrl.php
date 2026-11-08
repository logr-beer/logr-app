<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

class SafeUrl
{
    /** Maximum size of a downloaded image, in bytes. */
    public const MAX_IMAGE_BYTES = 10 * 1024 * 1024;

    protected const IMAGE_EXTENSIONS = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_GIF => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];

    protected static ?Closure $resolver = null;

    /**
     * Override DNS resolution (used by tests). Pass null to restore the default.
     */
    public static function resolveUsing(?Closure $resolver): void
    {
        static::$resolver = $resolver;
    }

    /**
     * An https URL whose host resolves only to public IP addresses.
     */
    public static function isPublicHttps(string $url): bool
    {
        $host = static::httpsHost($url);
        if ($host === null) {
            return false;
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : static::resolve($host);
        if (empty($ips)) {
            return false;
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_GLOBAL_RANGE)) {
                return false;
            }
        }

        return true;
    }

    /**
     * An https URL on one of the allow-listed image hosts (or a subdomain of one).
     */
    public static function isAllowedImageHost(string $url): bool
    {
        $host = static::httpsHost($url);
        if ($host === null) {
            return false;
        }

        foreach (config('logr.image_hosts', []) as $allowed) {
            $allowed = strtolower($allowed);
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Guzzle options that re-check every redirect target with the given callback.
     */
    public static function redirectOptions(Closure $isAllowed): array
    {
        return [
            'allow_redirects' => [
                'max' => 5,
                'protocols' => ['https'],
                'on_redirect' => function (RequestInterface $request, $response, UriInterface $uri) use ($isAllowed) {
                    if (! $isAllowed((string) $uri)) {
                        throw new RuntimeException('Redirect to a disallowed URL.');
                    }
                },
            ],
        ];
    }

    /**
     * Download an image from an allow-listed host. Returns the bytes and a file
     * extension matching the actual image type, or null if anything is off.
     *
     * @return array{body: string, extension: string}|null
     */
    public static function fetchImage(string $url, string $userAgent, int $timeout = 15): ?array
    {
        if (! static::isAllowedImageHost($url)) {
            return null;
        }

        $max = self::MAX_IMAGE_BYTES;

        $response = Http::withHeaders(['User-Agent' => $userAgent])
            ->timeout($timeout)
            ->withOptions(static::redirectOptions(fn (string $next) => static::isAllowedImageHost($next)) + [
                'progress' => function ($total, $downloaded) use ($max) {
                    if ($total > $max || $downloaded > $max) {
                        throw new RuntimeException('Image exceeds the size limit.');
                    }
                },
            ])
            ->get($url);

        if (! $response->successful()) {
            return null;
        }

        if (! str_starts_with(strtolower((string) $response->header('Content-Type')), 'image/')) {
            return null;
        }

        $body = $response->body();
        if ($body === '' || strlen($body) > $max) {
            return null;
        }

        $info = @getimagesizefromstring($body);
        if ($info === false || ! isset(self::IMAGE_EXTENSIONS[$info[2]])) {
            return null;
        }

        return ['body' => $body, 'extension' => self::IMAGE_EXTENSIONS[$info[2]]];
    }

    protected static function httpsHost(string $url): ?string
    {
        $parts = parse_url($url);
        if ($parts === false || strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            return null;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        return strtolower(trim($parts['host'], '[]'));
    }

    protected static function resolve(string $host): array
    {
        if (static::$resolver) {
            return (static::$resolver)($host);
        }

        $ips = gethostbynamel($host) ?: [];
        foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $record) {
            $ips[] = $record['ipv6'] ?? null;
        }

        return array_values(array_filter($ips));
    }
}
