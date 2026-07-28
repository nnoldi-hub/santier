<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pilot_invite_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('pilot_invite_id')->constrained('pilot_invites')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('direction', ['inbound', 'outbound'])->index();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->string('message_id')->nullable()->unique();
            $table->string('in_reply_to_message_id')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['tenant_id', 'pilot_invite_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pilot_invite_messages');
    }
};
