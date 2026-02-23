<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('race_id')->constrained()->cascadeOnDelete();

            $table->dateTime('bought_at')->nullable(); // 自動で now() を入れる想定
            $table->string('memo')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'race_id', 'bought_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bets');
    }
};
