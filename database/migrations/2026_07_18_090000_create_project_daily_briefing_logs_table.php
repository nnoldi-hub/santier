<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_daily_briefing_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->date('briefing_date');
            $table->timestamp('sent_at');
            $table->string('risk_level', 10);
            $table->unsignedInteger('blockers_count')->default(0);
            $table->unsignedInteger('recipients_count')->default(0);
            $table->json('channels')->nullable();
            $table->json('snapshot');
            $table->timestamps();

            $table->index(['tenant_id', 'project_id', 'briefing_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_daily_briefing_logs');
    }
};
