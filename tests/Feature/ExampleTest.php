<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_redirects_to_setup_when_no_users_exist(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/setup');
    }

    public function test_the_application_returns_a_successful_response(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }
}
