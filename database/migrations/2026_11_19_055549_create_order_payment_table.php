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
        Schema::create('order_payment', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('order_id')->nullable()->index('fk_order_payment_order');
            $table->bigInteger('payment_id')->nullable()->index('fk_order_payment_payment');
            $table->decimal('amount', 10)->nullable();
            $table->string('status', 50)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_payment');
    }
};
