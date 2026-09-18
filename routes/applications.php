<?php

use App\Http\Controllers\ApplicationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Bewerbungen
|--------------------------------------------------------------------------
|
| Der Tracker. Bewerbungen entstehen beim Generieren; von Hand angelegt
| werden nur die, die außerhalb des Tools entstanden sind.
|
*/

Route::middleware(['auth', 'verified'])->prefix('applications')->name('applications.')->group(function () {
    Route::get('/', [ApplicationController::class, 'index'])->name('index');
    Route::post('/', [ApplicationController::class, 'store'])->name('store');
    Route::get('{application}', [ApplicationController::class, 'show'])->name('show');
    Route::patch('{application}', [ApplicationController::class, 'update'])->name('update');
    Route::delete('{application}', [ApplicationController::class, 'destroy'])->name('destroy');
    Route::post('{application}/stage', [ApplicationController::class, 'stage'])->name('stage');
    Route::post('{application}/reactivate', [ApplicationController::class, 'reactivate'])->name('reactivate');
});
