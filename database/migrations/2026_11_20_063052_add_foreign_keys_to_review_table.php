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
        Schema::table('review', function (Blueprint $table) {
            $table->foreign(['product_id'], 'fk_review_product')->references(['id'])->on('product')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['user_id'], 'fk_review_user')->references(['id'])->on('user')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('review', function (Blueprint $table) {
            $table->dropForeign('fk_review_product');
            $table->dropForeign('fk_review_user');
        });
    }
};
