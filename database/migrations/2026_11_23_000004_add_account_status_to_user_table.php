<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $addActive = ! Schema::hasColumn('user', 'is_active');
        $addBlocked = ! Schema::hasColumn('user', 'is_blocked');
        if (! $addActive && ! $addBlocked) return;

        Schema::table('user', function (Blueprint $table) use ($addActive, $addBlocked) {
            if ($addActive) $table->boolean('is_active')->default(true);
            if ($addBlocked) $table->boolean('is_blocked')->default(false);
        });
    }

    public function down(): void
    {
        $columns = [];
        if (Schema::hasColumn('user', 'is_active')) $columns[] = 'is_active';
        if (Schema::hasColumn('user', 'is_blocked')) $columns[] = 'is_blocked';
        if (! $columns) return;

        Schema::table('user', fn (Blueprint $table) => $table->dropColumn($columns));
    }
};
