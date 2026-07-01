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
        Schema::create('pilot_invites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('company_name');
            $table->string('segment')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            $table->enum('status', ['invited', 'contacted', 'demo_scheduled', 'trial_started', 'closed_won', 'closed_lost'])->default('invited')->index();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('demo_scheduled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pilot_invites');
    }
};
