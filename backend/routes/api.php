<?php

use App\Http\Controllers\ContrasenaController;
use App\Http\Controllers\SaludController;
use App\Http\Controllers\SesionController;
use Illuminate\Support\Facades\Route;

Route::get('/salud', SaludController::class);

Route::post('/sesion', [SesionController::class, 'iniciar']);

Route::middleware('auth:web')->group(function () {
    Route::delete('/sesion', [SesionController::class, 'cerrar']);
    Route::put('/contrasena', [ContrasenaController::class, 'cambiar']);
});
