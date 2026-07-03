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
        Schema::create('task_material', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->string('unit_override', 50)->nullable();
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['task_id', 'material_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_material');
    }
};
