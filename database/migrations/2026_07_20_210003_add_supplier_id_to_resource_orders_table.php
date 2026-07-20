<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resource_orders', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('supplier_name')->constrained('suppliers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('resource_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
        });
    }
};
