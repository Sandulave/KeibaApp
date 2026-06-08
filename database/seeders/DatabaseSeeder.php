<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use InvalidArgumentException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            $this->raceSeederClass(),
        ]);

        $this->call([
            UserSeeder::class,
        ]);
    }

    private function raceSeederClass(): string
    {
        $configuredSeeder = config('domain.site.race_seeder');
        if (is_string($configuredSeeder) && $configuredSeeder !== '') {
            if (! class_exists($configuredSeeder)) {
                throw new InvalidArgumentException("Configured race seeder [{$configuredSeeder}] does not exist.");
            }

            return $configuredSeeder;
        }

        return match (config('domain.site.type')) {
            'summer' => SummerRaces2026Seeder::class,
            default => G1Races2026Seeder::class,
        };
    }
}
