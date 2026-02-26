<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceUserAdjustment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BetCommitBalanceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function createRace(): Race
    {
        return Race::create([
            'name' => '購入確定テスト',
            'race_date' => '2026-06-01',
            'course' => '東京',
            'horse_count' => 18,
        ]);
    }

    public function test_commit_subtracts_stake_amount_from_current_balance(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'current_balance' => 50000,
        ]);
        $race = $this->createRace();

        RaceUserAdjustment::create([
            'user_id' => $user->id,
            'race_id' => $race->id,
            'bonus_points' => 0,
            'challenge_choice' => 'normal',
            'challenge_chosen_at' => now(),
        ]);

        $sessionCart = [
            'race_id' => $race->id,
            'items' => [
                ['bet_type' => 'tansho', 'selection_key' => '1', 'amount' => 1200],
                ['bet_type' => 'fukusho', 'selection_key' => '2', 'amount' => 800],
            ],
            'groups' => [],
        ];

        $this->actingAs($user)
            ->withSession(["bet_cart_{$race->id}" => $sessionCart])
            ->post(route('bet.commit', $race))
            ->assertRedirect(route('bet.races'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_balance' => 48000,
        ]);
    }

    public function test_commit_rejects_when_stake_exceeds_current_balance(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'current_balance' => 1000,
        ]);
        $race = $this->createRace();

        RaceUserAdjustment::create([
            'user_id' => $user->id,
            'race_id' => $race->id,
            'bonus_points' => 0,
            'challenge_choice' => 'normal',
            'challenge_chosen_at' => now(),
        ]);

        $sessionCart = [
            'race_id' => $race->id,
            'items' => [
                ['bet_type' => 'tansho', 'selection_key' => '1', 'amount' => 1200],
            ],
            'groups' => [],
        ];

        $this->actingAs($user)
            ->withSession(["bet_cart_{$race->id}" => $sessionCart])
            ->post(route('bet.commit', $race))
            ->assertRedirect(route('bet.cart', $race))
            ->assertSessionHasErrors('balance');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_balance' => 1000,
        ]);
        $this->assertDatabaseMissing('bets', [
            'user_id' => $user->id,
            'race_id' => $race->id,
            'stake_amount' => 1200,
        ]);
    }
}
