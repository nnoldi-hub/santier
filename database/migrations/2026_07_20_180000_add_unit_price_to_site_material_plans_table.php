<?php

use App\Models\SiteMaterialPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_material_plans', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->nullable()->after('planned_quantity');
        });

        SiteMaterialPlan::whereNull('unit_price')
            ->with('material:id,unit_price')
            ->chunkById(200, function ($plans) {
                foreach ($plans as $plan) {
                    $plan->update(['unit_price' => $plan->material?->unit_price ?? 0]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('site_material_plans', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });
    }
};
