<?php

use App\Http\Controllers\Ai\AiTaskController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Der Generator ist der Arbeitsplatz — dort startet man, nicht auf einem
    // Dashboard.
    Route::redirect('dashboard', '/generator')->name('dashboard');

    // Bereiche der folgenden Phasen; die Navigation soll schon jetzt stimmen.
    Route::inertia('generator', 'generator/index')->name('generator.index');
    Route::inertia('queue', 'queue/index')->name('queue.index');
    Route::inertia('applications', 'applications/index')->name('applications.index');
    Route::inertia('statistics', 'statistics/index')->name('statistics.index');

    // Der Stand eines Hintergrund-Aufrufs; das Frontend fragt ihn ab.
    Route::get('ai-tasks/{aiTask}', [AiTaskController::class, 'show'])->name('ai-tasks.show');
});

require __DIR__.'/profile.php';
require __DIR__.'/settings.php';
