<?php

namespace Database\Seeders;

use App\Models\Training;
use Illuminate\Database\Seeder;

class TrainingSeeder extends Seeder
{
    public function run(): void
    {
        // On vide la table pour éviter les doublons lors des tests
        Training::truncate();

        $catalogue = [
            [
                'title' => 'TP COMPTABLE ASSISTANT(E)',
                'duration_hours' => 630,
                'internship_hours' => 200, // Total stage
                'scheduling_rules' => [
                    'stages_count' => 1,
                    'first_stage_delay_months' => 2.5,
                ]
            ],
            [
                'title' => 'TP SECRÉTAIRE COMPTABLE',
                'duration_hours' => 637,
                'internship_hours' => 200,
                'scheduling_rules' => [
                    'stages_count' => 1,
                    'first_stage_delay_months' => 2.5,
                ]
            ],
            [
                'title' => 'TP ASSISTANT(E) DE DIRECTION',
                'duration_hours' => 665,
                'internship_hours' => 200,
                'scheduling_rules' => [
                    'stages_count' => 1,
                    'first_stage_delay_months' => 3,
                ]
            ],
            [
                'title' => 'TP ARH (Assistant RH)',
                'duration_hours' => 651,
                'internship_hours' => 200,
                'scheduling_rules' => [
                    'stages_count' => 1,
                    'first_stage_delay_months' => 3,
                ]
            ],
            [
                'title' => 'TP CIP (Conseiller Insertion Pro)',
                'duration_hours' => 700,
                'internship_hours' => 399,
                // 3 stages. Début à 2.5 mois. Puis intervalles de 2 mois et 1.5 mois.
                'scheduling_rules' => [
                    'stages_count' => 3,
                    'first_stage_delay_months' => 2.5,
                    'gaps_between_stages_months' => [2, 1.5] 
                ]
            ],
            [
                'title' => 'TP FPA (Formateur Pro)',
                'duration_hours' => 665,
                'internship_hours' => 399,
                // 2 stages. Début à 3.5 mois. Puis intervalle de 1.5 mois.
                'scheduling_rules' => [
                    'stages_count' => 2,
                    'first_stage_delay_months' => 3.5,
                    'gaps_between_stages_months' => [1.5]
                ]
            ],
            [
                'title' => 'TP GCF (Gestionnaire Comptable Fiscal)',
                'duration_hours' => 679,
                'internship_hours' => 200,
                'scheduling_rules' => [
                    'stages_count' => 1,
                    'first_stage_delay_months' => 3,
                ]
            ],
            [
                'title' => 'TP GP (Gestionnaire de Paie)',
                'duration_hours' => 630,
                'internship_hours' => 200,
                'scheduling_rules' => [
                    'stages_count' => 1,
                    'first_stage_delay_months' => 3.5,
                ]
            ],
        ];

        foreach ($catalogue as $data) {
            // On calcule internship_weeks pour la compatibilité (Heures / 35)
            $weeks = ceil($data['internship_hours'] / 35);

            Training::create([
                'title' => $data['title'],
                'duration_hours' => $data['duration_hours'],
                'internship_weeks' => $weeks, 
                // On stocke la règle JSON
                'scheduling_rules' => $data['scheduling_rules']
            ]);
        }
    }
}