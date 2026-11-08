<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Content Security Policy, sent as report-only for now so violations show in the
     * browser console without breaking anything. Notes on the allowances:
     * - script-src 'unsafe-inline' 'unsafe-eval': Livewire/Alpine evaluate expressions from
     *   attributes and @script blocks are inline; analytics (GA/GTM) is optional.
     * - style-src 'unsafe-inline': Alpine x-show, Leaflet and inline style attributes.
     * - img-src https:: beer photos can be remote URLs; map tiles come from OpenStreetMap.
     */
    private const CONTENT_SECURITY_POLICY = "default-src 'self'; "
        ."script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com; "
        ."style-src 'self' 'unsafe-inline' https://fonts.bunny.net; "
        ."font-src 'self' data: https://fonts.bunny.net; "
        ."img-src 'self' data: blob: https:; "
        ."connect-src 'self' https://*.google-analytics.com https://*.googletagmanager.com; "
        ."object-src 'none'; "
        ."base-uri 'self'; "
        ."form-action 'self'; "
        ."frame-ancestors 'self'";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy-Report-Only' => self::CONTENT_SECURITY_POLICY,
        ];

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
