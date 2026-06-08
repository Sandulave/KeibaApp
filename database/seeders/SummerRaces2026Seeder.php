<?php

namespace Database\Seeders;

use App\Models\Race;
use Illuminate\Database\Seeder;

class SummerRaces2026Seeder extends Seeder
{
    public function run(): void
    {
        $races = [
            ['name' => '函館スプリントS（G3）', 'race_date' => '2026-06-13', 'course' => '函館'],
            ['name' => 'しらさぎS（G3）', 'race_date' => '2026-06-21', 'course' => '阪神'],
            ['name' => 'ラジオNIKKEI賞（G3）', 'race_date' => '2026-06-28', 'course' => '福島'],
            ['name' => '函館記念（G3）', 'race_date' => '2026-06-28', 'course' => '函館'],
            ['name' => '北九州記念（G3）', 'race_date' => '2026-07-05', 'course' => '小倉'],
            ['name' => '七夕賞（G3）', 'race_date' => '2026-07-12', 'course' => '福島'],
            ['name' => '小倉記念（G3）', 'race_date' => '2026-07-19', 'course' => '小倉'],
            ['name' => '函館2歳S（G3）', 'race_date' => '2026-07-19', 'course' => '函館'],
            ['name' => '関屋記念（G3）', 'race_date' => '2026-07-26', 'course' => '新潟'],
            ['name' => '東海S（G3）', 'race_date' => '2026-07-26', 'course' => '中京'],
            ['name' => 'アイビスサマーダッシュ（G3）', 'race_date' => '2026-08-02', 'course' => '新潟'],
            ['name' => 'クイーンS（G3）', 'race_date' => '2026-08-02', 'course' => '札幌'],
            ['name' => 'エルムS（G3）', 'race_date' => '2026-08-08', 'course' => '札幌'],
            ['name' => 'レパードS（G3）', 'race_date' => '2026-08-09', 'course' => '新潟'],
            ['name' => 'CBC賞（G3）', 'race_date' => '2026-08-09', 'course' => '中京'],
            ['name' => '中京記念（G3）', 'race_date' => '2026-08-16', 'course' => '中京'],
            ['name' => '札幌記念（G2）', 'race_date' => '2026-08-16', 'course' => '札幌'],
            ['name' => '新潟2歳S（G3）', 'race_date' => '2026-08-23', 'course' => '新潟'],
            ['name' => 'キーンランドC（G3）', 'race_date' => '2026-08-23', 'course' => '札幌'],
            ['name' => '新潟記念（G3）', 'race_date' => '2026-08-30', 'course' => '新潟'],
            ['name' => '中京2歳S（G3）', 'race_date' => '2026-08-30', 'course' => '中京'],
            ['name' => '京成杯オータムH（G3）', 'race_date' => '2026-09-05', 'course' => '中山'],
            ['name' => '札幌2歳S（G3）', 'race_date' => '2026-09-05', 'course' => '札幌'],
            ['name' => '紫苑S（G2）', 'race_date' => '2026-09-06', 'course' => '中山'],
            ['name' => 'セントウルS（G2）', 'race_date' => '2026-09-06', 'course' => '阪神'],
            ['name' => 'チャレンジC（G3）', 'race_date' => '2026-09-12', 'course' => '阪神'],
            ['name' => 'セントライト記念（G2）', 'race_date' => '2026-09-13', 'course' => '中山'],
            ['name' => 'ローズS（G2）', 'race_date' => '2026-09-13', 'course' => '阪神'],
        ];

        foreach ($races as $race) {
            Race::firstOrCreate(
                ['name' => $race['name'], 'race_date' => $race['race_date']],
                ['course' => $race['course']]
            );
        }
    }
}
