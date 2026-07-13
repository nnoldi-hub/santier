<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE export_subscriptions MODIFY frequency ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL DEFAULT 'weekly'");
    }

    public function down(): void
    {
        // Rows using the new frequencies are normalized to 'weekly' before narrowing
        // the enum back, since MySQL rejects rows that no longer fit the column type.
        DB::table('export_subscriptions')
            ->whereIn('frequency', ['monthly', 'quarterly', 'yearly'])
            ->update(['frequency' => 'weekly']);

        DB::statement("ALTER TABLE export_subscriptions MODIFY frequency ENUM('daily', 'weekly') NOT NULL DEFAULT 'weekly'");
    }
};
