<?php

namespace App\Services;

use App\Data\DayCellDTO;
use App\Models\Planning;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class PlanningGeneratorService
{
    public function __construct(protected HolidayService $holidayService) {}

    public function generateGrid(Planning $planning): array
    {
        // On charge les phases triées par priorité CROISSANTE pour écraser successivement
        // Ou DECROISSANTE et on prend le premier match. Prenons DESC (Le plus fort gagne tout de suite).
        $phases = $planning->phases()->orderBy('priority', 'desc')->get();
        
        $period = CarbonPeriod::create($planning->start_date, $planning->end_date);
        $gridByMonth = [];

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $monthKey = $date->format('Y-m'); // Clé de regroupement

            // 1. État par défaut (Formation ou Weekend)
            $isWeekend = $date->isWeekend();
            
            // DTO initial
            $cell = new DayCellDTO(
                date: $date->copy(),
                dayLetter: $this->frenchDayLetter($date),
                content: $isWeekend ? '' : $planning->default_hours,
                type: $isWeekend ? 'weekend' : 'formation',
                color: $isWeekend ? '#E5E7EB' : '#FFFFFF', // Gris Tailwind 100 vs Blanc
                label: $isWeekend ? '' : 'Formation',
                isWorked: !$isWeekend
            );

            // 2. Vérification Férié (Priorité ~50 implicite)
            $holidayName = $this->holidayService->getHolidayName($dateStr);
            if ($holidayName) {
                $cell->content = 'F'; // Code standard
                $cell->type = 'ferie';
                $cell->color = '#FCA5A5'; // Rouge clair
                $cell->label = $holidayName;
                $cell->isWorked = false;
            }

            // 3. Application des Phases (Stages, Fermeture, etc.)
            // La phase la plus prioritaire l'emporte sur tout le reste
            foreach ($phases as $phase) {
                if ($date->between($phase->start_date, $phase->end_date)) {
                    
                    // RÈGLE MÉTIER : 
                    // Si c'est un férié, une phase "faible" (Stage, prio 20) ne doit pas l'écraser.
                    // Mais une phase "forte" (Fermeture Centre, prio 100) doit l'écraser.
                    // Disons que Férié a une priorité virtuelle de 50.
                    
                    $phaseWins = true;

                    if ($cell->type === 'ferie' && $phase->priority < 50) {
                        $phaseWins = false; 
                    }

                    if ($phaseWins) {
                        // On applique la phase
                        // Si code existe (ex: "S"), on affiche le code. Sinon les heures.
                        $cell->content = $phase->code ?? $phase->hours_per_day;
                        $cell->type = 'phase_' . $phase->id;
                        $cell->color = $phase->color;
                        $cell->label = $phase->name;
                        $cell->isWorked = $phase->hours_per_day > 0;
                        
                        // On break car on a trié par priorité DESC, le plus fort a gagné
                        break; 
                    }
                }
            }

            // Construction structure groupée
            if (!isset($gridByMonth[$monthKey])) {
                $gridByMonth[$monthKey] = [
                    'month_label' => $date->translatedFormat('F Y'),
                    'days' => []
                ];
            }
            $gridByMonth[$monthKey]['days'][] = $cell;
        }

        return $gridByMonth;
    }

    private function frenchDayLetter(Carbon $date): string
    {
        // 0=Dim, 1=Lun ...
        $map = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];
        return $map[$date->dayOfWeek];
    }
}