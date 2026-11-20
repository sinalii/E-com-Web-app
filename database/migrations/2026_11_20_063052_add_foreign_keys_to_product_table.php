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
        Schema::table('product', function (Blueprint $table) {
            $table->foreign(['brand_id'], 'fk_product_brand')->references(['id'])->on('brand')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['category_id'], 'fk_product_category')->references(['id'])->on('category')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product', function (Blueprint $table) {
            $table->dropForeign('fk_product_brand');
            $table->dropForeign('fk_product_category');
        });
    }
};
