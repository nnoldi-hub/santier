<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite has no ALTER COLUMN / ENUM widening - the enum is only
            // enforced at the app level (StoreExportSubscriptionRequest), so
            // on sqlite the column is simply widened to a plain string.
            Schema::table('export_subscriptions', function (Blueprint $table) {
                $table->string('frequency', 20)->default('weekly')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE export_subscriptions MODIFY frequency ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL DEFAULT 'weekly'");
    }

    public function down(): void
    {
        // Rows using the new frequencies are normalized to 'weekly' before narrowing
        // the enum back, since MySQL rejects rows that no longer fit the column type.
        DB::table('export_subscriptions')
            ->whereIn('frequency', ['monthly', 'quarterly', 'yearly'])
            ->update(['frequency' => 'weekly']);

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('export_subscriptions', function (Blueprint $table) {
                $table->string('frequency', 20)->default('weekly')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE export_subscriptions MODIFY frequency ENUM('daily', 'weekly') NOT NULL DEFAULT 'weekly'");
    }
};
