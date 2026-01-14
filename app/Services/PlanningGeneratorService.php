<?php

namespace App\Services;

use App\Models\Planning;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class PlanningGeneratorService
{
    public function generateGrid(Planning $planning): array
    {
        $grid = [];
        $period = CarbonPeriod::create($planning->start_date, $planning->end_date);
        
        // Tri décroissant des priorités
        $phases = $planning->phases->sortByDesc('priority');

        foreach ($period as $date) {
            $monthKey = $date->format('Y-m');

            if (!isset($grid[$monthKey])) {
                $grid[$monthKey] = [
                    'month_label' => mb_strtoupper($date->translatedFormat('F Y')),
                    'days' => []
                ];
            }

            // --- RÈGLE ABSOLUE : WEEK-END = VIDE & GRIS CLAIR ---
            if ($date->isWeekend()) {
                $dayData = [
                    'date' => $date->copy(),
                    'dayLetter' => mb_substr($date->translatedFormat('D'), 0, 1),
                    'content' => '', // Toujours vide
                    'color' => '#F2F2F2', // <--- GRIS CLAIR ICI (au lieu de #FFFFFF)
                    'type' => 'weekend'
                ];
            }
            else {
                // Gestion des phases pour la semaine
                $activePhase = null;
                foreach ($phases as $phase) {
                    $pStart = Carbon::parse($phase->start_date);
                    $pEnd = Carbon::parse($phase->end_date);

                    if ($date->between($pStart, $pEnd)) {
                        $activePhase = $phase;
                        break;
                    }
                }

                if ($activePhase) {
                    $dayData = [
                        'date' => $date->copy(),
                        'dayLetter' => mb_substr($date->translatedFormat('D'), 0, 1),
                        'content' => $activePhase->code,
                        'color' => $activePhase->color,
                        'type' => 'phase'
                    ];
                } else {
                    $dayData = [
                        'date' => $date->copy(),
                        'dayLetter' => mb_substr($date->translatedFormat('D'), 0, 1),
                        'content' => $planning->default_hours,
                        'color' => '#FFFFFF',
                        'type' => 'standard'
                    ];
                }
            }

            $grid[$monthKey]['days'][] = (object)$dayData;
        }

        return $grid;
    }
}