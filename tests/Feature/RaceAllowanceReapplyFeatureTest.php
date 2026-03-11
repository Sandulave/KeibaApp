<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceUserAdjustment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RaceAllowanceReapplyFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reapply_allowances_to_selected_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $race = Race::create([
            'name' => '再適用テスト',
            'race_date' => '2026-03-10',
            'course' => '中山',
            'horse_count' => 18,
            'normal_allowance' => 12000,
            'challenge_allowance' => 45000,
        ]);

        $userChallenge = User::factory()->create([
            'role' => 'user',
            'current_balance' => 30000,
        ]);
        $userNormal = User::factory()->create([
            'role' => 'user',
            'current_balance' => 10000,
        ]);

        RaceUserAdjustment::create([
            'user_id' => $userChallenge->id,
            'race_id' => $race->id,
            'bonus_points' => 0,
            'challenge_choice' => 'challenge',
            'granted_allowance' => 30000,
            'challenge_chosen_at' => now(),
        ]);
        RaceUserAdjustment::create([
            'user_id' => $userNormal->id,
            'race_id' => $race->id,
            'bonus_points' => 0,
            'challenge_choice' => 'normal',
            'granted_allowance' => 10000,
            'challenge_chosen_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('races.edit', $race))
            ->post(route('races.allowances.reapply', $race));

        $response->assertRedirect(route('races.edit', $race));

        $this->assertDatabaseHas('race_user_adjustments', [
            'user_id' => $userChallenge->id,
            'race_id' => $race->id,
            'granted_allowance' => 45000,
        ]);
        $this->assertDatabaseHas('race_user_adjustments', [
            'user_id' => $userNormal->id,
            'race_id' => $race->id,
            'granted_allowance' => 12000,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $userChallenge->id,
            'current_balance' => 45000,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $userNormal->id,
            'current_balance' => 12000,
        ]);
    }
}
