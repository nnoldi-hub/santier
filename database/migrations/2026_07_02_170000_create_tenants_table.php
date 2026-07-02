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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('billing_plan')->default('free');
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->json('module_flags')->nullable();
            $table->timestamps();
        });

        DB::table('tenants')->insert([
            'id' => 1,
            'name' => 'Tenant implicit',
            'slug' => 'tenant-implicit',
            'billing_plan' => 'free',
            'status' => 'active',
            'module_flags' => json_encode([], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
