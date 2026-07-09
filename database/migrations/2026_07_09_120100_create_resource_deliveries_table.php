<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('resource_order_id')->constrained('resource_orders')->cascadeOnDelete();
            $table->decimal('declared_quantity', 14, 2)->default(0);
            $table->decimal('received_quantity', 14, 2)->default(0);
            $table->decimal('equipment_reported_quantity', 14, 2)->default(0);
            $table->decimal('consumed_quantity', 14, 2)->default(0);
            $table->decimal('returned_quantity', 14, 2)->default(0);
            $table->dateTime('delivered_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'resource_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_deliveries');
    }
};
