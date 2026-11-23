<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('address', function (Blueprint $table) {
            if (! Schema::hasColumn('address', 'additional_phone')) {
                $table->string('additional_phone', 20)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('address', 'delivery_instructions')) {
                $table->text('delivery_instructions')->nullable()->after('country');
            }
        });
    }

    public function down(): void
    {
        Schema::table('address', function (Blueprint $table) {
            if (Schema::hasColumn('address', 'additional_phone')) $table->dropColumn('additional_phone');
            if (Schema::hasColumn('address', 'delivery_instructions')) $table->dropColumn('delivery_instructions');
        });
    }
};
