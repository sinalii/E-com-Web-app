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
        Schema::create('coupon', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('code', 100)->nullable()->unique('code');
            $table->string('discount_type', 50)->nullable();
            $table->decimal('discount_value', 10)->nullable();
            $table->decimal('minimum_amount', 10)->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('is_active')->nullable()->default(true);
            $table->bigInteger('created_by')->nullable()->index('fk_coupon_creator');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon');
    }
};
