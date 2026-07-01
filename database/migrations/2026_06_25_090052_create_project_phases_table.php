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
        Schema::create('project_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', [
                'demolare','structura','instalatii_brute','tencuieli',
                'sape','glet','finisaje_umede','montaj_tamplarie',
                'zugraveli','pardoseli','finisaje_fine','curatenie','custom'
            ])->default('custom');
            $table->unsignedInteger('order')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('duration_days')->nullable();
            $table->enum('status', ['pending','in_progress','completed','blocked'])->default('pending');
            $table->unsignedTinyInteger('progress_pct')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_phases');
    }
};
