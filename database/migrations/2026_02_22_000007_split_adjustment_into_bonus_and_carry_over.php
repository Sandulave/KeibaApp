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
            $table->integer('bonus_points')->default(0)->after('race_id');
            $table->integer('carry_over_amount')->default(0)->after('bonus_points');
        });

        // 既存の合算値は bonus_points 側に退避
        DB::statement('UPDATE race_user_adjustments SET bonus_points = adjustment_amount');

        Schema::table('race_user_adjustments', function (Blueprint $table) {
            $table->dropColumn('adjustment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('race_user_adjustments', function (Blueprint $table) {
            $table->integer('adjustment_amount')->default(0)->after('carry_over_amount');
        });

        DB::statement('UPDATE race_user_adjustments SET adjustment_amount = bonus_points + carry_over_amount');

        Schema::table('race_user_adjustments', function (Blueprint $table) {
            $table->dropColumn(['bonus_points', 'carry_over_amount']);
        });
    }
};
