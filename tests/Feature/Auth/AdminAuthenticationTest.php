<?php

namespace Tests\Feature\Auth;

use App\Models\AppSetting;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
    }

    public function test_admin_can_authenticate_using_admin_login_screen(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->post('/admin/login', [
            'name' => $admin->name,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('races.index', absolute: false));
    }

    public function test_non_admin_cannot_authenticate_using_admin_login_screen(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'name' => $user->name,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors('name');
    }

    public function test_admin_login_rejects_invalid_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'name' => $admin->name,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors('name');
    }

    public function test_admin_login_screen_is_available_during_maintenance_mode(): void
    {
        AppSetting::query()->updateOrCreate([
            'key' => 'maintenance_enabled',
        ], [
            'value' => '1',
        ]);

        $response = $this->get('/admin/login');

        $response->assertOk();
    }

    public function test_user_seeder_does_not_reset_existing_admin_password(): void
    {
        $admin = User::factory()->create([
            'name' => 'admin',
            'password' => Hash::make('custom-admin-password'),
            'role' => 'admin',
        ]);

        $this->seed(UserSeeder::class);

        $admin->refresh();

        $this->assertTrue(Hash::check('custom-admin-password', $admin->password));
        $this->assertFalse(Hash::check('password', $admin->password));
    }

    public function test_admin_password_command_updates_admin_password(): void
    {
        $admin = User::factory()->create([
            'name' => 'admin',
            'password' => Hash::make('old-admin-password'),
            'role' => 'admin',
        ]);

        $this->artisan('admin:password')
            ->expectsQuestion('New password', 'new-admin-password')
            ->expectsQuestion('Confirm password', 'new-admin-password')
            ->expectsOutput('Admin password updated for [admin].')
            ->assertExitCode(0);

        $admin->refresh();

        $this->assertTrue(Hash::check('new-admin-password', $admin->password));
        $this->assertFalse(Hash::check('old-admin-password', $admin->password));
    }

    public function test_admin_password_command_can_read_password_from_environment(): void
    {
        $admin = User::factory()->create([
            'name' => 'admin',
            'password' => Hash::make('old-admin-password'),
            'role' => 'admin',
        ]);

        putenv('ADMIN_PASSWORD_TEST=env-admin-password');
        $_ENV['ADMIN_PASSWORD_TEST'] = 'env-admin-password';
        $_SERVER['ADMIN_PASSWORD_TEST'] = 'env-admin-password';

        try {
            $this->artisan('admin:password --password-env=ADMIN_PASSWORD_TEST')
                ->expectsOutput('Admin password updated for [admin].')
                ->assertExitCode(0);
        } finally {
            putenv('ADMIN_PASSWORD_TEST');
            unset($_ENV['ADMIN_PASSWORD_TEST'], $_SERVER['ADMIN_PASSWORD_TEST']);
        }

        $admin->refresh();

        $this->assertTrue(Hash::check('env-admin-password', $admin->password));
        $this->assertFalse(Hash::check('old-admin-password', $admin->password));
    }
}
