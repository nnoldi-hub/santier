<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distinct from the existing "tenant_id" column, which is the internal
     * staff/admin tenant that owns this pipeline entry (defaults to 1) -
     * "converted_tenant_id" is the prospect's OWN tenant, once they
     * register and their trial actually starts.
     */
    public function up(): void
    {
        Schema::table('pilot_invites', function (Blueprint $table) {
            $table->foreignId('converted_tenant_id')->nullable()->after('tenant_id')->constrained('tenants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pilot_invites', function (Blueprint $table) {
            $table->dropConstrainedForeignId('converted_tenant_id');
        });
    }
};
