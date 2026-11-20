<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist_item', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->bigInteger('product_id');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'product_id']);
            $table->foreign('user_id', 'fk_wishlist_user')->references('id')->on('user')->cascadeOnDelete();
            $table->foreign('product_id', 'fk_wishlist_product')->references('id')->on('product')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_item');
    }
};
