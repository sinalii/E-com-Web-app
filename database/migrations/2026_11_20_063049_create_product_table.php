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
        Schema::create('product', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('brand_id')->nullable()->index('fk_product_brand');
            $table->bigInteger('category_id')->nullable()->index('fk_product_category');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('slug')->nullable()->unique('slug');
            $table->string('sku', 100)->nullable()->unique('sku');
            $table->string('status', 50)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->boolean('is_active')->nullable()->default(true);
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product');
    }
};
