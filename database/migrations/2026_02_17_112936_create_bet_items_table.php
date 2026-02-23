<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bet_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bet_id')->constrained('bets')->cascadeOnDelete();

            $table->string('bet_type', 20);
            $table->string('selection_key', 32);
            $table->unsignedInteger('amount'); // 円

            $table->timestamps();

            // 重複買いOKなので unique は付けない
            $table->index(['bet_id', 'bet_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bet_items');
    }
};
