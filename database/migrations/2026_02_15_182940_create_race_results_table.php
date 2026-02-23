<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('race_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_id')->constrained('races')->cascadeOnDelete();

            $table->unsignedTinyInteger('rank'); // 1,2,3...
            $table->unsignedTinyInteger('horse_no'); // 後で alter で string(2) になる

            $table->timestamps();

            $table->index(['race_id', 'rank']);
            $table->index(['race_id', 'horse_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_results');
    }
};
