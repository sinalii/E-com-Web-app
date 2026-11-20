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
        Schema::create('user', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique('email');
            $table->string('password')->nullable();
            $table->tinyInteger('role')->nullable();
            $table->string('phone', 20)->nullable()->unique('phone');
            $table->string('profile')->nullable();
            $table->boolean('email_verify')->nullable()->default(false);
            $table->boolean('is_admin')->nullable()->default(false);
            $table->string('address')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};
