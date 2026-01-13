<?php

namespace App\Data;

use Carbon\Carbon;

class DayCellDTO
{
    public function __construct(
        public Carbon $date,
        public string $dayLetter, // L, M, M...
        public string|float $content, // 7, 4, "F", "S"
        public string $type, // formation, stage, ferie, weekend
        public string $color, // Hex code
        public string $label, // Nom de la phase active
        public bool $isWorked,
    ) {}
}