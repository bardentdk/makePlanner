<?php

namespace App\Services;

use Yasumi\Yasumi;
use Yasumi\Provider\France; // On cible la France

class HolidayService
{
    /**
     * Vérifie si une date est fériée et retourne son nom, ou null.
     */
    public function getHolidayName(string $dateStr): ?string
    {
        try {
            $date = new \DateTime($dateStr);
            $year = (int)$date->format('Y');

            // Optimisation : On pourrait mettre en cache l'instance par année
            // Mais pour un MVP, instancier Yasumi est assez rapide.
            $holidays = Yasumi::create('France', $year);

            if ($holidays->isHoliday($date)) {
                // On récupère l'objet holiday pour avoir le nom traduit
                foreach ($holidays->getHolidays() as $holiday) {
                    if ($holiday->format('Y-m-d') === $dateStr) {
                        return $holiday->getName(); // Ex: "Assomption"
                    }
                }
            }
        } catch (\Exception $e) {
            // Gestion erreur (ex: année hors scope supporté par Yasumi)
            return null;
        }

        return null;
    }

    /**
     * Retourne true si c'est un jour férié
     */
    public function isHoliday(string $dateStr): bool
    {
        return $this->getHolidayName($dateStr) !== null;
    }
}