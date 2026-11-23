<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('product', 'main_category_id')) {
            Schema::table('product', function (Blueprint $table) {
                $table->bigInteger('main_category_id')->nullable();
                $table->foreign('main_category_id', 'fk_product_main_category_id')
                    ->references('id')->on('category')->onUpdate('restrict')->onDelete('restrict');
            });
        }

        DB::table('product')
            ->join('category as subcategory', 'subcategory.id', '=', 'product.category_id')
            ->whereNotNull('subcategory.parent_id')
            ->whereNull('product.main_category_id')
            ->select(['product.id', 'subcategory.parent_id'])
            ->orderBy('product.id')
            ->chunkById(500, function ($products) {
                foreach ($products as $product) {
                    DB::table('product')->where('id', $product->id)
                        ->update(['main_category_id' => $product->parent_id]);
                }
            }, 'product.id', 'id');
    }

    public function down(): void
    {
        if (Schema::hasColumn('product', 'main_category_id')) {
            Schema::table('product', function (Blueprint $table) {
                $table->dropForeign('fk_product_main_category_id');
                $table->dropColumn('main_category_id');
            });
        }
    }
};
