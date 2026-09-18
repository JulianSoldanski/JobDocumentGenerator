<?php

use App\Http\Controllers\Ai\AiTaskController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\StatisticsController;
use Illuminate\Support\Facades\Route;

// Keine Startseite: Wer angemeldet ist, landet im Generator, alle anderen
// leitet die Anmeldung dorthin weiter.
Route::redirect('/', '/generator')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Der Generator ist der Arbeitsplatz — dort startet man, nicht auf einem
    // Dashboard.
    Route::redirect('dashboard', '/generator')->name('dashboard');

    Route::get('statistics', [StatisticsController::class, 'index'])->name('statistics.index');

    // Der Stand eines Hintergrund-Aufrufs; das Frontend fragt ihn ab.
    Route::get('ai-tasks/{aiTask}', [AiTaskController::class, 'show'])->name('ai-tasks.show');

    Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::patch('documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
    Route::get('documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
});

require __DIR__.'/applications.php';
require __DIR__.'/generator.php';
require __DIR__.'/profile.php';
require __DIR__.'/queue.php';
require __DIR__.'/settings.php';
