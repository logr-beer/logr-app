<?php

namespace Tests\Feature;

use App\Livewire\Admin\SystemInfo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        // The first user created becomes the admin
        $this->admin = User::factory()->create();
        $this->member = User::factory()->create();
    }

    public static function adminComponents(): array
    {
        return [
            'system info' => [SystemInfo::class, 'retryFailedJobs'],
            'api settings' => ['profile.api-settings-form', 'save'],
            'notification settings' => ['profile.notification-settings-form', 'cancelChannelPicker'],
        ];
    }

    #[DataProvider('adminComponents')]
    public function test_non_admin_cannot_mount_admin_component(string $component): void
    {
        Livewire::actingAs($this->member)
            ->test($component)
            ->assertForbidden();
    }

    #[DataProvider('adminComponents')]
    public function test_demoted_admin_cannot_call_admin_component_actions(string $component, string $action): void
    {
        $test = Livewire::actingAs($this->admin)
            ->test($component)
            ->assertOk();

        $this->admin->forceFill(['is_admin' => false])->save();

        $test->call($action)->assertForbidden();
    }

    #[DataProvider('adminComponents')]
    public function test_admin_can_use_admin_component(string $component, string $action): void
    {
        Livewire::actingAs($this->admin)
            ->test($component)
            ->call($action)
            ->assertOk();
    }

    public function test_non_admin_cannot_reach_admin_routes(): void
    {
        $this->actingAs($this->member);

        $this->get(route('admin.api'))->assertForbidden();
        $this->get(route('admin.notifications'))->assertForbidden();
        $this->get(route('admin.system'))->assertForbidden();
    }

    public static function destructiveActions(): array
    {
        return [
            'purge data' => ['purgeData', 'purgeConfirmation'],
            'purge settings' => ['purgeSettings', 'purgeSettingsConfirmation'],
            'reset app' => ['resetApp', null],
        ];
    }

    #[DataProvider('destructiveActions')]
    public function test_destructive_actions_do_nothing_in_demo_mode(string $action, ?string $confirmationField): void
    {
        config(['app.demo_mode' => true]);
        Artisan::spy();

        $test = Livewire::actingAs($this->admin)->test(SystemInfo::class);

        if ($confirmationField) {
            $test->set($confirmationField, 'PURGE');
        }

        $test->call($action)->assertNoRedirect();

        Artisan::shouldNotHaveReceived('call');
        $this->assertAuthenticatedAs($this->admin);
    }
}
