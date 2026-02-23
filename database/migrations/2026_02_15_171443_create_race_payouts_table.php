<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('race_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_id')->constrained('races')->cascadeOnDelete();

            $table->string('bet_type', 20);           // tansho, wide, umaren, sanrentan...
            $table->string('selection_scope', 10);    // horse / frame
            $table->string('selection_key', 32);      // "4" / "4-9" / "4>9>12"
            $table->unsignedInteger('payout_per_100'); // 100円あたり払戻金

            $table->timestamps();

            $table->unique(
                ['race_id', 'bet_type', 'selection_scope', 'selection_key'],
                'uq_race_payouts_identity'
            );

            $table->index(['race_id', 'bet_type'], 'ix_race_payouts_race_bet');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_payouts');
    }
};
