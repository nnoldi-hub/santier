<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->timestamp('plan_approved_at')->nullable()->after('total_budget');
            $table->foreignId('plan_approved_by')->nullable()->after('plan_approved_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_approved_by');
            $table->dropColumn('plan_approved_at');
        });
    }
};
