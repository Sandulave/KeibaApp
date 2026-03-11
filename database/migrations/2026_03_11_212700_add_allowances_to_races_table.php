<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->unsignedInteger('normal_allowance')->default(10_000)->after('horse_count');
            $table->unsignedInteger('challenge_allowance')->default(30_000)->after('normal_allowance');
        });
    }

    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn(['normal_allowance', 'challenge_allowance']);
        });
    }
};
