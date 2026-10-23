<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ContrasenaController;
use App\Http\Controllers\SaludController;
use App\Http\Controllers\SesionController;
use Illuminate\Support\Facades\Route;

Route::get('/salud', SaludController::class);

Route::post('/sesion', [SesionController::class, 'iniciar']);

Route::middleware('auth:web')->group(function () {
    Route::delete('/sesion', [SesionController::class, 'cerrar']);
    Route::put('/contrasena', [ContrasenaController::class, 'cambiar']);

    Route::middleware('rol:entrenador')->group(function () {
        Route::get('/categorias', [CategoriaController::class, 'listar']);
        Route::post('/categorias', [CategoriaController::class, 'crear']);
        Route::delete('/categorias/{id}', [CategoriaController::class, 'eliminar'])->whereUuid('id');
    });
});
