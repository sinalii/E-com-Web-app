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
        Schema::table('order_payment', function (Blueprint $table) {
            $table->foreign(['order_id'], 'fk_order_payment_order')->references(['id'])->on('order')->onUpdate('restrict')->onDelete('restrict');
            $table->foreign(['payment_id'], 'fk_order_payment_payment')->references(['id'])->on('payment')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_payment', function (Blueprint $table) {
            $table->dropForeign('fk_order_payment_order');
            $table->dropForeign('fk_order_payment_payment');
        });
    }
};
