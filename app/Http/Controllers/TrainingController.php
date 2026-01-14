<?php

namespace App\Http\Controllers;

use App\Models\Training;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrainingController extends Controller
{
    public function index()
    {
        return Inertia::render('Trainings/Index', [
            'trainings' => Training::orderBy('title')->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('Trainings/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'duration_hours' => 'required|integer|min:1',
            'internship_weeks' => 'required|integer|min:0',
            'internship_periods' => 'required|integer|min:1|max:4',
            
            // Validation des champs dynamiques
            'first_stage_delay' => 'required|numeric|min:0',
            'gaps' => 'array',
            'gaps.*' => 'numeric|min:0',
        ]);

        // Construction du JSON de règles
        $rules = [
            'stages_count' => (int)$request->internship_periods,
            'first_stage_delay_months' => (float)$request->first_stage_delay,
            'gaps_between_stages_months' => $request->gaps ?? []
        ];

        Training::create([
            'title' => $validated['title'],
            'duration_hours' => $validated['duration_hours'],
            'internship_weeks' => $validated['internship_weeks'],
            'internship_periods' => $validated['internship_periods'],
            'scheduling_rules' => $rules, // Sauvegarde du JSON
        ]);

        return to_route('trainings.index')->with('success', 'Formation ajoutée.');
    }

    public function edit(Training $training)
    {
        return Inertia::render('Trainings/Edit', [
            'training' => $training
        ]);
    }

    public function update(Request $request, Training $training)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'duration_hours' => 'required|integer|min:1',
            'internship_weeks' => 'required|integer|min:0',
            'internship_periods' => 'required|integer|min:1|max:4',
            'first_stage_delay' => 'required|numeric|min:0',
            'gaps' => 'array',
            'gaps.*' => 'numeric|min:0',
        ]);

        $rules = [
            'stages_count' => (int)$request->internship_periods,
            'first_stage_delay_months' => (float)$request->first_stage_delay,
            'gaps_between_stages_months' => $request->gaps ?? []
        ];

        $training->update([
            'title' => $validated['title'],
            'duration_hours' => $validated['duration_hours'],
            'internship_weeks' => $validated['internship_weeks'],
            'internship_periods' => $validated['internship_periods'],
            'scheduling_rules' => $rules,
        ]);

        return to_route('trainings.index')->with('success', 'Formation mise à jour.');
    }

    public function destroy(Training $training)
    {
        $training->delete();
        return to_route('trainings.index')->with('success', 'Formation supprimée.');
    }
}