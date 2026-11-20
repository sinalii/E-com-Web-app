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
        Schema::create('order_item', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('order_id')->nullable()->index('fk_order_item_order');
            $table->bigInteger('product_variant_id')->nullable()->index('fk_order_item_variant');
            $table->string('product_name')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('unit_price', 10)->nullable();
            $table->decimal('discount', 10)->nullable();
            $table->decimal('subtotal', 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item');
    }
};
