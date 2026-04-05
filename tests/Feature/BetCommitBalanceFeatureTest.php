<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceUserAdjustment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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

    public function test_commit_is_idempotent_with_same_token(): void
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

        $token = (string) Str::uuid();
        $sessionCart = [
            'race_id' => $race->id,
            'items' => [
                ['bet_type' => 'tansho', 'selection_key' => '1', 'amount' => 1200],
                ['bet_type' => 'fukusho', 'selection_key' => '2', 'amount' => 800],
            ],
            'groups' => [],
        ];

        $this->actingAs($user)
            ->withSession([
                "bet_cart_{$race->id}" => $sessionCart,
                "bet_commit_token_{$race->id}" => $token,
            ])
            ->post(route('bet.commit', $race), ['idempotency_key' => $token])
            ->assertRedirect(route('bet.races'));

        $this->actingAs($user)
            ->withSession([
                "bet_cart_{$race->id}" => $sessionCart,
                "bet_commit_token_{$race->id}" => $token,
            ])
            ->post(route('bet.commit', $race), ['idempotency_key' => $token])
            ->assertRedirect(route('bet.races'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'current_balance' => 48000,
        ]);
        $this->assertDatabaseCount('bets', 1);
        $this->assertDatabaseCount('bet_items', 2);
    }

    public function test_commit_preserves_snapshot_group_amounts_and_logs_amount_changes(): void
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
                ['bet_type' => 'sanrenpuku', 'selection_key' => '4-5-7', 'amount' => 300],
                ['bet_type' => 'sanrenpuku', 'selection_key' => '4-7-9', 'amount' => 100],
            ],
            'groups' => [
                [
                    'bet_type' => 'sanrenpuku',
                    'mode' => 'nagashi_1axis',
                    'input' => [
                        'axis' => '7',
                        'opponents' => ['4', '5', '9'],
                    ],
                    'item_keys' => ['sanrenpuku|4-5-7', 'sanrenpuku|4-7-9'],
                    'point_count' => 2,
                    'unit_amount' => 100,
                    'total_amount' => 200,
                ],
            ],
            'amount_changes' => [],
        ];

        $this->actingAs($user)
            ->withSession([
                "bet_cart_{$race->id}" => $sessionCart,
                "bet_commit_token_{$race->id}" => 'token-1',
            ])
            ->post(route('bet.commit', $race), [
                'idempotency_key' => 'token-1',
                'items' => [
                    ['amount' => 300],
                    ['amount' => 100],
                ],
            ])
            ->assertRedirect(route('bet.races'));

        $bet = \App\Models\Bet::query()->where('user_id', $user->id)->where('race_id', $race->id)->firstOrFail();
        $snapshot = $bet->build_snapshot;

        $this->assertSame(2, $snapshot['groups'][0]['point_count']);
        $this->assertSame(100, $snapshot['groups'][0]['unit_amount']);
        $this->assertSame(200, $snapshot['groups'][0]['total_amount']);
        $this->assertSame([
            'bet_type' => 'sanrenpuku',
            'selection_key' => '4-5-7',
            'old_amount' => 100,
            'new_amount' => 300,
            'changed_at' => $snapshot['amount_changes'][0]['changed_at'],
            'changed_by' => 'commit',
        ], $snapshot['amount_changes'][0]);
    }

    public function test_commit_logs_removed_items_when_latest_amount_is_zero(): void
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
                ['bet_type' => 'sanrenpuku', 'selection_key' => '4-5-7', 'amount' => 100],
                ['bet_type' => 'sanrenpuku', 'selection_key' => '4-7-9', 'amount' => 100],
            ],
            'groups' => [
                [
                    'bet_type' => 'sanrenpuku',
                    'mode' => 'nagashi_1axis',
                    'input' => [
                        'axis' => '7',
                        'opponents' => ['4', '5', '9'],
                    ],
                    'item_keys' => ['sanrenpuku|4-5-7', 'sanrenpuku|4-7-9'],
                    'point_count' => 2,
                    'unit_amount' => 100,
                    'total_amount' => 200,
                ],
            ],
            'amount_changes' => [],
            'removed_items' => [],
        ];

        $this->actingAs($user)
            ->withSession([
                "bet_cart_{$race->id}" => $sessionCart,
                "bet_commit_token_{$race->id}" => 'token-2',
            ])
            ->post(route('bet.commit', $race), [
                'idempotency_key' => 'token-2',
                'items' => [
                    ['amount' => 0],
                    ['amount' => 100],
                ],
            ])
            ->assertRedirect(route('bet.races'));

        $bet = \App\Models\Bet::query()->where('user_id', $user->id)->where('race_id', $race->id)->firstOrFail();
        $snapshot = $bet->build_snapshot;

        $this->assertSame(2, $snapshot['groups'][0]['point_count']);
        $this->assertSame(200, $snapshot['groups'][0]['total_amount']);
        $this->assertSame([
            'bet_type' => 'sanrenpuku',
            'selection_key' => '4-5-7',
            'amount' => 0,
            'removed_at' => $snapshot['removed_items'][0]['removed_at'],
            'removed_by' => 'commit_update_amount_zero',
        ], $snapshot['removed_items'][0]);
    }
}
