<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Planning extends Model
{
    protected $fillable = [
        'title',
        'learner_name',
        'start_date',
        'end_date',
        'heures_centre',        // <--- Ajouté
        'heures_stage', // <--- Ajouté
        'default_hours',
        'rules'
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d', // Format strict pour l'input date HTML5
        'end_date'   => 'date:Y-m-d', // Format strict pour l'input date HTML5
        'rules'      => 'array',
    ];

    public function phases(): HasMany
    {
        return $this->hasMany(PlanningPhase::class);
    }
}