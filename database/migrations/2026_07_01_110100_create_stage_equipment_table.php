<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stage_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained('project_phases')->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->date('usage_start')->nullable();
            $table->date('usage_end')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['equipment_id', 'usage_start', 'usage_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_equipment');
    }
};
