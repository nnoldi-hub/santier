<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->default(0)->after('id');
        });

        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->unique(['key', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropUnique(['key', 'tenant_id']);
        });

        Schema::table('app_settings', function (Blueprint $table) {
            $table->unique('key');
            $table->dropColumn('tenant_id');
        });
    }
};
