<?php

namespace App\Services;

use App\Helpers\HolidayHelper;
use Carbon\Carbon;

class AutoSchedulerService
{
    public function calculateStrictPlanning(Carbon $startDate, int $targetHeuresCentre, int $targetHeuresStage, int $nbStages, array $rules = []): array
    {
        $phases = [];
        $currentDate = $startDate->copy();

        // --- 1. CONFIGURATION ---
        $heuresParStage = $nbStages > 0 ? ceil($targetHeuresStage / $nbStages) : 0;
        
        $firstDelay = $rules['first_stage_delay_months'] ?? 3;
        $gaps = $rules['gaps_between_stages_months'] ?? [];

        // DATE DU 1ER STAGE
        $prochainStageDate = $this->addFloatMonths($startDate->copy(), (float)$firstDelay);

        // Compteurs
        $compteurCentre = 0;      
        $compteurStageTotal = 0;  
        
        $stagesRealises = 0;      
        $compteurStageActuel = 0; 
        $enModeStage = false;     

        $maxDays = 2000; 
        $d = 0;

        // --- BOUCLE PRINCIPALE (COURS & STAGES) ---
        // On continue tant qu'il reste des Heures Centre OU des Stages à faire.
        while (($compteurCentre < $targetHeuresCentre || $stagesRealises < $nbStages) && $d < $maxDays) {
            $d++;

            // 1. Week-end
            if ($currentDate->isWeekend()) {
                $currentDate->addDay();
                continue;
            }

            // 2. Férié
            if (HolidayHelper::isHoliday($currentDate)) {
                $phases[] = $this->createPhase($currentDate, 'F', 0, '#70AD47');
                $currentDate->addDay();
                continue;
            }

            // --- GESTION EN COURS DE STAGE ---
            if ($enModeStage) {
                if ($compteurStageActuel >= $heuresParStage) {
                    // FIN DU STAGE
                    $enModeStage = false; 
                    $stagesRealises++;
                    $compteurStageActuel = 0;
                    
                    // Calcul date prochain stage
                    $gapMonths = isset($gaps[$stagesRealises]) ? (float)$gaps[$stagesRealises] : 1.5;
                    $prochainStageDate = $this->addFloatMonths($currentDate->copy(), $gapMonths);
                    
                    continue; 
                } else {
                    // JOUR DE STAGE
                    $resteBloc = $heuresParStage - $compteurStageActuel;
                    $h = min(7, $resteBloc); 
                    
                    $phases[] = $this->createPhase($currentDate, 'S', $h, '#FCE4D6'); // Rose
                    
                    $compteurStageTotal += $h;
                    $compteurStageActuel += $h;
                    $currentDate->addDay();
                    continue;
                }
            }

            // --- DÉCLENCHEMENT STAGE ---
            if ($stagesRealises < $nbStages && $currentDate->gte($prochainStageDate)) {
                $enModeStage = true;
                continue; 
            }

            // --- GESTION DU CENTRE ---
            
            // Calcul des heures (si quota dépassé mais attente stage -> 7h forcées)
            $h = ($compteurCentre < $targetHeuresCentre) ? min(7, $targetHeuresCentre - $compteurCentre) : 7;
            if ($h == 0) $h = 7; 

            // LOGIQUE SIMPLIFIÉE : PLUS DE RÉVISIONS ICI !
            
            // 1. RECHERCHE DE STAGE (RS) : Lundis + Reste des stages à venir
            if ($currentDate->isMonday() && $stagesRealises < $nbStages) {
                $phases[] = $this->createPhase($currentDate, 'RS', $h, '#E2D0F9'); // Mauve
            }
            // 2. COURS STANDARD (C) : Tout le reste
            else {
                $phases[] = $this->createPhase($currentDate, 'C', $h, '#DBEAFE'); // Bleu
            }
            
            // Incrément compteur
            if ($compteurCentre < $targetHeuresCentre) {
                $compteurCentre += $h;
            }
            
            $currentDate->addDay();
        }

        // --- GESTION FINALE : COMBLEMENT DU MOIS COURANT ---
        // Une fois la boucle terminée, $currentDate est le lendemain de la fin de formation.
        // Ex: Fin le 15, $currentDate = 16.
        // Ex: Fin le 31, $currentDate = 1er du mois suivant.
        
        // On récupère le dernier jour "réel" de formation pour savoir dans quel mois on est censé être
        $dernierJourFormation = $currentDate->copy()->subDay();
        $finDuMois = $dernierJourFormation->copy()->endOfMonth();

        // Si après avoir fini, on est TOUJOURS dans le même mois que le dernier jour de formation
        // (Cela gère le cas où on finit le 30 sur un mois de 31 jours)
        // Si on finit le 31, $currentDate sera le 1er du mois suivant, donc la boucle ne se lance pas (CORRECT).
        
        while ($currentDate->lte($finDuMois)) {
            
            if ($currentDate->isWeekend()) { 
                $currentDate->addDay(); 
                continue; 
            }
            
            if (HolidayHelper::isHoliday($currentDate)) {
                $phases[] = $this->createPhase($currentDate, 'F', 0, '#70AD47');
                $currentDate->addDay(); 
                continue;
            }
            
            // On comble avec des RÉVISIONS
            $phases[] = $this->createPhase($currentDate, 'R', 7, '#BBF7D0'); // Vert
            $currentDate->addDay();
        }

        return [
            'end_date' => $currentDate->subDay()->format('Y-m-d'),
            'phases' => $phases
        ];
    }

    private function createPhase($date, $code, $hours, $color) {
        return [
            'start_date' => $date->format('Y-m-d'),
            'end_date' => $date->format('Y-m-d'),
            'code' => $code,             
            'hours' => $hours,           
            'color' => $color,
            'hours_per_day' => $hours,
            'priority' => 10
        ];
    }
    /**
     * Ajoute un nombre de mois (flottant) à une date.
     * Ex: 1.5 mois = 1 mois + 15 jours (approx)
     */
    private function addFloatMonths(Carbon $date, float $months)
    {
        $wholeMonths = (int) $months;
        $fraction = $months - $wholeMonths;
        $date->addMonths($wholeMonths);
        if ($fraction > 0) {
            $daysToAdd = round($fraction * 30);
            $date->addDays($daysToAdd);
        }
        return $date;
    }
}