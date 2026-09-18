<?php

use App\Http\Controllers\Generator\ExtractFieldsController;
use App\Http\Controllers\Generator\GenerateController;
use App\Http\Controllers\Generator\GeneratorController;
use App\Http\Controllers\Generator\JobPostingController;
use App\Http\Controllers\Generator\JobSummaryController;
use App\Http\Controllers\Generator\SessionController;
use App\Http\Middleware\RequireAiAccess;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Generator
|--------------------------------------------------------------------------
|
| Der Arbeitsplatz für genau eine Stelle: links die Anzeige, rechts das
| Dokument.
|
*/

Route::middleware(['auth', 'verified'])->prefix('generator')->name('generator.')->group(function () {
    Route::get('/', [GeneratorController::class, 'index'])->name('index');
    Route::post('/', [GeneratorController::class, 'store'])->name('store');
    Route::get('{session}', [GeneratorController::class, 'show'])->name('show');
    Route::patch('{session}', [SessionController::class, 'update'])->name('update');

    // Eine fremde Seite zu laden kostet zwar keine Tokens, aber Zeit und
    // Bandbreite — und einen Server als Abrufmaschine will man nicht.
    Route::post('{session}/posting', [JobPostingController::class, 'fetch'])
        ->middleware('throttle:20,1')
        ->name('posting');

    Route::middleware(['throttle:ai', RequireAiAccess::class])->group(function () {
        Route::post('{session}/fields', [ExtractFieldsController::class, 'store'])->name('fields');
        Route::post('{session}/summary', [JobSummaryController::class, 'store'])->name('summary');
        Route::post('{session}/generate', [GenerateController::class, 'store'])->name('generate');
    });
});
