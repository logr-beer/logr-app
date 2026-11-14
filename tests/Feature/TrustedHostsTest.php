<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Tests\TestCase;

class TrustedHostsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // TrustHosts is skipped while running unit tests, so run as production.
        $this->app['env'] = 'production';
    }

    protected function tearDown(): void
    {
        Request::setTrustedHosts([]);

        parent::tearDown();
    }

    public function test_foreign_host_is_rejected_when_trusted_hosts_are_set(): void
    {
        config(['app.trusted_hosts' => 'logr.example.com, other.example.com']);

        $this->get('http://evil.example.net/up')->assertBadRequest();
        $this->get('http://logr.example.com.evil.example.net/up')->assertBadRequest();
        $this->get('http://sub.logr.example.com/up')->assertBadRequest();
    }

    public function test_trusted_hosts_are_accepted(): void
    {
        config(['app.trusted_hosts' => 'logr.example.com, other.example.com']);

        $this->get('http://logr.example.com/up')->assertOk();
        $this->get('http://other.example.com/up')->assertOk();
    }

    public function test_any_host_is_accepted_when_trusted_hosts_are_unset(): void
    {
        config(['app.trusted_hosts' => null]);

        $this->get('http://anything.example.net/up')->assertOk();
    }
}
