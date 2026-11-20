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
        Schema::create('product_variant_option', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('product_variant_id')->nullable()->index('fk_option_variant');
            $table->string('option_name')->nullable();
            $table->string('option_value')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_option');
    }
};
