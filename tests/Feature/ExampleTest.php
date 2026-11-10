<?php

namespace Tests\Feature;

use App\Models\User;
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

    public function test_setup_redirect_keeps_the_host_port_from_the_request(): void
    {
        // Docker publishes the app on arbitrary host ports; redirects must not drop them.
        $response = $this->get('http://127.0.0.1:18337/login');

        $response->assertRedirect('http://127.0.0.1:18337/setup');
    }

    public function test_the_application_returns_a_successful_response(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }
}
