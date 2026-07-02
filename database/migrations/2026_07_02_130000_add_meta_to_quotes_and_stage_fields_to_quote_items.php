<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->json('meta')->nullable()->after('notes');
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->string('stage_name')->nullable()->after('name');
            $table->unsignedInteger('stage_order')->default(0)->after('sort_order');
            $table->index(['quote_id', 'stage_order']);
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropIndex(['quote_id', 'stage_order']);
            $table->dropColumn(['stage_name', 'stage_order']);
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }
};
