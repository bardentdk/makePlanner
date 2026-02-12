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
            // IMPORTANT : nullable car calculé après la 1ère sauvegarde
            $table->date('end_date')->nullable(); 
            
            // AJOUTS INDISPENSABLES POUR VOTRE CALCUL
            $table->integer('heures_centre')->default(0);
            $table->integer('heures_stage')->default(0);

            $table->integer('default_hours')->default(7);
            $table->json('rules')->nullable();
            $table->timestamps();
        });

        Schema::create('planning_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planning_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->float('hours_per_day')->default(0);
            $table->string('color')->default('#FFFFFF');
            $table->integer('priority')->default(0);
            // On peut ajouter raw_code si besoin
            // $table->string('raw_code')->nullable(); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_phases');
        Schema::dropIfExists('plannings');
    }
};