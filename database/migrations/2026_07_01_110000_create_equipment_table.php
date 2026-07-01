<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->string('type')->default('custom');
            $table->string('supplier_name')->nullable();
            $table->decimal('cost_per_hour', 12, 2)->default(0);
            $table->string('availability_status')->default('available');
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'name']);
            $table->index(['tenant_id', 'availability_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
