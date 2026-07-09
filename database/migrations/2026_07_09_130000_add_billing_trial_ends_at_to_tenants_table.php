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
        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('billing_trial_ends_at')->nullable()->after('billing_plan');
        });

        $trialDates = DB::table('users')
            ->select('tenant_id', DB::raw('MAX(billing_trial_ends_at) as latest_trial_end'))
            ->whereNotNull('billing_trial_ends_at')
            ->groupBy('tenant_id')
            ->get();

        foreach ($trialDates as $row) {
            DB::table('tenants')
                ->where('id', $row->tenant_id)
                ->update(['billing_trial_ends_at' => $row->latest_trial_end]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('billing_trial_ends_at');
        });
    }
};