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
        Schema::table('product_attribute', function (Blueprint $table) {
            $table->foreign(['product_id'], 'fk_attribute_product')->references(['id'])->on('product')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_attribute', function (Blueprint $table) {
            $table->dropForeign('fk_attribute_product');
        });
    }
};
