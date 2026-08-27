<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('lifecycle_status', 30)->default('active')->after('status')->index();
            $table->timestamp('deletion_requested_at')->nullable()->after('lifecycle_status');
            $table->timestamp('deletion_scheduled_for')->nullable()->after('deletion_requested_at');
            $table->timestamp('anonymized_at')->nullable()->after('deletion_scheduled_for');
            $table->string('lifecycle_reason', 500)->nullable()->after('anonymized_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropIndex(['lifecycle_status']);
            $table->dropColumn([
                'lifecycle_status',
                'deletion_requested_at',
                'deletion_scheduled_for',
                'anonymized_at',
                'lifecycle_reason',
            ]);
        });
    }
};
