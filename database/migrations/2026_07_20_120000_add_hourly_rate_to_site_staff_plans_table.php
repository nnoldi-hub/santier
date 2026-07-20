<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_staff_plans', function (Blueprint $table) {
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('planned_headcount');
        });
    }

    public function down(): void
    {
        Schema::table('site_staff_plans', function (Blueprint $table) {
            $table->dropColumn('hourly_rate');
        });
    }
};
