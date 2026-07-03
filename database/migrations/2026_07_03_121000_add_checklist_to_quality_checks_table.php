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
        Schema::table('quality_checks', function (Blueprint $table) {
            $table->json('checklist')->nullable()->after('description');
            $table->string('reception_type')->default('partial')->after('check_type')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quality_checks', function (Blueprint $table) {
            $table->dropColumn(['checklist', 'reception_type']);
        });
    }
};
