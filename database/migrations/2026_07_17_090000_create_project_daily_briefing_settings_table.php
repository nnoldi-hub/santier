<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_daily_briefing_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete()->unique();
            $table->boolean('enabled')->default(false);
            $table->time('send_time')->default('07:30:00');
            $table->json('recipient_user_ids')->nullable();
            $table->string('detail_level', 20)->default('complet');
            $table->json('channels')->nullable();
            $table->date('last_sent_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_daily_briefing_settings');
    }
};
