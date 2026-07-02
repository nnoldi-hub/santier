<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quote_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->foreignId('source_quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->foreignId('source_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->json('template_payload');
            $table->unsignedInteger('usage_count')->default(0);
            $table->decimal('quality_score', 5, 2)->default(0);
            $table->boolean('is_recommended')->default(true)->index();
            $table->timestamp('last_used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'usage_count']);
            $table->index(['tenant_id', 'quality_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_templates');
    }
};
