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
        Schema::table('pilot_invites', function (Blueprint $table) {
            $table->timestamp('follow_up_at')->nullable()->after('demo_scheduled_at');
            $table->timestamp('last_contacted_at')->nullable()->after('follow_up_at');
            $table->string('next_step', 255)->nullable()->after('last_contacted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pilot_invites', function (Blueprint $table) {
            $table->dropColumn(['follow_up_at', 'last_contacted_at', 'next_step']);
        });
    }
};