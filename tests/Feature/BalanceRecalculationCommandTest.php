<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\Race;
use App\Models\RaceUserAdjustment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceRecalculationCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_reports_differences_without_updating_balance(): void
    {
        $user = User::factory()->create([
            'display_name' => 'テストユーザー',
            'current_balance' => 9999,
        ]);
        $race = Race::create([
            'name' => '補正テスト',
            'race_date' => '2026-02-22',
            'course' => '東京',
            'horse_count' => 18,
        ]);

        Bet::create([
            'user_id' => $user->id,
            'race_id' => $race->id,
            'stake_amount' => 1000,
            'return_amount' => 2500,
        ]);
        RaceUserAdjustment::create([
            'user_id' => $user->id,
            'race_id' => $race->id,
            'bonus_points' => 500,
            'challenge_choice' => 'normal',
            'granted_allowance' => 10000,
            'challenge_chosen_at' => now(),
        ]);

        $this->artisan('balances:recalculate --dry-run')
            ->expectsOutputToContain(config('app.env'))
            ->expectsOutputToContain('テストユーザー')
            ->expectsOutputToContain('Race breakdown for users with balance differences:')
            ->expectsOutputToContain('Dry run only.')
            ->assertSuccessful();

        $this->assertSame(9999, (int) $user->fresh()->current_balance);
    }

    public function test_command_rebuilds_all_user_balances(): void
    {
        $user = User::factory()->create([
            'current_balance' => 9999,
        ]);
        $race = Race::create([
            'name' => '補正テスト',
            'race_date' => '2026-02-22',
            'course' => '東京',
            'horse_count' => 18,
        ]);

        Bet::create([
            'user_id' => $user->id,
            'race_id' => $race->id,
            'stake_amount' => 1000,
            'return_amount' => 2500,
        ]);
        RaceUserAdjustment::create([
            'user_id' => $user->id,
            'race_id' => $race->id,
            'bonus_points' => 500,
            'challenge_choice' => 'normal',
            'granted_allowance' => 10000,
            'challenge_chosen_at' => now(),
        ]);

        $this->artisan('balances:recalculate')
            ->expectsOutputToContain('Updated 1 user balance')
            ->assertSuccessful();

        $this->assertSame(12000, (int) $user->fresh()->current_balance);
    }
}
