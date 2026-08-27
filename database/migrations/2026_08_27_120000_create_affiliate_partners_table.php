<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_partners', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 80)->unique();
            $table->string('email')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->foreignId('affiliate_partner_id')->nullable()->after('module_flags')->constrained('affiliate_partners')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('affiliate_partner_id');
        });

        Schema::dropIfExists('affiliate_partners');
    }
};
