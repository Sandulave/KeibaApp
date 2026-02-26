<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'current_balance')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->integer('current_balance')->default(0)->after('audience_role');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('users', 'current_balance')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('current_balance');
        });
    }
};
