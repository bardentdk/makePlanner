<?php

namespace App\Http\Controllers;

use App\Models\Planning;
use App\Models\PlanningPhase;
use App\Services\PlanningGeneratorService;
use App\Actions\Planning\ExportPlanningXlsxAction;
use App\Http\Requests\UpdatePlanningRequest;
use Illuminate\Http\Request;
use App\Http\Requests\StorePlanningRequest;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class PlanningController extends Controller
{
    public function index()
    {
        // On récupère les plannings paginés (10 par page), triés par nouveauté
        $plannings = Planning::query()
            ->select('id', 'title', 'learner_name', 'start_date', 'end_date', 'created_at')
            ->orderByDesc('created_at')
            ->paginate(10);

        return Inertia::render('Plannings/Index', [
            'plannings' => $plannings
        ]);
    }
    public function create()
    {
        return Inertia::render('Plannings/Create');
    }
    public function edit(Planning $planning)
    {
        // On charge les phases pour pré-remplir le formulaire
        $planning->load('phases');

        return Inertia::render('Plannings/Edit', [
            'planning' => $planning
        ]);
    }

    /**
     * Met à jour le planning et ses phases
     */
    public function update(UpdatePlanningRequest $request, Planning $planning)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $planning) {
            // 1. Mise à jour des infos principales
            $planning->update([
                'title' => $data['title'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'default_hours' => $data['default_hours'],
            ]);

            // 2. Gestion des phases : Stratégie "Replace All"
            // On supprime tout ce qui existait pour éviter les conflits d'ID
            $planning->phases()->delete();

            // On recrée les nouvelles phases reçues du formulaire
            if (!empty($data['phases'])) {
                $planning->phases()->createMany($data['phases']);
            }
        });

        return to_route('plannings.show', $planning)
            ->with('success', 'Planning mis à jour avec succès.');
    }

    /**
     * Supprime le planning
     */
    public function destroy(Planning $planning)
    {
        $planning->delete();

        return to_route('plannings.index')
            ->with('success', 'Planning supprimé.');
    }
    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'title' => 'required|string',
    //         'learner_name' => 'nullable|string',
    //         'start_date' => 'required|date',
    //         'end_date' => 'required|date|after:start_date',
    //         'default_hours' => 'required|integer',
    //         'phases' => 'array',
    //         'phases.*.name' => 'required|string',
    //         'phases.*.start_date' => 'required|date',
    //         'phases.*.end_date' => 'required|date',
    //         'phases.*.priority' => 'integer',
    //     ]);

    //     $planning = Planning::create([
    //         'title' => $validated['title'],
    //         'learner_name' => $validated['learner_name'],
    //         'start_date' => $validated['start_date'],
    //         'end_date' => $validated['end_date'],
    //         'default_hours' => $validated['default_hours'],
    //     ]);

    //     foreach ($validated['phases'] ?? [] as $phase) {
    //         $planning->phases()->create($phase);
    //     }

    //     return redirect()->route('plannings.show', $planning);
    // }
    public function store(StorePlanningRequest $request)
    {
        // 1. Récupération des données validées & propres
        $data = $request->validated();

        // 2. On utilise une transaction pour garantir l'intégrité des données
        $planning = DB::transaction(function () use ($data) {
            
            // On isole les phases et on les retire du tableau principal
            // car la table 'plannings' ne connait pas la colonne 'phases'
            $phases = $data['phases'] ?? [];
            unset($data['phases']);

            // Création du Planning
            $planning = Planning::create($data);

            // Création des Phases (createMany est plus performant qu'une boucle)
            if (!empty($phases)) {
                $planning->phases()->createMany($phases);
            }

            return $planning;
        });

        // 3. Redirection (Laravel 9+ syntaxe courte)
        return to_route('plannings.show', $planning);
    }
    public function show(Planning $planning, PlanningGeneratorService $service)
    {
        $grid = $service->generateGrid($planning);
        
        return Inertia::render('Plannings/Show', [
            'planning' => $planning->load('phases'),
            'grid' => $grid
        ]);
    }

    // public function export(Planning $planning, PlanningGeneratorService $service)
    // {
    //     $fileName = sprintf(
    //         'Planning_%s_%s_%s_%s.xlsx',
    //         \Str::slug($planning->learner_name ?? 'Apprenant'),
    //         $planning->id,
    //         $planning->start_date->format('Ymd'),
    //         $planning->end_date->format('Ymd')
    //     );

    //     return Excel::download(
    //         new ExportPlanningXlsxAction($planning, $service), 
    //         $fileName
    //     );
    // }
    public function export(Planning $planning, PlanningGeneratorService $service)
    {
        // NOMMAGE : Planning_TITRE-FORMATION_YYYY-MM-DD.xlsx
        $fileName = sprintf(
            'Planning_%s_%s.xlsx',
            Str::slug($planning->title), // On utilise le titre de la formation
            $planning->start_date->format('Y-m-d')
        );

        return Excel::download(
            new ExportPlanningXlsxAction($planning, $service), 
            $fileName
        );
    }
    public function downloadPdf(Planning $planning, PlanningGeneratorService $service)
    {
        $grid = $service->generateGrid($planning);

        $pdf = Pdf::loadView('pdf.planning', [
            'planning' => $planning,
            'grid' => $grid,
            'phases' => $planning->phases->sortBy('priority'),
        ])->setPaper('a3', 'landscape'); // A3 recommandé pour tout faire tenir

        // NOMMAGE : Planning_TITRE-FORMATION_YYYY-MM-DD.pdf
        $fileName = sprintf(
            'Planning_%s_%s.pdf',
            Str::slug($planning->title), // On utilise le titre de la formation
            $planning->start_date->format('Y-m-d')
        );

        return $pdf->stream($fileName);
    }
}