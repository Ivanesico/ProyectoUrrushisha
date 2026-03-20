<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


// Usan sesion y no requieren estar logeado
Route::prefix('auth')->middleware('web')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Usan sesion y requieren estar logeado
Route::prefix('auth')->middleware(['web', 'auth'])->group(function () {
    Route::get('me', [AuthController::class, 'me']);
});
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('flavors', [FlavorController::class, 'index']);
});

