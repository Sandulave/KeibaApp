<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\BetItem;
use App\Models\Race;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JraVanSettlementImportApiTest extends TestCase
{
    use RefreshDatabase;

    private function createRace(): Race
    {
        return Race::create([
            'name' => 'テストレース',
            'race_date' => '2026-02-22',
            'course' => '東京',
            'horse_count' => 18,
        ]);
    }

    public function test_import_requires_token(): void
    {
        config(['services.jra_van_import.token' => 'secret-token']);
        $race = $this->createRace();

        $this->postJson(route('api.jra-van.races.settlement.import', $race), [])
            ->assertUnauthorized();
    }

    public function test_import_replaces_results_payouts_and_recalculates_bets(): void
    {
        config(['services.jra_van_import.token' => 'secret-token']);
        $race = $this->createRace();
        $user = User::factory()->create(['current_balance' => 0]);

        $bet = Bet::create([
            'user_id' => $user->id,
            'race_id' => $race->id,
            'stake_amount' => 200,
            'return_amount' => 0,
            'hit_count' => 0,
            'roi_percent' => 0,
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
            'withdrawals' => [18],
            'payouts' => [
                'tansho' => [
                    ['selection_key' => '4', 'payout_per_100' => 340, 'popularity' => 2],
                ],
                'umaren' => [
                    ['selection_key' => '1-2', 'payout_per_100' => 890, 'popularity' => 3],
                ],
            ],
        ];

        $this->withToken('secret-token')
            ->postJson(route('api.jra-van.races.settlement.import', $race), $payload)
            ->assertOk()
            ->assertJson([
                'message' => 'imported',
                'race_id' => $race->id,
            ]);

        $this->assertDatabaseHas('race_results', [
            'race_id' => $race->id,
            'rank' => 1,
            'horse_no' => '4',
        ]);
        $this->assertDatabaseHas('race_withdrawals', [
            'race_id' => $race->id,
            'horse_no' => '18',
        ]);
        $this->assertDatabaseHas('race_payouts', [
            'race_id' => $race->id,
            'bet_type' => 'umaren',
            'selection_key' => '1-2',
            'payout_per_100' => 890,
            'popularity' => 3,
        ]);
        $this->assertDatabaseHas('bets', [
            'id' => $bet->id,
            'stake_amount' => 200,
            'return_amount' => 1230,
            'hit_count' => 2,
            'roi_percent' => 615.00,
        ]);
    }
}
