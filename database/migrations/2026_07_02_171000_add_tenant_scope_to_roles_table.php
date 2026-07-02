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
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            $table->index('tenant_id');

            $table->dropUnique('roles_name_guard_name_unique');
            $table->unique(['tenant_id', 'name', 'guard_name'], 'roles_tenant_name_guard_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique('roles_tenant_name_guard_unique');
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
            $table->unique(['name', 'guard_name']);
        });
    }
};
