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
        Schema::create('shipping', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('order_id')->nullable()->index('fk_shipping_order');
            $table->bigInteger('address_id')->nullable()->index('fk_shipping_address');
            $table->string('courier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('status', 50)->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping');
    }
};
