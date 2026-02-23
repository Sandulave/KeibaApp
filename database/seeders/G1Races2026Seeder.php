<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Race;

class G1Races2026Seeder extends Seeder
{
    public function run(): void
    {
        $races = [
            // RaceResultSampleSeeder用のダミーレース（id=1001,1002）
            ['name' => 'フェブラリーS', 'race_date' => '2026-02-22', 'course' => '東京'],
            ['name' => '高松宮記念', 'race_date' => '2026-03-29', 'course' => '中京'],
            ['name' => '大阪杯', 'race_date' => '2026-04-05', 'course' => '阪神'],
            ['name' => '桜花賞', 'race_date' => '2026-04-12', 'course' => '阪神'],
            ['name' => '中山グランドジャンプ（J・GⅠ）', 'race_date' => '2026-04-18', 'course' => '中山'],
            ['name' => '皐月賞', 'race_date' => '2026-04-19', 'course' => '中山'],
            ['name' => '天皇賞（春）', 'race_date' => '2026-05-03', 'course' => '京都'],
            ['name' => 'NHKマイルC', 'race_date' => '2026-05-10', 'course' => '東京'],
            ['name' => 'ヴィクトリアマイル', 'race_date' => '2026-05-17', 'course' => '東京'],
            ['name' => 'オークス', 'race_date' => '2026-05-24', 'course' => '東京'],
            ['name' => '日本ダービー', 'race_date' => '2026-05-31', 'course' => '東京'],
            ['name' => '安田記念', 'race_date' => '2026-06-07', 'course' => '東京'],
            ['name' => '宝塚記念', 'race_date' => '2026-06-14', 'course' => '阪神'],
            ['name' => 'スプリンターズS', 'race_date' => '2026-09-27', 'course' => '中山'],
            ['name' => '秋華賞', 'race_date' => '2026-10-18', 'course' => '京都'],
            ['name' => '菊花賞', 'race_date' => '2026-10-25', 'course' => '京都'],
            ['name' => '天皇賞（秋）', 'race_date' => '2026-11-01', 'course' => '東京'],
            ['name' => 'エリザベス女王杯', 'race_date' => '2026-11-15', 'course' => '京都'],
            ['name' => 'マイルCS', 'race_date' => '2026-11-22', 'course' => '京都'],
            ['name' => 'ジャパンC', 'race_date' => '2026-11-29', 'course' => '東京'],
            ['name' => 'チャンピオンズC', 'race_date' => '2026-12-06', 'course' => '中京'],
            ['name' => '阪神JF', 'race_date' => '2026-12-13', 'course' => '阪神'],
            ['name' => '朝日杯FS', 'race_date' => '2026-12-20', 'course' => '阪神'],
            ['name' => '中山大障害（J・GⅠ）', 'race_date' => '2026-12-26', 'course' => '中山'],
            ['name' => 'ホープフルS', 'race_date' => '2026-12-26', 'course' => '中山'],
            ['name' => '有馬記念', 'race_date' => '2026-12-27', 'course' => '中山'],
        ];

        // 既に同名・同日がある場合は二重登録を避けたいなら updateOrCreate もアリ
        foreach ($races as $race) {
            if (isset($race['id'])) {
                Race::updateOrCreate(
                    ['id' => $race['id']],
                    [
                        'name' => $race['name'],
                        'race_date' => $race['race_date'],
                        'course' => $race['course'],
                    ]
                );
            } else {
                Race::firstOrCreate(
                    ['name' => $race['name'], 'race_date' => $race['race_date']],
                    ['course' => $race['course']]
                );
            }
        }
    }
}
