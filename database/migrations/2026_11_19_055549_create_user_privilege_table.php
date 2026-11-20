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
        Schema::create('user_privilege', function (Blueprint $table) {
            $table->bigInteger('user_id');
            $table->bigInteger('privilege_id')->index('fk_user_privilege_privilege');
            $table->timestamp('assigned_at')->nullable()->useCurrent();

            $table->primary(['user_id', 'privilege_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_privilege');
    }
};
