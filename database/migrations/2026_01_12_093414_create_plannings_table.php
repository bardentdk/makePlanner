<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plannings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('learner_name')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('default_hours')->default(7);
            $table->json('rules')->nullable(); // Pour stocker config jours ouvrés
            $table->timestamps();
        });

        Schema::create('planning_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planning_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // ex: Stage, Fermeture
            $table->string('code')->nullable(); // ex: F, S
            $table->date('start_date');
            $table->date('end_date');
            $table->float('hours_per_day')->default(0);
            $table->string('color')->default('#FFFFFF');
            $table->integer('priority')->default(0); // 10=Low, 90=High
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_phases');
        Schema::dropIfExists('plannings');
    }
};