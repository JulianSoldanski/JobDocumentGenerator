<?php

use App\Http\Controllers\Profile\AiAccessController;
use App\Http\Controllers\Profile\ContactController;
use App\Http\Controllers\Profile\CvImportController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Profile\ProfileEntryController;
use App\Http\Controllers\Profile\ProjectController;
use App\Http\Controllers\Profile\ProjectDraftController;
use App\Http\Controllers\Profile\StyleAnalysisController;
use App\Http\Controllers\Profile\WritingStyleController;
use App\Http\Middleware\RequireAiAccess;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Profil
|--------------------------------------------------------------------------
|
| Die Datenbasis: einmal gepflegt, immer wieder verwendet. Jeder Abschnitt hat
| eine eigene Adresse.
|
*/

Route::middleware(['auth', 'verified'])->prefix('profile')->name('profile.')->group(function () {
    Route::redirect('/', '/profile/experience');

    Route::get('experience', [ProfileController::class, 'experience'])->name('experience');
    Route::get('education', [ProfileController::class, 'education'])->name('education');
    Route::get('skills', [ProfileController::class, 'skills'])->name('skills');
    Route::get('projects', [ProfileController::class, 'projects'])->name('projects');
    Route::get('style', [ProfileController::class, 'style'])->name('style');
    Route::get('contact', [ProfileController::class, 'contact'])->name('contact');
    Route::get('import', [CvImportController::class, 'page'])->name('import');
    Route::get('ai', [AiAccessController::class, 'edit'])->name('ai');

    Route::post('entries', [ProfileEntryController::class, 'store'])->name('entries.store');
    Route::post('entries/reorder', [ProfileEntryController::class, 'reorder'])->name('entries.reorder');
    Route::patch('entries/{entry}', [ProfileEntryController::class, 'update'])->name('entries.update');
    Route::patch('entries/{entry}/visibility', [ProfileEntryController::class, 'visibility'])->name('entries.visibility');
    Route::delete('entries/{entry}', [ProfileEntryController::class, 'destroy'])->name('entries.destroy');

    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::post('projects/reorder', [ProjectController::class, 'reorder'])->name('projects.reorder');
    Route::patch('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::patch('projects/{project}/flags', [ProjectController::class, 'flags'])->name('projects.flags');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    Route::put('contact', [ContactController::class, 'update'])->name('contact.update');
    Route::put('style', [WritingStyleController::class, 'update'])->name('style.update');
    Route::post('import/apply', [CvImportController::class, 'apply'])->name('import.apply');

    Route::put('ai', [AiAccessController::class, 'update'])->name('ai.update');
    Route::delete('ai', [AiAccessController::class, 'destroy'])->name('ai.destroy');

    // KI-Aufrufe: sie kosten Geld, deshalb gedrosselt — und ohne hinterlegten
    // Schlüssel gar nicht erst gestartet.
    Route::middleware(['throttle:ai', RequireAiAccess::class])->group(function () {
        Route::post('ai/test', [AiAccessController::class, 'test'])->name('ai.test');
        Route::post('style/analyze', [StyleAnalysisController::class, 'store'])->name('style.analyze');
        Route::post('projects/draft', [ProjectDraftController::class, 'store'])->name('projects.draft');
        Route::post('import', [CvImportController::class, 'store'])->name('import.store');
    });
});
