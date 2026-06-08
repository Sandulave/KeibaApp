<?php

namespace Database\Seeders;

use App\Models\Race;
use Illuminate\Database\Seeder;

class SetSummerRaceHorseCountToZeroSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SummerRaces2026Seeder::races() as $race) {
            Race::query()
                ->where('name', $race['name'])
                ->whereDate('race_date', $race['race_date'])
                ->doesntHave('horses')
                ->update(['horse_count' => 0]);
        }
    }
}
