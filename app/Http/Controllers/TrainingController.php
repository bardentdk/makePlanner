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
        ]);

        Training::create($validated);

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
        ]);

        $training->update($validated);

        return to_route('trainings.index')->with('success', 'Formation mise à jour.');
    }

    public function destroy(Training $training)
    {
        $training->delete();
        return to_route('trainings.index')->with('success', 'Formation supprimée.');
    }
}