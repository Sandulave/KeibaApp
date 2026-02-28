<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->nullable()->after('race_id');
            $table->unique('idempotency_key', 'uq_bets_idempotency_key');
        });
    }

    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table->dropUnique('uq_bets_idempotency_key');
            $table->dropColumn('idempotency_key');
        });
    }
};

