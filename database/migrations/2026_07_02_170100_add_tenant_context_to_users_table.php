<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->default(1)->after('id')->constrained('tenants');
            $table->foreignId('current_tenant_id')->nullable()->after('tenant_id')->constrained('tenants');
            $table->boolean('is_superadmin')->default(false)->after('billing_plan');

            $table->index(['tenant_id', 'email']);
            $table->index(['current_tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'email']);
            $table->dropIndex(['current_tenant_id']);
            $table->dropConstrainedForeignId('current_tenant_id');
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn('is_superadmin');
        });
    }
};
