<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashToastTest extends TestCase
{
    use RefreshDatabase;

    public function test_flash_message_toast_fires_after_wire_navigate(): void
    {
        // DOMContentLoaded never fires after a wire:navigate redirect, so the toast must hook livewire:navigated
        $response = $this->actingAs(User::factory()->create())
            ->withSession(['message' => 'Check-in recorded!'])
            ->get('/dashboard');

        $response->assertOk()
            ->assertSee("document.addEventListener('livewire:navigated'", false)
            ->assertSee('"Check-in recorded!"', false)
            ->assertDontSee("addEventListener('DOMContentLoaded'", false);
    }

    public function test_flash_error_toast_fires_after_wire_navigate(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->withSession(['error' => 'Something broke'])
            ->get('/dashboard');

        $response->assertOk()
            ->assertSee("document.addEventListener('livewire:navigated'", false)
            ->assertSee('"Something broke"', false)
            ->assertSee("type: 'error'", false);
    }
}
