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
        Schema::table('category_filter', function (Blueprint $table) {
            $table->foreign(['category_id'], 'fk_category_filter_category')->references(['id'])->on('category')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('category_filter', function (Blueprint $table) {
            $table->dropForeign('fk_category_filter_category');
        });
    }
};
