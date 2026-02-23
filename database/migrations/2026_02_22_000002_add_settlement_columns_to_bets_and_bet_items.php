<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table->unsignedInteger('stake_amount')->default(0)->after('memo');
            $table->unsignedInteger('return_amount')->default(0)->after('stake_amount');
            $table->unsignedInteger('hit_count')->default(0)->after('return_amount');
            $table->decimal('roi_percent', 8, 2)->nullable()->after('hit_count');
            $table->dateTime('settled_at')->nullable()->after('roi_percent');
        });

        Schema::table('bet_items', function (Blueprint $table) {
            $table->unsignedInteger('return_amount')->default(0)->after('amount');
            $table->boolean('is_hit')->default(false)->after('return_amount');
        });
    }

    public function down(): void
    {
        Schema::table('bet_items', function (Blueprint $table) {
            $table->dropColumn(['return_amount', 'is_hit']);
        });

        Schema::table('bets', function (Blueprint $table) {
            $table->dropColumn(['stake_amount', 'return_amount', 'hit_count', 'roi_percent', 'settled_at']);
        });
    }
};
