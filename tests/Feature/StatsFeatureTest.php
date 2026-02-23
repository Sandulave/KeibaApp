<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\BetItem;
use App\Models\Race;
use App\Models\RaceUserAdjustment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatsFeatureTest extends TestCase
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

    public function test_owner_can_update_adjustment(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'audience_role' => 'viewer',
        ]);
        $race = $this->createRace();

        $response = $this->actingAs($user)->post(route('stats.users.adjustments.update', $user), [
            'race_id' => $race->id,
            'bonus_points' => 1200,
            'carry_over_amount' => 3400,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('race_user_adjustments', [
            'user_id' => $user->id,
            'race_id' => $race->id,
            'bonus_points' => 1200,
            'carry_over_amount' => 3400,
        ]);
    }

    public function test_kannrisyato_is_redirected_to_races_on_dashboard(): void
    {
        $kannrisya = User::factory()->create(['role' => 'kannrisyato']);

        $response = $this->actingAs($kannrisya)->get(route('dashboard'));

        $response->assertRedirect(route('races.index'));
    }

    public function test_other_user_cannot_update_adjustment(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $race = $this->createRace();

        $response = $this->actingAs($other)->post(route('stats.users.adjustments.update', $owner), [
            'race_id' => $race->id,
            'bonus_points' => 1,
            'carry_over_amount' => 2,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('race_user_adjustments', [
            'user_id' => $owner->id,
            'race_id' => $race->id,
        ]);
    }

    public function test_admin_can_update_adjustment_for_other_user(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin', 'audience_role' => 'streamer']);
        $race = $this->createRace();

        $response = $this->actingAs($admin)->post(route('stats.users.adjustments.update', $owner), [
            'race_id' => $race->id,
            'bonus_points' => 500,
            'carry_over_amount' => 700,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('race_user_adjustments', [
            'user_id' => $owner->id,
            'race_id' => $race->id,
            'bonus_points' => 500,
            'carry_over_amount' => 700,
        ]);
    }

    public function test_kannrisyato_can_update_adjustment_for_other_user(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $kannrisya = User::factory()->create(['role' => 'kannrisyato', 'audience_role' => 'streamer']);
        $race = $this->createRace();

        $response = $this->actingAs($kannrisya)->post(route('stats.users.adjustments.update', $owner), [
            'race_id' => $race->id,
            'bonus_points' => 900,
            'carry_over_amount' => 100,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('race_user_adjustments', [
            'user_id' => $owner->id,
            'race_id' => $race->id,
            'bonus_points' => 900,
            'carry_over_amount' => 100,
        ]);
    }

    public function test_race_bets_page_displays_bet_items(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $viewer = User::factory()->create(['role' => 'user']);
        $race = $this->createRace();

        $bet = Bet::create([
            'user_id' => $owner->id,
            'race_id' => $race->id,
            'stake_amount' => 1200,
            'return_amount' => 0,
            'hit_count' => 0,
            'roi_percent' => 0,
        ]);

        BetItem::create([
            'bet_id' => $bet->id,
            'bet_type' => 'sanrenpuku',
            'selection_key' => '1-2-3',
            'amount' => 1200,
            'return_amount' => 0,
            'is_hit' => false,
        ]);

        RaceUserAdjustment::create([
            'user_id' => $owner->id,
            'race_id' => $race->id,
            'bonus_points' => 0,
            'carry_over_amount' => 0,
        ]);

        $response = $this->actingAs($viewer)->get(route('stats.users.race-bets', [$owner, $race]));

        $response->assertOk();
        $response->assertSee('馬券詳細');
        $response->assertSee('1-2-3');
        $response->assertSee('三連複');
    }

    public function test_stats_index_sorts_by_total_desc_then_stake_asc(): void
    {
        $viewer = User::factory()->create(['role' => 'user']);
        $race = $this->createRace();

        $userA = User::factory()->create(['role' => 'user']);
        $userB = User::factory()->create(['role' => 'user']);
        $userC = User::factory()->create(['role' => 'user']);

        Bet::create([
            'user_id' => $userA->id,
            'race_id' => $race->id,
            'stake_amount' => 500,
            'return_amount' => 600,
            'hit_count' => 1,
            'roi_percent' => 120.00,
        ]);
        Bet::create([
            'user_id' => $userB->id,
            'race_id' => $race->id,
            'stake_amount' => 300,
            'return_amount' => 800,
            'hit_count' => 1,
            'roi_percent' => 266.67,
        ]);
        Bet::create([
            'user_id' => $userC->id,
            'race_id' => $race->id,
            'stake_amount' => 400,
            'return_amount' => 900,
            'hit_count' => 1,
            'roi_percent' => 225.00,
        ]);

        RaceUserAdjustment::create([
            'user_id' => $userA->id,
            'race_id' => $race->id,
            'bonus_points' => 300,
            'carry_over_amount' => 100,
        ]);
        RaceUserAdjustment::create([
            'user_id' => $userB->id,
            'race_id' => $race->id,
            'bonus_points' => 100,
            'carry_over_amount' => 100,
        ]);
        RaceUserAdjustment::create([
            'user_id' => $userC->id,
            'race_id' => $race->id,
            'bonus_points' => 0,
            'carry_over_amount' => 0,
        ]);

        // totals: A=1000, B=1000, C=900 -> A/B tie is resolved by stake asc so B then A.
        $response = $this->actingAs($viewer)->get(route('stats.index'));

        $response->assertOk();
        $response->assertViewHas('rows', function ($rows) use ($userA, $userB, $userC) {
            return $rows->pluck('user_id')->values()->all() === [$userB->id, $userA->id, $userC->id];
        });
    }

    public function test_stats_index_rank_is_always_based_on_total_amount(): void
    {
        $viewer = User::factory()->create(['role' => 'user']);
        $race = $this->createRace();

        $userA = User::factory()->create(['role' => 'user']); // total=1000
        $userB = User::factory()->create(['role' => 'user']); // total=900
        $userC = User::factory()->create(['role' => 'user']); // total=500

        Bet::create([
            'user_id' => $userA->id,
            'race_id' => $race->id,
            'stake_amount' => 400,
            'return_amount' => 800,
            'hit_count' => 1,
            'roi_percent' => 200.00,
        ]);
        Bet::create([
            'user_id' => $userB->id,
            'race_id' => $race->id,
            'stake_amount' => 200,
            'return_amount' => 900,
            'hit_count' => 1,
            'roi_percent' => 450.00,
        ]);
        Bet::create([
            'user_id' => $userC->id,
            'race_id' => $race->id,
            'stake_amount' => 100,
            'return_amount' => 500,
            'hit_count' => 1,
            'roi_percent' => 500.00,
        ]);

        RaceUserAdjustment::create([
            'user_id' => $userA->id,
            'race_id' => $race->id,
            'bonus_points' => 200,
            'carry_over_amount' => 0,
        ]);
        RaceUserAdjustment::create([
            'user_id' => $userB->id,
            'race_id' => $race->id,
            'bonus_points' => 0,
            'carry_over_amount' => 0,
        ]);
        RaceUserAdjustment::create([
            'user_id' => $userC->id,
            'race_id' => $race->id,
            'bonus_points' => 0,
            'carry_over_amount' => 0,
        ]);

        $response = $this->actingAs($viewer)->get(route('stats.index', [
            'sort' => 'total_stake',
            'dir' => 'asc',
        ]));

        $response->assertOk();
        $response->assertViewHas('rows', function ($rows) use ($userC, $userB, $userA) {
            return $rows->pluck('user_id')->values()->all() === [$userC->id, $userB->id, $userA->id];
        });
        $response->assertViewHas('rankByUserId', function ($rankByUserId) use ($userA, $userB, $userC) {
            return ($rankByUserId[$userA->id] ?? null) === 1
                && ($rankByUserId[$userB->id] ?? null) === 2
                && ($rankByUserId[$userC->id] ?? null) === 3;
        });
    }
}
