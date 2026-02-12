<?php

namespace App\Helpers;

use Carbon\Carbon;

class HolidayHelper
{
    public static function getHolidays(int $year): array
    {
        // Fériés fixes
        $holidays = [
            Carbon::create($year, 1, 1),   // Jour de l'an
            Carbon::create($year, 5, 1),   // Fête du travail
            Carbon::create($year, 5, 8),   // Victoire 1945
            Carbon::create($year, 7, 14),  // Fête nationale
            Carbon::create($year, 8, 15),  // Assomption
            Carbon::create($year, 11, 1),  // Toussaint
            Carbon::create($year, 11, 11), // Armistice
            Carbon::create($year, 12, 25), // Noël
        ];

        // Fériés mobiles (Basés sur Pâques)
        $easter = Carbon::createFromFormat('U', easter_date($year));
        
        $holidays[] = $easter->copy()->addDay();        // Lundi de Pâques (+1j)
        $holidays[] = $easter->copy()->addDays(39);     // Ascension (+39j)
        $holidays[] = $easter->copy()->addDays(50);     // Lundi de Pentecôte (+50j)

        return $holidays;
    }

    public static function isHoliday(Carbon $date): bool
    {
        $holidays = self::getHolidays($date->year);
        foreach ($holidays as $holiday) {
            if ($holiday->isSameDay($date)) {
                return true;
            }
        }
        return false;
    }
}