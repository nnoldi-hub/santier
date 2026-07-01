<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stage_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained('project_phases')->cascadeOnDelete();
            $table->foreignId('contractor_id')->nullable()->constrained('contractors')->nullOnDelete();
            $table->date('report_date')->index();
            $table->unsignedTinyInteger('progress_pct')->default(0);
            $table->text('activities')->nullable();
            $table->text('issues')->nullable();
            $table->json('materials_used')->nullable();
            $table->json('equipment_used')->nullable();
            $table->json('images')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['stage_id', 'report_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_reports');
    }
};
