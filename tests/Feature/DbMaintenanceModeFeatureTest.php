<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DbMaintenanceModeFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available_when_maintenance_is_off(): void
    {
        AppSetting::query()->updateOrCreate([
            'key' => 'maintenance_enabled',
        ], [
            'value' => '0',
        ]);

        $response = $this->get('/login');

        $response->assertOk();
    }

    public function test_login_page_returns_503_when_maintenance_is_on(): void
    {
        AppSetting::query()->updateOrCreate([
            'key' => 'maintenance_enabled',
        ], [
            'value' => '1',
        ]);

        $response = $this->get('/login');

        $response->assertStatus(503);
    }

    public function test_non_admin_user_gets_503_when_maintenance_is_on(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        AppSetting::query()->updateOrCreate([
            'key' => 'maintenance_enabled',
        ], [
            'value' => '1',
        ]);

        AppSetting::query()->updateOrCreate([
            'key' => 'maintenance_message',
        ], [
            'value' => 'DB切替でメンテ中',
        ]);

        $response = $this->actingAs($user)->get('/bet');

        $response->assertStatus(503);
        $response->assertSee('ただいまメンテナンス中です');
        $response->assertSee('DB切替でメンテ中');
    }

    public function test_admin_user_can_access_even_when_maintenance_is_on(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        AppSetting::query()->updateOrCreate([
            'key' => 'maintenance_enabled',
        ], [
            'value' => '1',
        ]);

        $response = $this->actingAs($admin)->get('/admin/maintenance');

        $response->assertOk();
    }
}
