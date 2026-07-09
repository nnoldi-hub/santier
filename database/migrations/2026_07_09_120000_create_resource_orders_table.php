<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('phase_id')->nullable()->constrained('project_phases')->nullOnDelete();
            $table->string('resource_type')->index();
            $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete();
            $table->string('supplier_name')->nullable();
            $table->string('carrier_name')->nullable();
            $table->string('equipment_name')->nullable();
            $table->decimal('ordered_quantity', 14, 2)->default(0);
            $table->string('ordered_unit', 50)->nullable();
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->date('delivery_date')->nullable()->index();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'project_id']);
            $table->index(['tenant_id', 'resource_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_orders');
    }
};
