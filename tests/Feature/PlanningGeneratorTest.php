<?php

use App\Models\Planning;
use App\Services\HolidayService;
use App\Services\PlanningGeneratorService;
use Carbon\Carbon;

it('generates a grid with holidays and priorities correctly', function () {
    // 1. Mock du HolidayService pour contrôler le test (éviter appel API externe ou calculs date)
    // On dit : "Le 2024-05-01 est férié"
    $holidayMock = Mockery::mock(HolidayService::class);
    $holidayMock->shouldReceive('getHolidayName')
        ->with('2024-05-01')->andReturn('Fête du travail');
    $holidayMock->shouldReceive('getHolidayName')
        ->with(Mockery::any())->andReturnNull();

    // 2. Création du Service avec le Mock
    $service = new PlanningGeneratorService($holidayMock);

    // 3. Création Données (En mémoire, pas besoin de DB si on passait des objets, 
    // mais ici nos services attendent des Models Eloquent, donc on utilise la factory ou create)
    // Note: Pour un test unitaire pur, on éviterait la DB, mais en Laravel Feature c'est ok.
    
    $planning = Planning::create([
        'title' => 'Test Planning',
        'start_date' => '2024-04-29', // Lundi
        'end_date' => '2024-05-03',   // Vendredi
        'default_hours' => 7
    ]);

    // Cas A : Ajout d'une phase "Stage" (Prio 20) qui chevauche le Férié
    $planning->phases()->create([
        'name' => 'Stage',
        'start_date' => '2024-05-01',
        'end_date' => '2024-05-02',
        'priority' => 20, // Faible
        'code' => 'S',
        'color' => '#FFFF00'
    ]);

    // 4. Exécution
    $grid = $service->generateGrid($planning);
    $days = $grid['2024-05']['days'];

    // 5. Assertions
    
    // 29 Avril (Lundi) -> Formation standard
    expect($days[0]->content)->toBe(7); 
    
    // 01 Mai (Mercredi) -> C'est Férié ET Stage. 
    // Règle : Férié (prio ~50) > Stage (prio 20). Donc ça doit rester "F".
    // Le code dans le service dit : if ($cell->type === 'ferie' && $phase->priority < 50) => phase ne gagne pas.
    expect($days[2]->date->format('Y-m-d'))->toBe('2024-05-01');
    expect($days[2]->content)->toBe('F'); 
    expect($days[2]->type)->toBe('ferie');

    // 02 Mai (Jeudi) -> Stage uniquement
    expect($days[3]->content)->toBe('S');
    expect($days[3]->label)->toBe('Stage');

});

it('allows closure to overwrite holidays', function () {
    // Cas B : Fermeture Centre (Prio 100) sur un jour férié
    
    $holidayMock = Mockery::mock(HolidayService::class);
    $holidayMock->shouldReceive('getHolidayName')->andReturn('Noël'); // Disons 25 Déc

    $service = new PlanningGeneratorService($holidayMock);

    $planning = Planning::create([
        'title' => 'Noel Test',
        'start_date' => '2024-12-25',
        'end_date' => '2024-12-25',
        'default_hours' => 7
    ]);

    $planning->phases()->create([
        'name' => 'Fermeture Totale',
        'start_date' => '2024-12-25',
        'end_date' => '2024-12-25',
        'priority' => 100, // Très haute
        'code' => 'FC',
        'color' => '#000000'
    ]);

    $grid = $service->generateGrid($planning);
    $day = $grid['2024-12']['days'][0];

    // Ici Fermeture > Férié
    expect($day->content)->toBe('FC');
    expect($day->label)->toBe('Fermeture Totale');
});