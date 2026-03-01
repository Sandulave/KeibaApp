<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AudienceRoleSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_audience_role_is_redirected_to_selection_from_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'audience_role' => null,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/audience-role');
    }

    public function test_user_without_audience_role_is_redirected_to_selection_from_stats(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'audience_role' => null,
        ]);

        $response = $this->actingAs($user)->get('/stats');

        $response->assertRedirect('/audience-role');
    }

    public function test_user_can_select_audience_role_and_continue(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'audience_role' => null,
        ]);

        $response = $this->actingAs($user)->put('/audience-role', [
            'audience_role' => 'streamer',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertSame('streamer', $user->fresh()->audience_role);
    }

    public function test_admin_is_not_forced_to_select_audience_role(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'audience_role' => null,
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertRedirect('/races');
    }
}
