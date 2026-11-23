<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('product')
            ->join('category as main_category', 'main_category.id', '=', 'product.category_id')
            ->whereNull('main_category.parent_id')
            ->whereNull('product.main_category_id')
            ->select(['product.id', 'main_category.id as main_id'])
            ->orderBy('product.id')
            ->chunkById(500, function ($products) {
                foreach ($products as $product) {
                    DB::table('product')->where('id', $product->id)
                        ->update(['main_category_id' => $product->main_id]);
                }
            }, 'product.id', 'id');
    }

    public function down(): void
    {
        // Keep product category assignments intact when rolling back.
    }
};
