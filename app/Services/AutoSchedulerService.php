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
        // 1. Calcul de la Date de Fin Théorique
        // On part sur 35h/semaine (7h/j * 5j)
        // Ceci est une estimation pour caler le calendrier.
        $weeksNeeded = ceil($durationHours / 35);
        
        // On ajoute les semaines de stage au temps total car elles sont incluses ou en plus ?
        // En général dans un TP, le volume horaire inclut tout ou est séparé.
        // Partons du principe que duration_hours = heures en CENTRE. 
        // Donc Durée Totale = (Heures Centre / 35) + Semaines Stage + Vacances Noel éventuelles.
        
        // Pour simplifier, on ajoute juste le temps brut pour trouver la fin approximative,
        // puis on affinera avec les phases.
        $totalWeeks = $weeksNeeded + $internshipWeeks; 
        
        // On ajoute une marge pour Noel (2 semaines) si on traverse décembre
        // Estimation simple :
        $endDate = $startDate->copy()->addWeeks($totalWeeks);
        
        // Ajustement Noel (Si on traverse le 25 déc)
        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            if ($date->month == 12 && $date->day == 25) {
                $endDate->addWeeks(2); // On décale la fin de 2 semaines pour compenser la fermeture
                break;
            }
        }

        // On s'assure que ça finit un vendredi
        if (!$endDate->isFriday()) {
            $endDate->next(Carbon::FRIDAY);
        }

        // 2. Génération des Phases
        $phases = [];

        // A. Fermeture Noël (23 Déc au 5 Janvier)
        // On regarde chaque année couverte par le planning
        $startYear = $startDate->year;
        $endYear = $endDate->year;

        for ($y = $startYear; $y <= $endYear; $y++) {
            // Noël de l'année Y
            $noelStart = Carbon::create($y, 12, 23);
            $noelEnd = Carbon::create($y + 1, 1, 5);

            // Si cette période touche notre planning
            if ($noelStart->lessThanOrEqualTo($endDate) && $noelEnd->greaterThanOrEqualTo($startDate)) {
                // Ajustement week-end : Si le 23 est un samedi/dimanche ? 
                // La règle dit "23/12 au 05/01". On applique bêtement, le générateur fera le reste (grisera les WE).
                
                $phases[] = [
                    'name' => 'Fermeture Noël',
                    'code' => 'FC',
                    'start_date' => $noelStart->format('Y-m-d'),
                    'end_date' => $noelEnd->format('Y-m-d'),
                    'hours_per_day' => 7, // Garder 7h pour la structure, ou 0 ? "7h pour tout" tu as dit.
                    // Si c'est fermé, c'est 0h travaillées, mais peut-être veux-tu que la case C contienne "FC" ?
                    // Si tu veux "FC", hours importe peu pour l'affichage, mais pour le total c'est 0.
                    // On va mettre 0 pour le calcul d'heures, mais le code 'FC' s'affichera.
                    'hours_per_day' => 0, 
                    'color' => '#d1d5db', // Gris
                    'priority' => 100, // Priorité MAX
                ];
            }
        }

        // B. Stage (Après 4 mois)
        // Règle : Start + 4 mois
        $stageStart = $startDate->copy()->addMonths(4);
        
        // Si ça tombe un samedi/dimanche, on décale au lundi
        if ($stageStart->isWeekend()) {
            $stageStart->next(Carbon::MONDAY);
        }

        $stageEnd = $stageStart->copy()->addWeeks($internshipWeeks)->subDay(); // -1 jour pour finir la veille
        
        // Vérif : Si le stage tombe pendant Noel ? (Cas rare mais possible)
        // Avec la priorité, Noel écrasera le stage visuellement. C'est OK.

        $phases[] = [
            'name' => 'Période de Stage',
            'code' => 'S',
            'start_date' => $stageStart->format('Y-m-d'),
            'end_date' => $stageEnd->format('Y-m-d'),
            'hours_per_day' => 7,
            'color' => '#fef08a', // Jaune
            'priority' => 50,
        ];

        // C. Révisions (2 dernières semaines)
        $revisionEnd = $endDate->copy();
        $revisionStart = $endDate->copy()->subWeeks(2)->startOfWeek(); // Lundi des 2 dernières semaines

        $phases[] = [
            'name' => 'Révisions',
            'code' => 'R', // Ou juste 7h ? Mettons 'R' pour distinguer
            'start_date' => $revisionStart->format('Y-m-d'),
            'end_date' => $revisionEnd->format('Y-m-d'),
            'hours_per_day' => 7,
            'color' => '#bbf7d0', // Vert
            'priority' => 40,
        ];

        return [
            'end_date' => $endDate->format('Y-m-d'),
            'phases' => $phases
        ];
    }
}