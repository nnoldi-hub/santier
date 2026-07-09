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
        Schema::table('pilot_invites', function (Blueprint $table) {
            $table->string('commercial_stage', 40)->nullable()->after('status')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pilot_invites', function (Blueprint $table) {
            $table->dropIndex(['commercial_stage']);
            $table->dropColumn('commercial_stage');
        });
    }
};