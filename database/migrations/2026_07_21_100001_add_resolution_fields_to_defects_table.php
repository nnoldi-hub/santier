<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('defects', function (Blueprint $table) {
            $table->text('resolution_notes')->nullable()->after('resolved_at');
            $table->foreignId('resolved_by')->nullable()->after('resolution_notes')->constrained('users')->nullOnDelete();
            $table->string('signature_path')->nullable()->after('resolved_by');
            $table->string('signed_by_name')->nullable()->after('signature_path');
            $table->timestamp('signed_at')->nullable()->after('signed_by_name');
        });
    }

    public function down(): void
    {
        Schema::table('defects', function (Blueprint $table) {
            $table->dropForeign(['resolved_by']);
            $table->dropColumn(['resolution_notes', 'resolved_by', 'signature_path', 'signed_by_name', 'signed_at']);
        });
    }
};
