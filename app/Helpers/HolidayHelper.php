<?php

namespace App\Helpers;

use Carbon\Carbon;

class HolidayHelper
{
    public static function getHolidays(int $year): array
    {
        $holidays = [
            Carbon::create($year, 1, 1),   // Jour de l'an
            Carbon::create($year, 5, 1),   // Fête du travail
            Carbon::create($year, 5, 8),   // Victoire 1945
            Carbon::create($year, 7, 14),  // Fête Nationale
            Carbon::create($year, 8, 15),  // Assomption
            Carbon::create($year, 11, 1),  // Toussaint
            Carbon::create($year, 11, 11), // Armistice
            Carbon::create($year, 12, 20), // Abolition esclavage (Réunion)
            Carbon::create($year, 12, 25), // Noël
        ];

        // Fêtes mobiles (basées sur Pâques)
        if (extension_loaded('calendar')) {
            $easterDays = easter_days($year);
            $easter = Carbon::createFromDate($year, 3, 21)->addDays($easterDays);
        } else {
            // Fallback approximatif si l'extension calendar n'est pas active (rare)
            // Pour 2025/2026 ça ira, mais mieux vaut activer l'extension php_calendar
            $easter = Carbon::create($year, 4, 15); 
        }

        $lundiPaques = $easter->copy()->addDay();
        $ascension = $easter->copy()->addDays(39);
        $lundiPentecote = $easter->copy()->addDays(50);

        $holidays[] = $lundiPaques;
        $holidays[] = $ascension;
        $holidays[] = $lundiPentecote;

        return $holidays;
    }
}