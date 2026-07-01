<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('billing_plan', 20)->default('free')->after('onboarding_completed_at');
            $table->timestamp('billing_trial_ends_at')->nullable()->after('billing_plan');
        });

        DB::table('users')->update([
            'billing_plan' => 'pro',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['billing_plan', 'billing_trial_ends_at']);
        });
    }
};
