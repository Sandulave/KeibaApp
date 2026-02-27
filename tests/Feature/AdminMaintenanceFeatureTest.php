<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMaintenanceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_maintenance_setting_screen(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.maintenance.edit'));

        $response->assertOk();
        $response->assertSee('メンテナンス設定');
    }

    public function test_non_admin_cannot_open_maintenance_setting_screen(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get(route('admin.maintenance.edit'));

        $response->assertForbidden();
    }

    public function test_admin_can_update_maintenance_setting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put(route('admin.maintenance.update'), [
            'enabled' => '1',
            'message' => '管理画面からON',
        ]);

        $response->assertRedirect(route('admin.maintenance.edit'));
        $this->assertSame('1', AppSetting::query()->where('key', 'maintenance_enabled')->value('value'));
        $this->assertSame('管理画面からON', AppSetting::query()->where('key', 'maintenance_message')->value('value'));
    }
}
