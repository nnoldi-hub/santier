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
        Schema::table('materials', function (Blueprint $table) {
            $table->decimal('stock_quantity', 12, 2)->nullable()->after('unit_price');
            $table->decimal('min_stock_quantity', 12, 2)->nullable()->after('stock_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'min_stock_quantity']);
        });
    }
};
