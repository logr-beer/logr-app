<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response
            ->assertOk()
            ->assertSeeVolt('profile.update-profile-information-form')
            ->assertSeeVolt('profile.update-password-form')
            ->assertSeeVolt('profile.delete-user-form');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.update-profile-information-form')
            ->set('name', 'Test User')
            ->set('username', 'testuser')
            ->call('updateProfileInformation');

        $component
            ->assertHasNoErrors()
            ->assertNoRedirect();

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('testuser', $user->username);
    }

    public function test_user_can_delete_their_account(): void
    {
        User::factory()->create(); // first user becomes the admin
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_sole_admin_cannot_delete_their_account(): void
    {
        $admin = User::factory()->create();
        User::factory()->create();
        $this->assertTrue($admin->fresh()->is_admin);

        $this->actingAs($admin);

        Volt::test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser')
            ->assertHasErrors('account')
            ->assertNoRedirect();

        $this->assertNotNull($admin->fresh());
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_can_delete_their_account_when_another_admin_exists(): void
    {
        $admin = User::factory()->create();
        $other = User::factory()->create();
        $other->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin);

        Volt::test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($admin->fresh());
    }

    public function test_last_user_cannot_delete_their_account(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => false])->save();

        $this->actingAs($user);

        Volt::test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser')
            ->assertHasErrors('account')
            ->assertNoRedirect();

        $this->assertNotNull($user->fresh());
    }

    public function test_account_cannot_be_deleted_in_demo_mode(): void
    {
        User::factory()->create();
        $user = User::factory()->create();
        config(['app.demo_mode' => true]);

        $this->actingAs($user);

        Volt::test('profile.delete-user-form')
            ->set('password', 'password')
            ->call('deleteUser')
            ->assertForbidden();

        $this->assertNotNull($user->fresh());
    }

    public function test_make_admin_command_promotes_a_user(): void
    {
        User::factory()->create();
        $user = User::factory()->create(['username' => 'second']);
        $this->assertFalse($user->fresh()->is_admin);

        $this->artisan('user:make-admin', ['username' => 'second'])->assertSuccessful();
        $this->assertTrue($user->fresh()->is_admin);

        $this->artisan('user:make-admin', ['username' => 'missing'])->assertFailed();
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('profile.delete-user-form')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $component
            ->assertHasErrors('password')
            ->assertNoRedirect();

        $this->assertNotNull($user->fresh());
    }
}
