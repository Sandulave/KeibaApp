<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\BetItem;
use App\Models\Race;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RaceSettlementFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::create([
            'name' => 'admin_'.uniqid(),
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    private function createRace(): Race
    {
        return Race::create([
            'name' => 'テストレース',
            'race_date' => '2026-02-22',
            'course' => '東京',
        ]);
    }

    public function test_admin_can_open_settlement_edit_screen(): void
    {
        $admin = $this->adminUser();
        $race = $this->createRace();

        $response = $this->actingAs($admin)->get(route('races.settlement.edit', $race));

        $response->assertOk();
        $response->assertSee('精算');
    }

    public function test_settlement_data_is_saved_and_replaced(): void
    {
        $admin = $this->adminUser();
        $race = $this->createRace();

        $firstPayload = [
            'ranks' => [
                1 => [1],
                2 => [2],
                3 => [3],
            ],
            'withdrawals' => [18],
            'payouts' => [
                'wakuren' => [
                    ['selection_key' => '2-2', 'payout_per_100' => 1230, 'popularity' => 1],
                    ['selection_key' => '', 'payout_per_100' => '', 'popularity' => ''],
                ],
            ],
        ];

        $this->actingAs($admin)
            ->post(route('races.settlement.update', $race), $firstPayload)
            ->assertRedirect(route('races.settlement.edit', $race))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('race_results', [
            'race_id' => $race->id,
            'rank' => 1,
            'horse_no' => '1',
        ]);
        $this->assertDatabaseHas('race_withdrawals', [
            'race_id' => $race->id,
            'horse_no' => '18',
        ]);
        $this->assertDatabaseHas('race_payouts', [
            'race_id' => $race->id,
            'bet_type' => 'wakuren',
            'selection_scope' => 'frame',
            'selection_key' => '2-2',
            'payout_per_100' => 1230,
            'popularity' => 1,
        ]);

        $secondPayload = [
            'ranks' => [
                1 => [4],
                2 => [5],
                3 => [6],
            ],
            'withdrawals' => [],
            'payouts' => [
                'tansho' => [
                    ['selection_key' => '4', 'payout_per_100' => 540, 'popularity' => 2],
                ],
            ],
        ];

        $this->actingAs($admin)
            ->post(route('races.settlement.update', $race), $secondPayload)
            ->assertRedirect(route('races.settlement.edit', $race))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('race_payouts', [
            'race_id' => $race->id,
            'bet_type' => 'wakuren',
            'selection_key' => '2-2',
        ]);
        $this->assertDatabaseHas('race_payouts', [
            'race_id' => $race->id,
            'bet_type' => 'tansho',
            'selection_scope' => 'horse',
            'selection_key' => '4',
            'payout_per_100' => 540,
            'popularity' => 2,
        ]);
    }

    public function test_validation_rejects_duplicate_rank_and_invalid_payout(): void
    {
        $admin = $this->adminUser();
        $race = $this->createRace();

        $payload = [
            'ranks' => [
                1 => [1],
                2 => [1],
                3 => [],
            ],
            'withdrawals' => [],
            'payouts' => [
                'sanrentan' => [
                    ['selection_key' => '', 'payout_per_100' => 1200, 'popularity' => 1],
                    ['selection_key' => '1>2>3', 'payout_per_100' => '', 'popularity' => 1],
                ],
            ],
        ];

        $this->actingAs($admin)
            ->from(route('races.settlement.edit', $race))
            ->post(route('races.settlement.update', $race), $payload)
            ->assertRedirect(route('races.settlement.edit', $race))
            ->assertSessionHasErrors([
                'ranks',
                'payouts.sanrentan.0.selection_key',
                'payouts.sanrentan.1.payout_per_100',
            ]);
    }

    public function test_settlement_recalculates_bet_returns(): void
    {
        $admin = $this->adminUser();
        $race = $this->createRace();
        $admin->forceFill(['current_balance' => 1000])->save();

        $bet = Bet::create([
            'user_id' => $admin->id,
            'race_id' => $race->id,
            'stake_amount' => 200,
        ]);

        BetItem::insert([
            [
                'bet_id' => $bet->id,
                'bet_type' => 'tansho',
                'selection_key' => '4',
                'amount' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'bet_id' => $bet->id,
                'bet_type' => 'umaren',
                'selection_key' => '1-2',
                'amount' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $payload = [
            'ranks' => [
                1 => [4],
                2 => [1],
                3 => [2],
            ],
            'withdrawals' => [],
            'payouts' => [
                'tansho' => [
                    ['selection_key' => '4', 'payout_per_100' => 340, 'popularity' => 2],
                ],
                'umaren' => [
                    ['selection_key' => '1-2', 'payout_per_100' => 890, 'popularity' => 3],
                ],
            ],
        ];

        $this->actingAs($admin)
            ->post(route('races.settlement.update', $race), $payload)
            ->assertRedirect(route('races.settlement.edit', $race))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bet_items', [
            'bet_id' => $bet->id,
            'bet_type' => 'tansho',
            'selection_key' => '4',
            'return_amount' => 340,
            'is_hit' => 1,
        ]);
        $this->assertDatabaseHas('bet_items', [
            'bet_id' => $bet->id,
            'bet_type' => 'umaren',
            'selection_key' => '1-2',
            'return_amount' => 890,
            'is_hit' => 1,
        ]);

        $this->assertDatabaseHas('bets', [
            'id' => $bet->id,
            'stake_amount' => 200,
            'return_amount' => 1230,
            'hit_count' => 2,
            'roi_percent' => 615.00,
        ]);
        $this->assertSame(2230, (int) $admin->fresh()->current_balance);
    }

    public function test_settlement_recalculation_does_not_double_add_return_to_current_balance(): void
    {
        $admin = $this->adminUser();
        $race = $this->createRace();
        $admin->forceFill(['current_balance' => 500])->save();

        $bet = Bet::create([
            'user_id' => $admin->id,
            'race_id' => $race->id,
            'stake_amount' => 100,
            'return_amount' => 0,
        ]);

        BetItem::create([
            'bet_id' => $bet->id,
            'bet_type' => 'tansho',
            'selection_key' => '4',
            'amount' => 100,
        ]);

        $payload = [
            'ranks' => [
                1 => [4],
                2 => [1],
                3 => [2],
            ],
            'withdrawals' => [],
            'payouts' => [
                'tansho' => [
                    ['selection_key' => '4', 'payout_per_100' => 340, 'popularity' => 2],
                ],
            ],
        ];

        $this->actingAs($admin)
            ->post(route('races.settlement.update', $race), $payload)
            ->assertRedirect(route('races.settlement.edit', $race));
        $this->assertSame(840, (int) $admin->fresh()->current_balance);

        $this->actingAs($admin)
            ->post(route('races.settlement.update', $race), $payload)
            ->assertRedirect(route('races.settlement.edit', $race));
        $this->assertSame(840, (int) $admin->fresh()->current_balance);
    }

    public function test_settlement_recalculates_balance_when_payout_is_corrected(): void
    {
        $admin = $this->adminUser();
        $race = $this->createRace();
        $admin->forceFill(['current_balance' => 1000])->save();

        $bet = Bet::create([
            'user_id' => $admin->id,
            'race_id' => $race->id,
            'stake_amount' => 100,
            'return_amount' => 0,
        ]);

        BetItem::create([
            'bet_id' => $bet->id,
            'bet_type' => 'tansho',
            'selection_key' => '4',
            'amount' => 100,
        ]);

        $wrongPayload = [
            'ranks' => [
                1 => [4],
                2 => [1],
                3 => [2],
            ],
            'withdrawals' => [],
            'payouts' => [
                'tansho' => [
                    ['selection_key' => '4', 'payout_per_100' => 500, 'popularity' => 1],
                ],
            ],
        ];

        $this->actingAs($admin)
            ->post(route('races.settlement.update', $race), $wrongPayload)
            ->assertRedirect(route('races.settlement.edit', $race));

        $this->assertSame(1500, (int) $admin->fresh()->current_balance);

        $correctedPayload = [
            'ranks' => [
                1 => [4],
                2 => [1],
                3 => [2],
            ],
            'withdrawals' => [],
            'payouts' => [
                'tansho' => [
                    ['selection_key' => '4', 'payout_per_100' => 300, 'popularity' => 1],
                ],
            ],
        ];

        $this->actingAs($admin)
            ->post(route('races.settlement.update', $race), $correctedPayload)
            ->assertRedirect(route('races.settlement.edit', $race));

        $this->assertSame(1300, (int) $admin->fresh()->current_balance);
        $this->assertDatabaseHas('bets', [
            'id' => $bet->id,
            'return_amount' => 300,
        ]);
    }
}
