<?php

use App\Http\Controllers\PlanningController;
use App\Http\Controllers\TrainingController;

Route::get('/', function () { return redirect('/plannings/create'); });

Route::get('/plannings/create', [PlanningController::class, 'create'])->name('plannings.create');
Route::post('/plannings', [PlanningController::class, 'store'])->name('plannings.store');
Route::get('/plannings/{planning}', [PlanningController::class, 'show'])->name('plannings.show');
Route::get('/plannings/{planning}/export', [PlanningController::class, 'export'])->name('plannings.export');
Route::get('/plannings/{planning}/pdf', [PlanningController::class, 'downloadPdf'])->name('plannings.pdf');
// Page liste (Index)
Route::get('/plannings', [PlanningController::class, 'index'])->name('plannings.index');

// Modification
Route::get('/plannings/{planning}/edit', [PlanningController::class, 'edit'])->name('plannings.edit');
Route::put('/plannings/{planning}', [PlanningController::class, 'update'])->name('plannings.update');

// Suppression
Route::delete('/plannings/{planning}', [PlanningController::class, 'destroy'])->name('plannings.destroy');
// Ressources de formations
Route::resource('trainings', TrainingController::class);