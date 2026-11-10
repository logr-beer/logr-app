<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_shows_github_link_with_running_version(): void
    {
        config(['logr.version' => '9.8.7']);

        $response = $this->actingAs(User::factory()->create())->get('/profile');

        $response->assertOk()
            ->assertSeeInOrder(['About Logr', 'v9.8.7', 'github.com/logr-beer/logr-app'])
            ->assertSee('href="https://github.com/logr-beer/logr-app"', false)
            ->assertSee('href="https://github.com/logr-beer/logr-app/releases/tag/v9.8.7"', false);
    }

    public function test_profile_about_section_is_shown_in_demo_mode(): void
    {
        config(['app.demo_mode' => true]);

        $this->actingAs(User::factory()->create())->get('/profile')
            ->assertOk()
            ->assertSee('About Logr')
            ->assertSee('href="https://github.com/logr-beer/logr-app"', false);
    }

    public function test_system_info_shows_version_next_to_github_link(): void
    {
        config(['logr.version' => '9.8.7']);

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/system')
            ->assertOk()
            ->assertSeeInOrder(['Source code &amp; issues', 'v9.8.7'], false);
    }
}
