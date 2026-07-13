<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pilot_invites', function (Blueprint $table) {
            $table->timestamp('onboarding_handoff_at')->nullable()->after('last_contacted_at');
        });
    }

    public function down(): void
    {
        Schema::table('pilot_invites', function (Blueprint $table) {
            $table->dropColumn('onboarding_handoff_at');
        });
    }
};
