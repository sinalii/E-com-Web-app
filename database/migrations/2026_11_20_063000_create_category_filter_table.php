<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_filter', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->bigInteger('category_id')->index('fk_category_filter_category');
            $table->string('name', 180);
            $table->string('slug', 200);
            $table->string('input_type', 30)->default('checkbox');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_common')->default(false);
            $table->unique(['category_id', 'slug'], 'uq_category_filter_slug');
            $table->foreign('category_id', 'fk_category_filter_category')
                ->references('id')->on('category')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_filter');
    }
};
