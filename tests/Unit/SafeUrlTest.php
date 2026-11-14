<?php

namespace Tests\Unit;

use App\Support\SafeUrl;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SafeUrlTest extends TestCase
{
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    public function test_public_https_requires_https(): void
    {
        SafeUrl::resolveUsing(fn () => ['93.184.216.34']);

        $this->assertTrue(SafeUrl::isPublicHttps('https://untappd.com/rss/user/demo'));
        $this->assertFalse(SafeUrl::isPublicHttps('http://untappd.com/rss/user/demo'));
        $this->assertFalse(SafeUrl::isPublicHttps('ftp://untappd.com/feed'));
        $this->assertFalse(SafeUrl::isPublicHttps('not a url'));
    }

    public function test_public_https_rejects_internal_addresses(): void
    {
        foreach (['127.0.0.1', '10.0.0.5', '192.168.1.1', '169.254.169.254', '::1', 'fe80::1', '0.0.0.0'] as $ip) {
            SafeUrl::resolveUsing(fn () => [$ip]);
            $this->assertFalse(SafeUrl::isPublicHttps('https://feed.example/rss'), "Resolved to {$ip}");
        }

        $this->assertFalse(SafeUrl::isPublicHttps('https://127.0.0.1/rss'));
        $this->assertFalse(SafeUrl::isPublicHttps('https://[::1]/rss'));
    }

    public function test_public_https_rejects_if_any_resolved_address_is_internal(): void
    {
        SafeUrl::resolveUsing(fn () => ['93.184.216.34', '10.0.0.1']);

        $this->assertFalse(SafeUrl::isPublicHttps('https://feed.example/rss'));
    }

    public function test_public_https_rejects_unresolvable_hosts(): void
    {
        SafeUrl::resolveUsing(fn () => []);

        $this->assertFalse(SafeUrl::isPublicHttps('https://feed.example/rss'));
    }

    public function test_image_host_allow_list(): void
    {
        $this->assertTrue(SafeUrl::isAllowedImageHost('https://untappd.akamaized.net/photos/a.jpg'));
        $this->assertTrue(SafeUrl::isAllowedImageHost('https://assets.untappd.com/site/beer_logos/b.png'));
        $this->assertFalse(SafeUrl::isAllowedImageHost('http://untappd.akamaized.net/photos/a.jpg'));
        $this->assertFalse(SafeUrl::isAllowedImageHost('https://example.com/a.jpg'));
        $this->assertFalse(SafeUrl::isAllowedImageHost('https://untappd.akamaized.net.example.com/a.jpg'));
        $this->assertFalse(SafeUrl::isAllowedImageHost('https://user@example.com/a.jpg'));
    }

    public function test_fetch_image_returns_bytes_and_extension(): void
    {
        Http::fake(['untappd.akamaized.net/*' => Http::response(base64_decode(self::PNG), 200, ['Content-Type' => 'image/png'])]);

        $image = SafeUrl::fetchImage('https://untappd.akamaized.net/photos/a.png', 'test');

        $this->assertSame('png', $image['extension']);
        $this->assertSame(base64_decode(self::PNG), $image['body']);
    }

    public function test_fetch_image_rejects_non_image_content_type(): void
    {
        Http::fake(['untappd.akamaized.net/*' => Http::response('<html></html>', 200, ['Content-Type' => 'text/html'])]);

        $this->assertNull(SafeUrl::fetchImage('https://untappd.akamaized.net/photos/a.jpg', 'test'));
    }

    public function test_fetch_image_rejects_bytes_that_are_not_an_image(): void
    {
        Http::fake(['untappd.akamaized.net/*' => Http::response('{"secret":true}', 200, ['Content-Type' => 'image/jpeg'])]);

        $this->assertNull(SafeUrl::fetchImage('https://untappd.akamaized.net/photos/a.jpg', 'test'));
    }

    public function test_fetch_image_rejects_oversized_bodies(): void
    {
        $body = base64_decode(self::PNG).str_repeat("\0", SafeUrl::MAX_IMAGE_BYTES);
        Http::fake(['untappd.akamaized.net/*' => Http::response($body, 200, ['Content-Type' => 'image/png'])]);

        $this->assertNull(SafeUrl::fetchImage('https://untappd.akamaized.net/photos/a.png', 'test'));
    }

    public function test_fetch_image_does_not_request_disallowed_hosts(): void
    {
        Http::fake();

        $this->assertNull(SafeUrl::fetchImage('https://169.254.169.254/latest/meta-data', 'test'));
        Http::assertNothingSent();
    }
}
