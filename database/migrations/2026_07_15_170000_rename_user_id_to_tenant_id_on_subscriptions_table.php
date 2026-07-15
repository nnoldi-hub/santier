<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cashier's published migration hardcodes `user_id`, assuming User is the
     * billable model. This app uses Tenant as the Cashier billable, and
     * Eloquent's default foreign key convention for relations resolves to
     * `tenant_id` - the subscriptions table needs to match that name.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->renameColumn('user_id', 'tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->renameColumn('tenant_id', 'user_id');
        });
    }
};
