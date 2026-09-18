<?php

use App\Http\Controllers\CaptureController;
use App\Http\Controllers\QueueController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Queue
|--------------------------------------------------------------------------
|
| Stellen sammeln, bevor sie bearbeitet werden — per Bookmarklet von jeder
| Seite aus oder von Hand.
|
*/

// Das Bookmarklet meldet sich über das Token im Link an, nicht über die Sitzung.
Route::get('capture', CaptureController::class)->middleware('throttle:60,1')->name('queue.capture');

Route::middleware(['auth', 'verified'])->prefix('queue')->name('queue.')->group(function () {
    Route::get('/', [QueueController::class, 'index'])->name('index');
    Route::post('/', [QueueController::class, 'store'])->name('store');
    Route::post('token', [QueueController::class, 'token'])->name('token');
    Route::patch('{item}', [QueueController::class, 'update'])->name('update');
    Route::delete('{item}', [QueueController::class, 'destroy'])->name('destroy');
    Route::post('{item}/generate', [QueueController::class, 'generate'])->name('generate');
});
