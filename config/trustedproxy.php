<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Logr is usually run behind a reverse proxy (Traefik, Caddy, nginx, ...)
    | that terminates HTTPS. Trusting it lets Laravel honour X-Forwarded-*
    | headers so generated URLs (including Livewire upload URLs) use https.
    |
    | "*" trusts the directly connecting client. Set a comma-separated list
    | of IPs / CIDR ranges to restrict which proxies are trusted.
    |
    */

    'proxies' => env('TRUSTED_PROXIES', '*'),
];
