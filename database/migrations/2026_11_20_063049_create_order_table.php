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
        Schema::create('order', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('user_id')->nullable()->index('fk_order_user');
            $table->bigInteger('coupon_id')->nullable()->index('fk_order_coupon');
            $table->string('status', 50)->nullable();
            $table->decimal('subtotal', 10)->nullable();
            $table->decimal('discount', 10)->nullable();
            $table->decimal('shipping_cost', 10)->nullable();
            $table->decimal('tax', 10)->nullable();
            $table->decimal('total_amount', 10)->nullable();
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
        Schema::dropIfExists('order');
    }
};
