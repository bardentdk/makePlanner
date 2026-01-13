<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // MVP : tout le monde peut créer
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            // 'learner_name' => SUPPRIMÉ
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'default_hours' => ['required', 'integer', 'min:0', 'max:12'],
            'phases' => ['array'],
            'phases.*.name' => ['required', 'string'],
            'phases.*.start_date' => ['required', 'date'],
            'phases.*.end_date' => ['required', 'date', 'after_or_equal:phases.*.start_date'],
            'phases.*.hours_per_day' => ['required', 'numeric'],
            'phases.*.priority' => ['required', 'integer'],
            'phases.*.color' => ['required', 'string'], 
            'phases.*.code' => ['nullable', 'string', 'max:5'],
        ];
    }
    
    // Custom messages si besoin pour le français
    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'La date de fin doit être après le début.',
            'phases.*.color.regex' => 'Couleur invalide (format hex requis).',
        ];
    }
}