<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Training;

class TrainingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Training::create([
            'title' => 'TITRE PRO SECRÉTAIRE COMPTABLE', 
            'duration_hours' => 900, 
            'internship_weeks' => 6
        ]);
        Training::create([
            'title' => 'TITRE PRO DÉVELOPPEUR WEB', 
            'duration_hours' => 1200, 
            'internship_weeks' => 8
        ]);
    }
}
