<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('race_user_adjustments', function (Blueprint $table) {
            $table->integer('granted_allowance')->nullable()->after('challenge_choice');
        });

        $raceAllowanceById = DB::table('races')
            ->select(['id', 'normal_allowance', 'challenge_allowance'])
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->id => [
                    'normal' => (int) ($row->normal_allowance ?? 10_000),
                    'challenge' => (int) ($row->challenge_allowance ?? 30_000),
                ],
            ]);

        DB::table('race_user_adjustments')
            ->select(['id', 'race_id', 'challenge_choice'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($raceAllowanceById) {
                foreach ($rows as $row) {
                    $choice = (string) ($row->challenge_choice ?? '');
                    $raceId = (int) ($row->race_id ?? 0);

                    $allowance = match ($choice) {
                        'normal' => (int) ($raceAllowanceById[$raceId]['normal'] ?? 10_000),
                        'challenge' => (int) ($raceAllowanceById[$raceId]['challenge'] ?? 30_000),
                        default => 0,
                    };

                    DB::table('race_user_adjustments')
                        ->where('id', $row->id)
                        ->update(['granted_allowance' => $allowance]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('race_user_adjustments', function (Blueprint $table) {
            $table->dropColumn('granted_allowance');
        });
    }
};
