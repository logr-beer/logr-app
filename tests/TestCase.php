<?php

namespace Tests;

use App\Support\SafeUrl;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        SafeUrl::resolveUsing(null);

        parent::tearDown();
    }
}
