<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('pilot_invite_id')->constrained('pilot_invites')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action_type')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'pilot_invite_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_actions');
    }
};
