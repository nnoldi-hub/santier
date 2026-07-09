<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_document_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('resource_order_id')->constrained('resource_orders')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('document_role')->nullable()->index();
            $table->string('document_number')->nullable()->index();
            $table->string('supplier_name')->nullable();
            $table->string('carrier_name')->nullable();
            $table->string('equipment_name')->nullable();
            $table->decimal('declared_quantity', 14, 2)->default(0);
            $table->decimal('delivered_quantity', 14, 2)->default(0);
            $table->decimal('difference_quantity', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'resource_order_id']);
            $table->unique(['resource_order_id', 'document_id'], 'resource_document_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_document_links');
    }
};
