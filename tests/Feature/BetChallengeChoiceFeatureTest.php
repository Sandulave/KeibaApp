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
}
