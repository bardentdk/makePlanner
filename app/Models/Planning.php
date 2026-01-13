<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Planning extends Model
{
    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'rules' => 'array',
    ];

    public function phases(): HasMany
    {
        return $this->hasMany(PlanningPhase::class);
    }
}