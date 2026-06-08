<?php

namespace Tests\Feature;

use App\Models\Race;
use App\Models\RaceHorse;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SetSummerRaceHorseCountToZeroSeeder;
use Database\Seeders\SummerRaces2026Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_uses_g1_races_by_default(): void
    {
        config([
            'domain.site.type' => 'g1',
            'domain.site.race_seeder' => null,
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('races', [
            'name' => 'フェブラリーS',
            'race_date' => '2026-02-22',
            'course' => '東京',
        ]);
        $this->assertDatabaseMissing('races', [
            'name' => '札幌記念（G2）',
            'race_date' => '2026-08-16',
        ]);
    }

    public function test_database_seeder_uses_summer_races_for_summer_site(): void
    {
        config([
            'domain.site.type' => 'summer',
            'domain.site.race_seeder' => null,
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('races', [
            'name' => '札幌記念（G2）',
            'race_date' => '2026-08-16',
            'course' => '札幌',
            'horse_count' => 0,
        ]);
        $this->assertDatabaseMissing('races', [
            'name' => 'フェブラリーS',
            'race_date' => '2026-02-22',
        ]);
    }

    public function test_summer_race_seeder_is_idempotent(): void
    {
        $this->seed(SummerRaces2026Seeder::class);
        $this->seed(SummerRaces2026Seeder::class);

        $this->assertSame(28, Race::query()->count());
    }

    public function test_set_summer_race_horse_count_to_zero_seeder_updates_only_unregistered_races(): void
    {
        $this->seed(SummerRaces2026Seeder::class);

        $registeredRace = Race::query()
            ->where('name', '札幌記念（G2）')
            ->firstOrFail();
        $registeredRace->update(['horse_count' => 16]);
        RaceHorse::create([
            'race_id' => $registeredRace->id,
            'horse_no' => 1,
            'horse_name' => '登録済みホース',
        ]);

        Race::query()
            ->where('name', '函館スプリントS（G3）')
            ->update(['horse_count' => 18]);

        $this->seed(SetSummerRaceHorseCountToZeroSeeder::class);

        $this->assertDatabaseHas('races', [
            'name' => '函館スプリントS（G3）',
            'horse_count' => 0,
        ]);
        $this->assertDatabaseHas('races', [
            'name' => '札幌記念（G2）',
            'horse_count' => 16,
        ]);
    }
}
