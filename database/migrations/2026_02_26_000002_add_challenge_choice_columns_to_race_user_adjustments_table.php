<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasChallengeChoice = Schema::hasColumn('race_user_adjustments', 'challenge_choice');
        $hasChallengeChosenAt = Schema::hasColumn('race_user_adjustments', 'challenge_chosen_at');
        $hasCarryOverAmount = Schema::hasColumn('race_user_adjustments', 'carry_over_amount');
        $hasBonusPoints = Schema::hasColumn('race_user_adjustments', 'bonus_points');

        if ($hasChallengeChoice && $hasChallengeChosenAt) {
            return;
        }

        Schema::table('race_user_adjustments', function (Blueprint $table) use ($hasCarryOverAmount, $hasBonusPoints) {
            if (!Schema::hasColumn('race_user_adjustments', 'challenge_choice')) {
                $challengeChoice = $table->string('challenge_choice', 20)->nullable();
                if ($hasCarryOverAmount) {
                    $challengeChoice->after('carry_over_amount');
                } elseif ($hasBonusPoints) {
                    $challengeChoice->after('bonus_points');
                }
            }

            if (!Schema::hasColumn('race_user_adjustments', 'challenge_chosen_at')) {
                $challengeChosenAt = $table->timestamp('challenge_chosen_at')->nullable();
                if (Schema::hasColumn('race_user_adjustments', 'challenge_choice')) {
                    $challengeChosenAt->after('challenge_choice');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('race_user_adjustments', function (Blueprint $table) {
            $dropColumns = [];
            if (Schema::hasColumn('race_user_adjustments', 'challenge_choice')) {
                $dropColumns[] = 'challenge_choice';
            }
            if (Schema::hasColumn('race_user_adjustments', 'challenge_chosen_at')) {
                $dropColumns[] = 'challenge_chosen_at';
            }
            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
