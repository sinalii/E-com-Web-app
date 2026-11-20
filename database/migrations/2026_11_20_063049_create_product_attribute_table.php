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
        Schema::create('product_attribute', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('product_id')->nullable()->index('fk_attribute_product');
            $table->string('name')->nullable();
            $table->string('value')->nullable();
            $table->boolean('is_variant')->nullable()->default(false);
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attribute');
    }
};
