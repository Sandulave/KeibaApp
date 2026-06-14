<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BetChallengeChoiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function createRace(): Race
    {
        return Race::create([
            'name' => 'G1テスト',
            'race_date' => '2026-05-10',
            'course' => '東京',
            'horse_count' => 18,
        ]);
    }

    private function createRaceWithAllowances(int $normalAllowance, int $challengeAllowance): Race
    {
        return Race::create([
            'name' => '配布金額テスト',
            'race_date' => '2026-05-11',
            'course' => '東京',
            'horse_count' => 18,
            'normal_allowance' => $normalAllowance,
            'challenge_allowance' => $challengeAllowance,
        ]);
    }

    public function test_unselected_user_is_redirected_to_challenge_select_from_types(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $race = $this->createRace();

        $response = $this->actingAs($user)->get(route('bet.types', $race));

        $response->assertRedirect(route('bet.challenge.select', $race));
    }

    public function test_user_can_select_challenge_choice_once_and_proceed(): void
    {
        $user = User::factory()->create(['role' => 'user', 'current_balance' => 0]);
        $race = $this->createRace();

        $this->actingAs($user)
            ->post(route('bet.challenge.store', $race), [
                'challenge_choice' => 'challenge',
            ])
            ->assertRedirect(route('bet.types', $race));

        $this->assertDatabaseHas('race_user_adjustments', [
            'user_id' => $user->id,
            'race_id' => $race->id,
            'challenge_choice' => 'challenge',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_balance' => 30000,
        ]);

        $this->actingAs($user)
            ->post(route('bet.challenge.store', $race), [
                'challenge_choice' => 'normal',
            ])
            ->assertRedirect(route('bet.types', $race));

        $this->assertDatabaseHas('race_user_adjustments', [
            'user_id' => $user->id,
            'race_id' => $race->id,
            'challenge_choice' => 'challenge',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_balance' => 30000,
        ]);
    }

    public function test_normal_choice_adds_10000_to_current_balance(): void
    {
        $user = User::factory()->create(['role' => 'user', 'current_balance' => 1500]);
        $race = $this->createRace();

        $this->actingAs($user)
            ->post(route('bet.challenge.store', $race), [
                'challenge_choice' => 'normal',
            ])
            ->assertRedirect(route('bet.types', $race));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_balance' => 11500,
        ]);
    }

    public function test_challenge_choice_uses_race_allowance_amount(): void
    {
        $user = User::factory()->create(['role' => 'user', 'current_balance' => 1000]);
        $race = $this->createRaceWithAllowances(12000, 42000);

        $this->actingAs($user)
            ->post(route('bet.challenge.store', $race), [
                'challenge_choice' => 'challenge',
            ])
            ->assertRedirect(route('bet.types', $race));

        $this->assertDatabaseHas('race_user_adjustments', [
            'user_id' => $user->id,
            'race_id' => $race->id,
            'challenge_choice' => 'challenge',
            'granted_allowance' => 42000,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_balance' => 43000,
        ]);
    }

    public function test_summer_site_auto_grants_g3_allowance_without_challenge_selection(): void
    {
        config(['domain.site.type' => 'summer']);

        $user = User::factory()->create(['role' => 'user', 'current_balance' => 700]);
        $race = Race::create([
            'name' => '函館スプリントS（G3）',
            'race_date' => '2026-06-13',
            'course' => '函館',
            'horse_count' => 18,
        ]);

        $this->actingAs($user)
            ->get(route('bet.types', $race))
            ->assertOk();

        $this->assertDatabaseHas('race_user_adjustments', [
            'user_id' => $user->id,
            'race_id' => $race->id,
            'challenge_choice' => 'normal',
            'granted_allowance' => 6000,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_balance' => 6700,
        ]);

        $this->actingAs($user)
            ->get(route('bet.types', $race))
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_balance' => 6700,
        ]);
    }

    public function test_summer_site_auto_grants_g2_allowance_from_challenge_url(): void
    {
        config(['domain.site.type' => 'summer']);

        $user = User::factory()->create(['role' => 'user', 'current_balance' => 1000]);
        $race = Race::create([
            'name' => '札幌記念（G2）',
            'race_date' => '2026-08-16',
            'course' => '札幌',
            'horse_count' => 18,
        ]);

        $this->actingAs($user)
            ->get(route('bet.challenge.select', $race))
            ->assertRedirect(route('bet.types', $race));

        $this->assertDatabaseHas('race_user_adjustments', [
            'user_id' => $user->id,
            'race_id' => $race->id,
            'challenge_choice' => 'normal',
            'granted_allowance' => 10000,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_balance' => 11000,
        ]);
    }
}
