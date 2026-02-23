<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('race_payouts', function (Blueprint $table) {
            $table->string('selection_scope', 10)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('race_payouts', function (Blueprint $table) {
            $table->string('selection_scope', 10)->nullable(false)->change();
        });
    }
};
