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
        Schema::table('wishlist_item', function (Blueprint $table) {
            $table->foreign(['product_id'], 'fk_wishlist_product')->references(['id'])->on('product')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['user_id'], 'fk_wishlist_user')->references(['id'])->on('user')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wishlist_item', function (Blueprint $table) {
            $table->dropForeign('fk_wishlist_product');
            $table->dropForeign('fk_wishlist_user');
        });
    }
};
