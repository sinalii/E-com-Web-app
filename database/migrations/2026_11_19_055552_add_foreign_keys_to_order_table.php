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
        Schema::table('order', function (Blueprint $table) {
            $table->foreign(['coupon_id'], 'fk_order_coupon')->references(['id'])->on('coupon')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['user_id'], 'fk_order_user')->references(['id'])->on('user')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order', function (Blueprint $table) {
            $table->dropForeign('fk_order_coupon');
            $table->dropForeign('fk_order_user');
        });
    }
};
