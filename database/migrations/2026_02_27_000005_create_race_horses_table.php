<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('race_horses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('horse_no');
            $table->string('horse_name', 255);
            $table->timestamps();

            $table->unique(['race_id', 'horse_no'], 'uq_race_horses_race_horse_no');
            $table->index(['race_id', 'horse_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('race_horses');
    }
};
