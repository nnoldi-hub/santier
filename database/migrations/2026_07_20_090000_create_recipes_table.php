<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('subject_type', 30);
            $table->unsignedBigInteger('subject_id');
            $table->string('name', 255);
            $table->string('unit', 20);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'subject_type', 'subject_id']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
