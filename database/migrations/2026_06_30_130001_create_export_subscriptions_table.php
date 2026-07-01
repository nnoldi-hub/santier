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
        Schema::create('export_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('export_type')->index();
            $table->string('format', 20)->default('xlsx');
            $table->enum('frequency', ['daily', 'weekly'])->default('weekly')->index();
            $table->string('schedule_time', 5)->default('08:00');
            $table->unsignedTinyInteger('schedule_weekday')->nullable();
            $table->json('filters')->nullable();
            $table->json('recipients');
            $table->boolean('active')->default(true)->index();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_subscriptions');
    }
};
