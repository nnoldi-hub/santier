<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_equipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment');
            $table->decimal('hours_per_unit', 10, 4);
            $table->timestamps();
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->decimal('drying_hours', 10, 2)->nullable()->after('unit');
            $table->decimal('curing_hours', 10, 2)->nullable()->after('drying_hours');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['drying_hours', 'curing_hours']);
        });

        Schema::dropIfExists('recipe_equipment_items');
    }
};
