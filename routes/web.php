<?php

use App\Http\Controllers\MapController;
use App\Http\Controllers\PinController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/map');
Route::get('/map', [MapController::class, 'index'])->name('map');
Route::get('/dashboard', fn () => redirect()->route('map'))->middleware('auth')->name('dashboard');

Route::get('/api/pins', [PinController::class, 'index']);
Route::get('/api/pins/{pin}', [PinController::class, 'show']);

Route::middleware('auth')->group(function () {
    Route::post('/api/pins', [PinController::class, 'store']);
    Route::post('/api/pins/{pin}/comments', [PinController::class, 'comment']);
    Route::post('/api/pins/{pin}/react', [PinController::class, 'react']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';