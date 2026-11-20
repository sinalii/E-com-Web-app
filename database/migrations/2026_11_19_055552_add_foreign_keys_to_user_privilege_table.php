<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_privilege', function (Blueprint $table) {
            $table->foreign(['privilege_id'], 'fk_user_privilege_privilege')->references(['id'])->on('privilege')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['user_id'], 'fk_user_privilege_user')->references(['id'])->on('user')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_privilege', function (Blueprint $table) {
            $table->dropForeign('fk_user_privilege_privilege');
            $table->dropForeign('fk_user_privilege_user');
        });
    }
};
