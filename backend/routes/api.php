<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ContrasenaController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\JugadorController;
use App\Http\Controllers\SaludController;
use App\Http\Controllers\SesionController;
use Illuminate\Support\Facades\Route;

Route::get('/salud', SaludController::class);

Route::post('/sesion', [SesionController::class, 'iniciar']);

Route::middleware('auth:web')->group(function () {
    Route::delete('/sesion', [SesionController::class, 'cerrar']);
    Route::put('/contrasena', [ContrasenaController::class, 'cambiar']);

    Route::get('/jugadores', [JugadorController::class, 'listar']);
    Route::get('/jugadores/busqueda', [JugadorController::class, 'buscar']);
    Route::get('/jugadores/{jugador}', [JugadorController::class, 'ver'])->whereUuid('jugador');

    Route::middleware('rol:entrenador')->group(function () {
        Route::get('/categorias', [CategoriaController::class, 'listar']);
        Route::post('/categorias', [CategoriaController::class, 'crear']);
        Route::delete('/categorias/{id}', [CategoriaController::class, 'eliminar'])->whereUuid('id');

        Route::get('/equipos', [EquipoController::class, 'listar']);
        Route::post('/equipos', [EquipoController::class, 'crear']);

        Route::post('/jugadores', [JugadorController::class, 'crear']);
    });
});
