<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('race_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_id')->constrained('races')->cascadeOnDelete();

            $table->unsignedTinyInteger('horse_no'); // 取消馬番
            $table->string('reason')->nullable();

            $table->timestamps();

            $table->unique(['race_id', 'horse_no'], 'uq_race_withdrawals_race_horse');
            $table->index(['race_id'], 'ix_race_withdrawals_race');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_withdrawals');
    }
};
