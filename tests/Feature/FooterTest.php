<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FooterTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_footer_shows_copyright_with_current_year(): void
    {
        Carbon::setTestNow('2031-06-15 12:00:00');

        $response = $this->actingAs(User::factory()->create())->get('/dashboard');

        $response->assertOk()
            ->assertSee('&copy; 2031 Logr. All Rights Reserved.', false);
    }

    public function test_footer_has_a_single_copyright_line(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/dashboard');

        $this->assertSame(1, substr_count($response->getContent(), 'All Rights Reserved.'));
    }
}
