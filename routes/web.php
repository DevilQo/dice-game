<?php

use Illuminate\Support\Facades\Route;

//Route::get('/', function () {
//    return view('welcome');
//});
use App\Http\Controllers\GameController;

Route::middleware(['auth', 'ensure-dice'])->group(function () {
    Route::get('/dice', [GameController::class, 'showDice'])->name('show-dice');
    Route::post('/roll-dice', [GameController::class, 'rollDice'])->name('roll-dice');
});
