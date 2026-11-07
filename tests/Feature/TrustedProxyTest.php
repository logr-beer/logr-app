<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test/proxy', fn () => response()->json([
            'secure' => request()->isSecure(),
            'url' => url('/dashboard'),
            'upload_url' => URL::temporarySignedRoute('livewire.upload-file', now()->addMinutes(5)),
        ]));
    }

    private function getThroughProxy(): array
    {
        return $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.2'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'logr.example.com',
                'X-Forwarded-Port' => '443',
            ])
            ->get('/_test/proxy')
            ->assertOk()
            ->json();
    }

    public function test_forwarded_https_is_honoured_by_default(): void
    {
        $data = $this->getThroughProxy();

        $this->assertTrue($data['secure']);
        $this->assertSame('https://logr.example.com/dashboard', $data['url']);
        $this->assertStringStartsWith('https://logr.example.com/livewire/upload-file?', $data['upload_url']);
    }

    public function test_forwarded_headers_from_untrusted_proxy_are_ignored(): void
    {
        config(['trustedproxy.proxies' => '10.0.0.1, 10.0.0.2']);

        $data = $this->getThroughProxy();

        $this->assertFalse($data['secure']);
        $this->assertStringStartsWith('http://', $data['url']);
        $this->assertStringNotContainsString('logr.example.com', $data['url']);
    }

    public function test_listed_proxy_is_trusted(): void
    {
        config(['trustedproxy.proxies' => '10.0.0.1,172.18.0.0/16']);

        $this->assertTrue($this->getThroughProxy()['secure']);
    }
}
