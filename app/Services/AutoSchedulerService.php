<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AutoSchedulerService
{
    /**
     * @param Carbon $startDate
     * @param int $centerHours Heures de formation (Centre)
     * @param int $totalInternshipWeeks Durée TOTALE des stages en semaines (legacy)
     * @param array|null $rules Configuration spécifique (JSON)
     */
    public function calculatePlanning(Carbon $startDate, int $centerHours, int $totalInternshipWeeks, ?array $rules = null): array
    {
        $phases = [];
        
        // --- 1. LECTURE DES RÈGLES ---
        $rules = $rules ?? [];
        $stagesCount = $rules['stages_count'] ?? 1;
        $firstDelayMonths = $rules['first_stage_delay_months'] ?? 3.5; // Par défaut
        $gaps = $rules['gaps_between_stages_months'] ?? [];

        // Calcul de la durée d'UN stage (Total / Nombre de stages)
        // Ex: CIP 399h / 3 = 133h par stage
        // On convertit en semaines : (TotalWeeks / Count)
        $weeksPerStage = $totalInternshipWeeks / $stagesCount;

        // --- 2. PLACEMENT DES STAGES ---
        
        // A. Premier Stage
        // On utilise addWeeks(months * 4.33) pour être plus précis que addMonths qui arrondit parfois mal les demis
        $weeksDelay = $firstDelayMonths * 4.33; 
        
        $currentStageStart = $startDate->copy()->addWeeks($weeksDelay);
        if ($currentStageStart->isWeekend()) $currentStageStart->next(Carbon::MONDAY);
        
        // On sauvegarde le début du tout premier stage pour la "Recherche de stage"
        $firstStageDateForSearch = $currentStageStart->copy();

        // Boucle pour créer les X stages
        for ($i = 0; $i < $stagesCount; $i++) {
            
            $currentStageEnd = $currentStageStart->copy()->addWeeks($weeksPerStage)->subDay();
            
            // Si fin le WE, on ramène au vendredi
            if ($currentStageEnd->isWeekend()) $currentStageEnd->previous(Carbon::FRIDAY);

            $phases[] = [
                'name' => 'Période de Stage ' . ($stagesCount > 1 ? ($i + 1) : ''),
                'code' => 'S',
                'start_date' => $currentStageStart->format('Y-m-d'),
                'end_date' => $currentStageEnd->format('Y-m-d'),
                'hours_per_day' => 7,
                'color' => '#fef08a', // Jaune
                'priority' => 50,
            ];

            // Calcul du début du PROCHAIN stage (s'il y en a un autre)
            if ($i < $stagesCount - 1) {
                // On récupère l'intervalle spécifique pour ce trou (ou 1.5 mois par défaut)
                $gapMonths = $gaps[$i] ?? 1.5;
                $gapWeeks = $gapMonths * 4.33;
                
                // Le prochain démarre après la fin du précédent + gap
                $currentStageStart = $currentStageEnd->copy()->addWeeks($gapWeeks);
                if ($currentStageStart->isWeekend()) $currentStageStart->next(Carbon::MONDAY);
            }
        }

        // --- 3. CALCUL DE LA FIN DE FORMATION ---
        
        // La fin de formation est déterminée par le temps total "Heures Centre + Heures Stages + Trous"
        // Le plus simple : On regarde la fin du dernier stage, et on ajoute le reste des heures de centre ?
        // NON. Dans un TP alterné, la fin est souvent : Date Début + (Total Heures / 35).
        // Mais avec des gros trous entre les stages, la date de fin réelle recule.
        
        // Approche Robuste : 
        // On calcule la durée théorique totale en semaines (Centre + Stages)
        $totalHours = $centerHours + ($totalInternshipWeeks * 35);
        $totalWeeks = ceil($totalHours / 35);
        
        // On ajoute les "Gaps" (trous entre stages) à la durée totale car ce sont des périodes actives (formation centre)
        $totalGapMonths = array_sum($gaps);
        $totalWeeks += ($totalGapMonths * 4.33);

        // Date de fin brute
        $endDate = $startDate->copy()->addWeeks($totalWeeks);

        // Ajustement Noel (Si on traverse le 25 déc)
        $periodCheck = CarbonPeriod::create($startDate, $endDate);
        foreach ($periodCheck as $date) {
            if ($date->month == 12 && $date->day == 25) {
                $endDate->addWeeks(2); // +2 semaines fermeture
                break;
            }
        }
        if (!$endDate->isFriday()) $endDate->next(Carbon::FRIDAY);


        // --- 4. AUTRES PHASES (Noël, Révisions, Recherche) ---

        // Fermeture Noël
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
                    'color' => '#d1d5db',
                    'priority' => 100,
                ];
            }
        }

        // Révisions (2 dernières semaines)
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

        // Recherche de Stage (Lundis avant le 1er stage)
        $searchPeriod = CarbonPeriod::create($startDate, $firstStageDateForSearch->subDay());
        foreach ($searchPeriod as $date) {
            if ($date->isMonday() && !$date->isSameDay($startDate)) {
                $phases[] = [
                    'name' => 'Recherche de stage',
                    'code' => 'RS',
                    'start_date' => $date->format('Y-m-d'),
                    'end_date' => $date->format('Y-m-d'),
                    'hours_per_day' => 7,
                    'color' => '#fb923c', // Orange
                    'priority' => 45,
                ];
            }
        }

        return [
            'end_date' => $endDate->format('Y-m-d'),
            'phases' => $phases
        ];
    }
}