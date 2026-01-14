<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    // Autorise l'insertion de toutes les données
    protected $guarded = [];

    // --- C'EST ICI LA CORRECTION ---
    // On dit à Laravel : "La colonne scheduling_rules est un tableau,
    // transforme-la en JSON quand tu enregistres, et en Array quand tu lis."
    protected $casts = [
        'scheduling_rules' => 'array',
    ];
}