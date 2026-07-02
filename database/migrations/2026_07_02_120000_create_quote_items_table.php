<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->string('item_type')->default('custom')->index();
            $table->unsignedBigInteger('reference_id')->nullable()->index();
            $table->string('name');
            $table->string('unit')->default('buc');
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('cost_unit_price', 12, 2)->default(0);
            $table->decimal('sell_unit_price', 12, 2)->default(0);
            $table->decimal('line_cost_total', 12, 2)->default(0);
            $table->decimal('line_sell_total', 12, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['quote_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
    }
};
