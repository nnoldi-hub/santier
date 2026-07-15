<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->timestamp('internal_approved_at')->nullable()->after('accepted_at');
            $table->foreignId('internal_approved_by')->nullable()->after('internal_approved_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('internal_approved_by');
            $table->dropColumn('internal_approved_at');
        });
    }
};
