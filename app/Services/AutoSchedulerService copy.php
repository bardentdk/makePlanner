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
        
        // 1. Calcul du quota d'heures par stage
        $heuresParStage = $nbStages > 0 ? ceil($targetHeuresStage / $nbStages) : 0;
        
        // 2. Déclenchement du stage (après 40% du parcours théorique réalisé)
        $ratioDeclenchement = 0.4; 

        // Compteurs
        $compteurCentre = 0;      // Englobe : Cours (C) + Révisions (R) + Recherche (RS)
        $compteurStageTotal = 0;  // Englobe : Stage (S) uniquement
        
        $stagesRealises = 0;      // Combien de périodes de stage ont été terminées ?
        $compteurStageActuel = 0; // Avancement dans la période de stage en cours
        $enModeStage = false;     // Est-ce qu'on est actuellement en stage ?

        $maxDays = 2000; 
        $d = 0;

        // BOUCLE PRINCIPALE : TANT QU'IL RESTE DES HEURES À PLACER
        while (($compteurCentre < $targetHeuresCentre || $compteurStageTotal < $targetHeuresStage) && $d < $maxDays) {
            $d++;

            // A. GESTION WEEK-END
            if ($currentDate->isWeekend()) {
                $currentDate->addDay();
                continue;
            }

            // B. GESTION FÉRIÉ (Prioritaire sur tout)
            if (HolidayHelper::isHoliday($currentDate)) {
                $phases[] = $this->createPhase($currentDate, 'F', 0, '#70AD47'); // Vert Férié
                $currentDate->addDay();
                continue;
            }

            // --- LOGIQUE DU STAGE ---
            
            // Si on est DÉJÀ dans un stage
            if ($enModeStage) {
                // Vérifie si le stage est fini (soit ce bloc, soit le total)
                if ($compteurStageActuel >= $heuresParStage || $compteurStageTotal >= $targetHeuresStage) {
                    $enModeStage = false; // Fin du stage, retour au centre
                    $stagesRealises++;
                    $compteurStageActuel = 0;
                    // On n'avance pas la date, on laisse la boucle traiter le retour au centre immédiatement
                    continue; 
                } else {
                    // On pose une journée de STAGE
                    $resteBloc = $heuresParStage - $compteurStageActuel;
                    $resteTotal = $targetHeuresStage - $compteurStageTotal;
                    $h = min(7, $resteBloc, $resteTotal);

                    $phases[] = $this->createPhase($currentDate, 'S', $h, '#FCE4D6'); // Rose
                    
                    $compteurStageTotal += $h;
                    $compteurStageActuel += $h;
                    $currentDate->addDay();
                    continue;
                }
            }

            // Si on doit DÉCLENCHER un nouveau stage
            // Règle : Il reste des stages à faire ET on a assez avancé dans les cours
            $seuilDeclenchement = $targetHeuresCentre * ($ratioDeclenchement * ($stagesRealises + 1));
            
            // Sécurité : Si on a fini tous les cours mais qu'il reste du stage, on force le départ
            $forceStage = ($compteurCentre >= $targetHeuresCentre && $stagesRealises < $nbStages);

            if (!$enModeStage && $stagesRealises < $nbStages && ($compteurCentre >= $seuilDeclenchement || $forceStage)) {
                $enModeStage = true;
                continue; 
            }

            // --- LOGIQUE DU CENTRE (Cours, Recherche, Révisions) ---
            
            if ($compteurCentre < $targetHeuresCentre) {
                $h = min(7, $targetHeuresCentre - $compteurCentre);
                
                // 1. EST-CE DES RÉVISIONS ? (Les 70 dernières heures du parcours)
                $resteAFaire = $targetHeuresCentre - $compteurCentre;
                
                if ($resteAFaire <= 70) {
                     // C'est la toute fin : RÉVISIONS
                     $phases[] = $this->createPhase($currentDate, 'R', $h, '#BBF7D0'); // Vert Clair
                }
                // 2. EST-CE DE LA RECHERCHE DE STAGE ? 
                // Condition : C'est un Lundi ET il reste des stages à passer dans le futur
                elseif ($currentDate->isMonday() && $stagesRealises < $nbStages) {
                     // C'est un lundi avant-stage : RECHERCHE
                     $phases[] = $this->createPhase($currentDate, 'RS', $h, '#E2D0F9'); // Mauve
                }
                // 3. SINON : COURS STANDARD
                else {
                     $phases[] = $this->createPhase($currentDate, 'C', $h, '#DBEAFE'); // Bleu
                }

                $compteurCentre += $h;
                $currentDate->addDay();
            } else {
                // Plus rien à placer -> Fin du planning
                break;
            }
        }

        return [
            'end_date' => $currentDate->subDay()->format('Y-m-d'),
            'phases' => $phases
        ];
    }

    private function createPhase($date, $code, $hours, $color) {
        $content = $code;
        
        // AFFICHAGE :
        // Si c'est C (Cours) ou R (Révisions) -> On affiche le chiffre (ex: 7).
        // Si c'est RS (Recherche), S (Stage), F (Férié) -> On affiche le CODE texte.
        if ($hours > 0 && ($code === 'C' || $code === 'R')) {
            $content = $hours; 
        }
        return [
            'start_date' => $date->format('Y-m-d'),
            'end_date' => $date->format('Y-m-d'),
            'code' => $code,             // On garde 'R', 'C', 'RS'... (Crucial pour le PDF)
            'hours' => $hours,           
            'color' => $color,
            'hours_per_day' => $hours,
            'priority' => 10
        ];
        // return [
        //     'start_date' => $date->format('Y-m-d'),
        //     'end_date' => $date->format('Y-m-d'),
        //     'code' => $content,          // Visuel (ex: "7", "RS")
        //     'raw_code' => $code,         // Technique (C, R, RS...) -> Sert au calcul PDF
        //     'hours' => $hours,           // Valeur Math
        //     'color' => $color,
        //     'hours_per_day' => $hours,
        //     'priority' => 10
        // ];
    }
}