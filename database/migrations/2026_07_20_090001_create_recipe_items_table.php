<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->decimal('quantity_per_unit', 10, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_items');
    }
};
