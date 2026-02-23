<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('race_user_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('race_id')->constrained()->cascadeOnDelete();
            $table->integer('adjustment_amount')->default(0); // ボーナス+繰越の合算額
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'race_id'], 'uq_race_user_adjustments_user_race');
            $table->index(['user_id'], 'ix_race_user_adjustments_user');
            $table->index(['race_id'], 'ix_race_user_adjustments_race');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_user_adjustments');
    }
};
