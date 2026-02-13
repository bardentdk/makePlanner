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
        
        // Configuration des délais
        $firstDelay = $rules['first_stage_delay_months'] ?? 3;
        $gaps = $rules['gaps_between_stages_months'] ?? [];

        // CALCUL DE LA DATE DU 1ER STAGE
        $prochainStageDate = $this->addFloatMonths($startDate->copy(), (float)$firstDelay);

        // Compteurs
        $compteurCentre = 0;      
        $compteurStageTotal = 0;  
        
        $stagesRealises = 0;      
        $compteurStageActuel = 0; 
        $enModeStage = false;     

        $maxDays = 2000; 
        $d = 0;

        // --- BOUCLE PRINCIPALE ---
        // On continue tant que :
        // 1. Les heures de centre ne sont pas finies
        // 2. OU tant que tous les stages ne sont pas passés (Priorité absolue aux stages)
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
                    // On remplit le bloc coûte que coûte, même si ça dépasse un peu le total théorique pour arrondir la journée
                    $h = min(7, $resteBloc); 
                    
                    $phases[] = $this->createPhase($currentDate, 'S', $h, '#FCE4D6'); // Rose
                    
                    $compteurStageTotal += $h;
                    $compteurStageActuel += $h;
                    $currentDate->addDay();
                    continue;
                }
            }

            // --- DÉCLENCHEMENT STAGE ---
            // Si on a atteint la date ET qu'il reste des stages
            if ($stagesRealises < $nbStages && $currentDate->gte($prochainStageDate)) {
                $enModeStage = true;
                continue; 
            }

            // --- GESTION DU CENTRE ---
            
            // Calcul des heures à placer (max 7h)
            // Si on a dépassé le quota mais qu'on attend un stage, on force 7h pour avancer
            $h = ($compteurCentre < $targetHeuresCentre) ? min(7, $targetHeuresCentre - $compteurCentre) : 7;
            if ($h == 0) $h = 7; // Sécurité pour éviter boucle infinie si quota pile atteint

            // --- LOGIQUE DE DÉCISION DU TYPE DE JOURNÉE ---
            
            // 1. RECHERCHE DE STAGE (RS)
            // Condition : C'est un Lundi ET il reste des stages à venir dans le futur.
            if ($currentDate->isMonday() && $stagesRealises < $nbStages) {
                $phases[] = $this->createPhase($currentDate, 'RS', $h, '#E2D0F9'); // Mauve
            }
            
            // 2. RÉVISIONS (R)
            // Condition STRICTE : Tous les stages sont finis ET on est dans les 70 dernières heures.
            // C'est ici qu'on empêche le "Sandwich".
            elseif ($stagesRealises >= $nbStages && ($targetHeuresCentre - $compteurCentre <= 70)) {
                $phases[] = $this->createPhase($currentDate, 'R', $h, '#BBF7D0'); // Vert
            }
            
            // 3. COURS STANDARD (C)
            // Tout le reste (Mardi-Vendredi avant stages, ou début de formation)
            else {
                $phases[] = $this->createPhase($currentDate, 'C', $h, '#DBEAFE'); // Bleu
            }
            
            // On incrémente le compteur seulement si on n'a pas dépassé le quota
            // (Ou on incrémente quand même, selon si vous voulez voir le dépassement ou non. Ici on incrémente pour la logique)
            if ($compteurCentre < $targetHeuresCentre) {
                $compteurCentre += $h;
            }
            
            $currentDate->addDay();
        }

        // --- GESTION FIN DE MOIS (RÉVISIONS DE CLÔTURE) ---
        // Règle : Si le planning s'arrête le 12 du mois, on remplit jusqu'au 30/31 avec des Révisions.
        
        $finDuMois = $currentDate->copy()->endOfMonth();

        // On vérifie qu'on est bien dans le même mois (pour ne pas ajouter un mois entier si ça finit le 31)
        if ($currentDate->month == $finDuMois->month && $currentDate->lte($finDuMois)) {
            while ($currentDate->lte($finDuMois)) {
                
                // On saute les WE
                if ($currentDate->isWeekend()) { 
                    $currentDate->addDay(); 
                    continue; 
                }
                
                // On marque les fériés
                if (HolidayHelper::isHoliday($currentDate)) {
                    $phases[] = $this->createPhase($currentDate, 'F', 0, '#70AD47');
                    $currentDate->addDay(); 
                    continue;
                }
                
                // On remplit le reste avec des RÉVISIONS
                $phases[] = $this->createPhase($currentDate, 'R', 7, '#BBF7D0'); // Vert
                $currentDate->addDay();
            }
        }

        return [
            'end_date' => $currentDate->subDay()->format('Y-m-d'),
            'phases' => $phases
        ];
    }

    private function createPhase($date, $code, $hours, $color) 
    {
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