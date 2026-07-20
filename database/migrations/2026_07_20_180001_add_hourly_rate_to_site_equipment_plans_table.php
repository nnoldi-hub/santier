<?php

use App\Models\SiteEquipmentPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_equipment_plans', function (Blueprint $table) {
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('quantity');
        });

        SiteEquipmentPlan::whereNull('hourly_rate')
            ->with('equipment:id,cost_per_hour')
            ->chunkById(200, function ($plans) {
                foreach ($plans as $plan) {
                    $plan->update(['hourly_rate' => $plan->equipment?->cost_per_hour ?? 0]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('site_equipment_plans', function (Blueprint $table) {
            $table->dropColumn('hourly_rate');
        });
    }
};
