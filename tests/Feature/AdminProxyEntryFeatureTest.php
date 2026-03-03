<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\BetItem;
use App\Models\Race;
use App\Models\RaceUserAdjustment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProxyEntryFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function createRace(): Race
    {
        return Race::create([
            'name' => '代理入力テストレース',
            'race_date' => '2026-03-03',
            'course' => '東京',
            'horse_count' => 18,
        ]);
    }

    public function test_admin_can_open_proxy_entry_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.proxy-entry.edit'))
            ->assertOk()
            ->assertSee('代理入力');
    }

    public function test_non_admin_cannot_open_proxy_entry_page(): void
    {
        $viewer = User::factory()->create(['role' => 'user']);

        $this->actingAs($viewer)
            ->get(route('admin.proxy-entry.edit'))
            ->assertForbidden();
    }

    public function test_admin_cannot_open_bet_ui_without_proxy_target_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('bet.races'))
            ->assertRedirect(route('admin.proxy-entry.edit'));
    }

    public function test_admin_can_start_proxy_bet_ui_and_update_adjustment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create([
            'role' => 'user',
            'current_balance' => 100000,
        ]);
        $race = $this->createRace();

        $startResponse = $this->actingAs($admin)->post(route('admin.proxy-entry.bet-ui.start'), [
            'user_id' => $target->id,
        ]);
        $startResponse->assertRedirect(route('bet.races'));
        $startResponse->assertSessionHas('admin_proxy.user_id', $target->id);

        $this->actingAs($admin)->post(route('admin.proxy-entry.adjustment.store'), [
            'user_id' => $target->id,
            'race_id' => $race->id,
            'bonus_points' => 500,
        ])->assertRedirect(route('admin.proxy-entry.edit', [
            'user_id' => $target->id,
            'race_id' => $race->id,
        ]));

        $this->assertDatabaseHas('race_user_adjustments', [
            'user_id' => $target->id,
            'race_id' => $race->id,
            'bonus_points' => 500,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'current_balance' => 500,
        ]);
    }

    public function test_admin_can_delete_proxy_race_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create([
            'role' => 'user',
            'current_balance' => 10000,
        ]);
        $race = $this->createRace();

        $bet = Bet::create([
            'user_id' => $target->id,
            'race_id' => $race->id,
            'stake_amount' => 1200,
            'return_amount' => 0,
            'hit_count' => 0,
            'roi_percent' => 0,
        ]);

        BetItem::create([
            'bet_id' => $bet->id,
            'bet_type' => 'tansho',
            'selection_key' => '1',
            'amount' => 1200,
            'return_amount' => 0,
            'is_hit' => false,
        ]);

        RaceUserAdjustment::create([
            'user_id' => $target->id,
            'race_id' => $race->id,
            'bonus_points' => 500,
            'challenge_choice' => 'normal',
            'challenge_chosen_at' => now(),
            'note' => 'test',
        ]);

        $this->actingAs($admin)->delete(route('admin.proxy-entry.race-data.destroy'), [
            'user_id' => $target->id,
            'race_id' => $race->id,
        ])->assertRedirect(route('admin.proxy-entry.edit', [
            'user_id' => $target->id,
            'race_id' => $race->id,
        ]));

        $this->assertDatabaseMissing('bets', [
            'id' => $bet->id,
        ]);
        $this->assertDatabaseHas('race_user_adjustments', [
            'user_id' => $target->id,
            'race_id' => $race->id,
            'bonus_points' => 0,
            'challenge_choice' => null,
            'note' => null,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'current_balance' => 0,
        ]);
    }
}
