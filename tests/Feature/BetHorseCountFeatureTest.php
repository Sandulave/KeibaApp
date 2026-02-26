<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceHorse;
use App\Models\RaceUserAdjustment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BetHorseCountFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function createRace(int $horseCount = 18): Race
    {
        return Race::create([
            'name' => '頭数テストレース',
            'horse_count' => $horseCount,
            'race_date' => '2026-03-01',
            'course' => '東京',
        ]);
    }

    private function chooseNormalChallenge(User $user, Race $race): void
    {
        RaceUserAdjustment::create([
            'user_id' => $user->id,
            'race_id' => $race->id,
            'bonus_points' => 0,
            'challenge_choice' => 'normal',
            'challenge_chosen_at' => now(),
        ]);
    }

    public function test_admin_can_store_race_with_horse_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('races.store'), [
            'name' => '登録テスト',
            'horse_count' => 12,
            'race_date' => '2026-03-02',
            'course' => '中山',
            'horse_names' => [
                1 => 'テストホース',
            ],
        ]);

        $response->assertRedirect(route('races.index'));
        $this->assertDatabaseHas('races', [
            'name' => '登録テスト',
            'horse_count' => 12,
        ]);
        $this->assertDatabaseHas('race_horses', [
            'horse_no' => 1,
            'horse_name' => 'テストホース',
        ]);
    }

    public function test_admin_can_update_race_with_horse_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $race = $this->createRace(18);

        $response = $this->actingAs($admin)->put(route('races.update', $race), [
            'name' => $race->name,
            'horse_count' => 10,
            'race_date' => (string) $race->race_date,
            'course' => $race->course,
        ]);

        $response->assertRedirect(route('races.index'));
        $this->assertDatabaseHas('races', [
            'id' => $race->id,
            'horse_count' => 10,
        ]);
    }

    public function test_build_screen_displays_horses_only_up_to_race_horse_count(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $race = $this->createRace(12);
        $this->chooseNormalChallenge($user, $race);

        RaceHorse::create([
            'race_id' => $race->id,
            'horse_no' => 12,
            'horse_name' => 'サンプル12',
        ]);

        $response = $this->actingAs($user)->get(route('bet.build.mode', [$race, 'umaren', 'box']));

        $response->assertOk();
        $response->assertSee('aria-label="馬番12"', false);
        $response->assertDontSee('aria-label="馬番13"', false);
        $response->assertSee('サンプル12');
    }

    public function test_cart_add_rejects_horse_number_over_race_horse_count(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $race = $this->createRace(12);
        $this->chooseNormalChallenge($user, $race);

        $response = $this->actingAs($user)->from(route('bet.build.mode', [$race, 'umaren', 'box']))
            ->post(route('bet.cart.add', $race), [
                'betType' => 'umaren',
                'mode' => 'box',
                'horses' => [1, 13],
                'amount' => 100,
            ]);

        $response->assertRedirect(route('bet.build.mode', [$race, 'umaren', 'box']));
        $response->assertSessionHasErrors(['horses.1']);
    }

    public function test_wakuren_nagashi_allows_same_frame_pair(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $race = $this->createRace(18);
        $this->chooseNormalChallenge($user, $race);

        $response = $this->actingAs($user)->post(route('bet.cart.add', $race), [
            'betType' => 'wakuren',
            'mode' => 'nagashi_1axis',
            'axis' => 2,
            'opponents' => [2, 3],
            'amount' => 100,
        ]);

        $response->assertRedirect(route('bet.cart', $race));
        $response->assertSessionHasNoErrors();

        $cart = session("bet_cart_{$race->id}");
        $this->assertNotNull($cart);
        $this->assertSame(['2-2', '2-3'], collect($cart['items'])->pluck('selection_key')->values()->all());
    }

    public function test_umaren_box_allows_more_than_12_horses_when_race_has_enough_horses(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $race = $this->createRace(18);
        $this->chooseNormalChallenge($user, $race);

        $response = $this->actingAs($user)->post(route('bet.cart.add', $race), [
            'betType' => 'umaren',
            'mode' => 'box',
            'horses' => range(1, 13),
            'amount' => 100,
        ]);

        $response->assertRedirect(route('bet.cart', $race));
        $response->assertSessionHasNoErrors();

        $cart = session("bet_cart_{$race->id}");
        $this->assertNotNull($cart);
        $this->assertCount(78, $cart['items']);
    }

    public function test_cart_add_rejects_when_built_points_exceed_1000(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $race = $this->createRace(18);
        $this->chooseNormalChallenge($user, $race);

        $response = $this->actingAs($user)
            ->from(route('bet.build.mode', [$race, 'sanrentan', 'box']))
            ->post(route('bet.cart.add', $race), [
                'betType' => 'sanrentan',
                'mode' => 'box',
                'horses' => range(1, 13), // 13P3 = 1716
                'amount' => 100,
            ]);

        $response->assertRedirect(route('bet.build.mode', [$race, 'sanrentan', 'box']));
        $response->assertSessionHasErrors([
            'cart_add' => 'まとめてカートに追加できる点数は1000点までです（現在 1716点）。',
        ]);
    }

    public function test_wakuren_formation_allows_same_frame_pair(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $race = $this->createRace(18);
        $this->chooseNormalChallenge($user, $race);

        $response = $this->actingAs($user)->post(route('bet.cart.add', $race), [
            'betType' => 'wakuren',
            'mode' => 'formation',
            'first' => [2],
            'second' => [2, 3],
            'amount' => 100,
        ]);

        $response->assertRedirect(route('bet.cart', $race));
        $response->assertSessionHasNoErrors();

        $cart = session("bet_cart_{$race->id}");
        $this->assertNotNull($cart);
        $this->assertSame(['2-2', '2-3'], collect($cart['items'])->pluck('selection_key')->values()->all());
    }

    public function test_cart_items_are_sorted_by_bet_type_and_selection_key(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $race = $this->createRace(18);
        $this->chooseNormalChallenge($user, $race);

        $cartItems = [
            ['bet_type' => 'sanrentan', 'selection_key' => '1>2>3', 'amount' => 100],
            ['bet_type' => 'umaren', 'selection_key' => '2-3', 'amount' => 100],
            ['bet_type' => 'fukusho', 'selection_key' => '1', 'amount' => 100],
            ['bet_type' => 'tansho', 'selection_key' => '1', 'amount' => 100],
            ['bet_type' => 'wakuren', 'selection_key' => '1-2', 'amount' => 100],
            ['bet_type' => 'wide', 'selection_key' => '1-3', 'amount' => 100],
            ['bet_type' => 'umatan', 'selection_key' => '1>3', 'amount' => 100],
            ['bet_type' => 'sanrenpuku', 'selection_key' => '1-2-3', 'amount' => 100],
        ];

        $response = $this->actingAs($user)
            ->withSession([
                "bet_cart_{$race->id}" => [
                    'race_id' => $race->id,
                    'items' => $cartItems,
                ],
            ])
            ->get(route('bet.cart', $race));

        $response->assertOk();
        $response->assertSeeInOrder([
            '単勝',
            '複勝',
            '馬連',
            'ワイド',
            '馬単',
            '三連複',
            '三連単',
            '枠連',
        ]);
    }
}
