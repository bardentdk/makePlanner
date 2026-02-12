<?php

namespace App\Services;

use App\Helpers\HolidayHelper;
use Carbon\Carbon;

class AutoSchedulerService
{
    public function calculateStrictPlanning(Carbon $startDate, int $targetHeuresCentre, int $targetHeuresStage, int $nbStages): array
    {
        $phases = [];
        $currentDate = $startDate->copy();

        // --- CONFIGURATION ---
        $heuresParStage = $nbStages > 0 ? ceil($targetHeuresStage / $nbStages) : 0;
        $ratioDeclenchement = 0.4; 

        // Compteurs
        $compteurCentre = 0;      // Englobe : Cours (C) + Recherche (RS). PAS les révisions.
        $compteurStageTotal = 0;  // S uniquement
        
        $stagesRealises = 0;      
        $compteurStageActuel = 0; 
        $enModeStage = false;     

        $maxDays = 2000; 
        $d = 0;

        // --- BOUCLE PRINCIPALE : ON PLACE LES COURS ET STAGES ---
        while (($compteurCentre < $targetHeuresCentre || $compteurStageTotal < $targetHeuresStage) && $d < $maxDays) {
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

            // --- STAGE ---
            if ($enModeStage) {
                if ($compteurStageActuel >= $heuresParStage || $compteurStageTotal >= $targetHeuresStage) {
                    $enModeStage = false; 
                    $stagesRealises++;
                    $compteurStageActuel = 0;
                    continue; 
                } else {
                    $resteBloc = $heuresParStage - $compteurStageActuel;
                    $resteTotal = $targetHeuresStage - $compteurStageTotal;
                    $h = min(7, $resteBloc, $resteTotal);

                    $phases[] = $this->createPhase($currentDate, 'S', $h, '#FCE4D6');
                    $compteurStageTotal += $h;
                    $compteurStageActuel += $h;
                    $currentDate->addDay();
                    continue;
                }
            }

            // --- DÉCLENCHEMENT STAGE ---
            $seuilDeclenchement = $targetHeuresCentre * ($ratioDeclenchement * ($stagesRealises + 1));
            $forceStage = ($compteurCentre >= $targetHeuresCentre && $stagesRealises < $nbStages);

            if (!$enModeStage && $stagesRealises < $nbStages && ($compteurCentre >= $seuilDeclenchement || $forceStage)) {
                $enModeStage = true;
                continue; 
            }

            // --- CENTRE (Cours & Recherche) ---
            if ($compteurCentre < $targetHeuresCentre) {
                $h = min(7, $targetHeuresCentre - $compteurCentre);
                
                // Ici, on ne gère PLUS les révisions. On remplit jusqu'au bout.
                
                // Recherche de Stage (RS) : Lundis + Reste des stages
                if ($currentDate->isMonday() && $stagesRealises < $nbStages) {
                     $phases[] = $this->createPhase($currentDate, 'RS', $h, '#E2D0F9'); // Mauve
                }
                // Cours Standard (C)
                else {
                     $phases[] = $this->createPhase($currentDate, 'C', $h, '#DBEAFE'); // Bleu
                }

                $compteurCentre += $h;
                $currentDate->addDay();
            } else {
                break; // Fini pour les heures payées
            }
        }

        // --- NOUVEAU : GESTION DES RÉVISIONS (FIN DE MOIS) ---
        // Une fois la formation finie, si le mois n'est pas terminé, on comble avec des révisions.
        // On recule d'un jour car la boucle while a fait un addDay() de trop à la fin
        
        // La date actuelle est le lendemain de la fin de formation.
        // Si ce jour est toujours dans le même mois que la veille (ou si le mois n'est pas fini)
        
        // On prend la date de fin réelle des cours
        $finFormation = $currentDate->copy(); 
        
        // On regarde la fin du mois de cette date de fin
        $finDuMois = $currentDate->copy()->endOfMonth();

        // Tant que 'currentDate' est <= finDuMois, on ajoute des révisions
        // Attention : Si la formation finit le 31, cette boucle ne s'exécute pas (c'est ce qu'on veut)
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

            // On ajoute une phase "R" (Révisions)
            // Indépendant des heures centre -> On met 7h par défaut
            $phases[] = $this->createPhase($currentDate, 'R', 7, '#BBF7D0'); // Vert
            
            $currentDate->addDay();
        }

        return [
            // On retourne la date de fin incluant les révisions potentielles
            'end_date' => $currentDate->subDay()->format('Y-m-d'),
            'phases' => $phases
        ];
    }

    private function createPhase($date, $code, $hours, $color) {
        // Cette fonction reste inchangée par rapport à la dernière version
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
}