<?php

namespace App\Services;

use App\Helpers\HolidayHelper;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AutoSchedulerService
{
    public function calculatePlanning(Carbon $startDate, int $centerHours, int $totalInternshipWeeks, ?array $rules = null): array
    {
        $phases = [];
        
        // --- 1. CONFIGURATION ---
        $rules = $rules ?? [];
        $stagesCount = $rules['stages_count'] ?? 1;
        $firstDelayMonths = $rules['first_stage_delay_months'] ?? 3.5;
        $gaps = $rules['gaps_between_stages_months'] ?? [];

        $weeksPerStage = $totalInternshipWeeks / $stagesCount;

        // --- 2. STAGES ---
        $weeksDelay = $firstDelayMonths * 4.33; 
        $currentStageStart = $startDate->copy()->addWeeks($weeksDelay);
        if ($currentStageStart->isWeekend()) $currentStageStart->next(Carbon::MONDAY);
        
        $firstStageDateForSearch = $currentStageStart->copy();

        for ($i = 0; $i < $stagesCount; $i++) {
            $currentStageEnd = $currentStageStart->copy()->addWeeks($weeksPerStage)->subDay();
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

            if ($i < $stagesCount - 1) {
                $gapMonths = $gaps[$i] ?? 1.5;
                $currentStageStart = $currentStageEnd->copy()->addWeeks($gapMonths * 4.33);
                if ($currentStageStart->isWeekend()) $currentStageStart->next(Carbon::MONDAY);
            }
        }

        // --- 3. CALCUL FIN ---
        $totalHours = $centerHours + ($totalInternshipWeeks * 35);
        $totalWeeks = ceil($totalHours / 35);
        $totalGapMonths = array_sum($gaps);
        $totalWeeks += ($totalGapMonths * 4.33);

        $endDate = $startDate->copy()->addWeeks($totalWeeks);
        
        // Ajustement Noël
        $periodCheck = CarbonPeriod::create($startDate, $endDate);
        foreach ($periodCheck as $date) {
            if ($date->month == 12 && $date->day == 25) {
                $endDate->addWeeks(2);
                break;
            }
        }
        if (!$endDate->isFriday()) $endDate->next(Carbon::FRIDAY);


        // --- 4. PHASES SPÉCIALES ---

        // A. Fermeture Noël
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

        // B. Révisions
        $revisionEnd = $endDate->copy();
        $revisionStart = $endDate->copy()->subWeeks(2)->startOfWeek(); 
        $phases[] = [
            'name' => 'Révisions',
            'code' => 'R',
            'start_date' => $revisionStart->format('Y-m-d'),
            'end_date' => $revisionEnd->format('Y-m-d'),
            'hours_per_day' => 7,
            'color' => '#bbf7d0',
            'priority' => 40,
        ];

        // C. Recherche de Stage
        $searchPeriod = CarbonPeriod::create($startDate, $firstStageDateForSearch->subDay());
        foreach ($searchPeriod as $date) {
            if ($date->isMonday() && !$date->isSameDay($startDate)) {
                $phases[] = [
                    'name' => 'Recherche de stage',
                    'code' => 'RS',
                    'start_date' => $date->format('Y-m-d'),
                    'end_date' => $date->format('Y-m-d'),
                    'hours_per_day' => 7,
                    'color' => '#fb923c',
                    'priority' => 45,
                ];
            }
        }

        // D. Jours Fériés (NOUVEAU)
        $yearsToCheck = range($startDate->year, $endDate->year);
        foreach ($yearsToCheck as $year) {
            $holidays = HolidayHelper::getHolidays($year);
            foreach ($holidays as $holiday) {
                if ($holiday->between($startDate, $endDate)) {
                    // On ne crée pas de phase fériée si c'est un WE (le générateur gérera le blanc)
                    if ($holiday->isWeekend()) continue;

                    $phases[] = [
                        'name' => 'Férié',
                        'code' => 'F',
                        'start_date' => $holiday->format('Y-m-d'),
                        'end_date' => $holiday->format('Y-m-d'),
                        'hours_per_day' => 0,
                        'color' => '#ef4444', // Rouge
                        'priority' => 80, // > Stage (50)
                    ];
                }
            }
        }

        return [
            'end_date' => $endDate->format('Y-m-d'),
            'phases' => $phases
        ];
    }
}