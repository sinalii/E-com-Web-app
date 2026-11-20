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
        Schema::create('cart_item', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('cart_id')->nullable()->index('fk_cart_item_cart');
            $table->bigInteger('product_variant_id')->nullable()->index('fk_cart_item_variant');
            $table->integer('quantity')->nullable();
            $table->decimal('unit_price', 10)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_item');
    }
};
