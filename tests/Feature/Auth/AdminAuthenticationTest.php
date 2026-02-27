<?php

namespace Tests\Feature\Auth;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
