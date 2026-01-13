<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AutoSchedulerService
{
    /**
     * Calcule la date de fin et génère les phases automatiques
     */
    public function calculatePlanning(Carbon $startDate, int $durationHours, int $internshipWeeks): array
    {
        // 1. Calcul de la Date de Fin Théorique (Estimation brute)
        $weeksNeeded = ceil($durationHours / 35);
        $totalWeeks = $weeksNeeded + $internshipWeeks; 
        
        $endDate = $startDate->copy()->addWeeks($totalWeeks);
        
        // Ajustement Noel (Si on traverse le 25 déc, on ajoute 2 semaines de marge)
        $periodCheck = CarbonPeriod::create($startDate, $endDate);
        foreach ($periodCheck as $date) {
            if ($date->month == 12 && $date->day == 25) {
                $endDate->addWeeks(2);
                break;
            }
        }

        // On finit toujours un vendredi
        if (!$endDate->isFriday()) {
            $endDate->next(Carbon::FRIDAY);
        }

        $phases = [];

        // --- A. Fermeture Noël (23 Déc au 5 Janvier) ---
        $startYear = $startDate->year;
        $endYear = $endDate->year;

        for ($y = $startYear; $y <= $endYear; $y++) {
            $noelStart = Carbon::create($y, 12, 23);
            $noelEnd = Carbon::create($y + 1, 1, 5);

            if ($noelStart->lessThanOrEqualTo($endDate) && $noelEnd->greaterThanOrEqualTo($startDate)) {
                $phases[] = [
                    'name' => 'Fermeture Noël',
                    'code' => 'FC',
                    'start_date' => $noelStart->format('Y-m-d'),
                    'end_date' => $noelEnd->format('Y-m-d'),
                    'hours_per_day' => 0, 
                    'color' => '#d1d5db', // Gris
                    'priority' => 100, // Priorité MAX (écrase tout)
                ];
            }
        }

        // --- B. Stage (Calcul Date Début) ---
        // Règle : Start + 4 mois
        $stageStart = $startDate->copy()->addMonths(4);
        
        // Si ça tombe un WE, on décale au lundi
        if ($stageStart->isWeekend()) {
            $stageStart->next(Carbon::MONDAY);
        }

        $stageEnd = $stageStart->copy()->addWeeks($internshipWeeks)->subDay();

        $phases[] = [
            'name' => 'Période de Stage',
            'code' => 'S',
            'start_date' => $stageStart->format('Y-m-d'),
            'end_date' => $stageEnd->format('Y-m-d'),
            'hours_per_day' => 7,
            'color' => '#fef08a', // Jaune
            'priority' => 50,
        ];

        // --- C. Révisions (2 dernières semaines) ---
        $revisionEnd = $endDate->copy();
        $revisionStart = $endDate->copy()->subWeeks(2)->startOfWeek(); 

        $phases[] = [
            'name' => 'Révisions',
            'code' => 'R',
            'start_date' => $revisionStart->format('Y-m-d'),
            'end_date' => $revisionEnd->format('Y-m-d'),
            'hours_per_day' => 7,
            'color' => '#bbf7d0', // Vert
            'priority' => 40,
        ];

        // --- D. Recherche de Stage (Tous les Lundis avant le stage) ---
        // Période : Du début formation jusqu'à la veille du stage
        $searchPeriod = CarbonPeriod::create($startDate, $stageStart->copy()->subDay());

        foreach ($searchPeriod as $date) {
            // Si c'est un Lundi
            if ($date->isMonday()) {
                
                // EXCEPTION : Si c'est le tout premier jour de formation
                if ($date->isSameDay($startDate)) {
                    continue; // On laisse en "Formation" normale
                }

                $phases[] = [
                    'name' => 'Recherche de stage',
                    'code' => 'RS', // Code pour l'Excel
                    'start_date' => $date->format('Y-m-d'),
                    'end_date' => $date->format('Y-m-d'), // Dure 1 jour
                    'hours_per_day' => 7,
                    'color' => '#fb923c', // Orange (reconnaissable)
                    'priority' => 45, // Priorité < 50 (ne doit pas écraser un férié ou une fermeture centre)
                ];
            }
        }

        return [
            'end_date' => $endDate->format('Y-m-d'),
            'phases' => $phases
        ];
    }
}