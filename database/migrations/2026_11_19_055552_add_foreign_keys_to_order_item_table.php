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
        Schema::table('order_item', function (Blueprint $table) {
            $table->foreign(['order_id'], 'fk_order_item_order')->references(['id'])->on('order')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['product_variant_id'], 'fk_order_item_variant')->references(['id'])->on('product_variant')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_item', function (Blueprint $table) {
            $table->dropForeign('fk_order_item_order');
            $table->dropForeign('fk_order_item_variant');
        });
    }
};
