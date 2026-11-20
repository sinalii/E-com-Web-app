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
        Schema::table('product_variant_option', function (Blueprint $table) {
            $table->foreign(['product_variant_id'], 'fk_option_variant')->references(['id'])->on('product_variant')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variant_option', function (Blueprint $table) {
            $table->dropForeign('fk_option_variant');
        });
    }
};
