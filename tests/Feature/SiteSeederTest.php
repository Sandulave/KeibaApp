<?php

namespace Tests\Feature;

use App\Models\Race;
use Database\Seeders\DatabaseSeeder;
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
}
