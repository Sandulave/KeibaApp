<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('race_user_adjustments', 'carry_over_amount')) {
            return;
        }

        Schema::table('race_user_adjustments', function (Blueprint $table) {
            $table->dropColumn('carry_over_amount');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('race_user_adjustments', 'carry_over_amount')) {
            return;
        }

        Schema::table('race_user_adjustments', function (Blueprint $table) {
            $table->integer('carry_over_amount')->default(0)->after('bonus_points');
        });
    }
};
