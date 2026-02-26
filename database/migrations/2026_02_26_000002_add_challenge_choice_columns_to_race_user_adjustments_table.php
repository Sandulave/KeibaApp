<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('race_user_adjustments', function (Blueprint $table) {
            $table->string('challenge_choice', 20)->nullable()->after('carry_over_amount');
            $table->timestamp('challenge_chosen_at')->nullable()->after('challenge_choice');
        });
    }

    public function down(): void
    {
        Schema::table('race_user_adjustments', function (Blueprint $table) {
            $table->dropColumn(['challenge_choice', 'challenge_chosen_at']);
        });
    }
};
