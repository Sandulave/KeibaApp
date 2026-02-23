<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'bonus_points')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('bonus_points');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'bonus_points')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->integer('bonus_points')->default(0)->after('audience_role');
        });
    }
};
