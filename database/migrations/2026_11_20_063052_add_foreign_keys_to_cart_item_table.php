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
        Schema::table('cart_item', function (Blueprint $table) {
            $table->foreign(['cart_id'], 'fk_cart_item_cart')->references(['id'])->on('cart')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['product_variant_id'], 'fk_cart_item_variant')->references(['id'])->on('product_variant')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_item', function (Blueprint $table) {
            $table->dropForeign('fk_cart_item_cart');
            $table->dropForeign('fk_cart_item_variant');
        });
    }
};
