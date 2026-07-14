<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_staff_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('phase_id')->nullable()->constrained('project_phases')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('contractor_id')->nullable()->constrained('contractors')->nullOnDelete();
            $table->string('specialty');
            $table->unsignedInteger('planned_headcount')->default(1);
            $table->date('planned_start')->nullable();
            $table->date('planned_end')->nullable();
            $table->string('risk_level', 10)->default('medium');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_staff_plans');
    }
};
