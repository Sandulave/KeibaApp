<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // race_results: horse_no を string(2) に変更
        Schema::table('race_results', function (Blueprint $table) {
            $table->string('horse_no', 2)->change();
        });
        // race_withdrawals: horse_no を string(2) に変更
        Schema::table('race_withdrawals', function (Blueprint $table) {
            $table->string('horse_no', 2)->change();
        });
    }

    public function down(): void
    {
        // 元に戻す（unsignedTinyInteger）
        Schema::table('race_results', function (Blueprint $table) {
            $table->unsignedTinyInteger('horse_no')->change();
        });
        Schema::table('race_withdrawals', function (Blueprint $table) {
            $table->unsignedTinyInteger('horse_no')->change();
        });
    }
};
