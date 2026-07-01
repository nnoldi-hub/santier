<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stage_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained('project_phases')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('assignee_type')->nullable();
            $table->unsignedBigInteger('assignee_id')->nullable();
            $table->timestamp('deadline')->nullable()->index();
            $table->string('status')->default('todo')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['stage_id', 'status']);
            $table->index(['assignee_type', 'assignee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_tasks');
    }
};
