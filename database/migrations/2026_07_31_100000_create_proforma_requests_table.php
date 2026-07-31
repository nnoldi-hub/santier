<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proforma_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('company_name');
            $table->string('company_cui');
            $table->string('company_address')->nullable();
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone');
            $table->string('plan', 20);
            $table->string('interval', 10)->default('monthly');
            $table->unsignedTinyInteger('discount_pct')->default(20);
            $table->string('status', 20)->default('sent');
            $table->timestamp('sent_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proforma_requests');
    }
};
