<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ContrasenaController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\EquipoJugadorController;
use App\Http\Controllers\JugadorController;
use App\Http\Controllers\PeriodoEscolarController;
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

    Route::middleware('rol:docente')->group(function () {
        Route::get('/periodos-escolares', [PeriodoEscolarController::class, 'listar']);
        Route::post('/periodos-escolares', [PeriodoEscolarController::class, 'crear']);
        Route::delete('/periodos-escolares/{id}', [PeriodoEscolarController::class, 'eliminar'])->whereUuid('id');
    });

    Route::middleware('rol:entrenador')->group(function () {
        Route::get('/categorias', [CategoriaController::class, 'listar']);
        Route::post('/categorias', [CategoriaController::class, 'crear']);
        Route::delete('/categorias/{id}', [CategoriaController::class, 'eliminar'])->whereUuid('id');

        Route::get('/equipos', [EquipoController::class, 'listar']);
        Route::post('/equipos', [EquipoController::class, 'crear']);

        Route::post('/equipos/{equipo}/jugadores', [EquipoJugadorController::class, 'asignar'])->whereUuid('equipo');
        Route::patch('/equipos/{equipo}/jugadores/{jugador}', [EquipoJugadorController::class, 'cambiarPosicion'])->whereUuid(['equipo', 'jugador']);

        Route::post('/jugadores', [JugadorController::class, 'crear']);
        Route::patch('/jugadores/{jugador}', [JugadorController::class, 'actualizar'])->whereUuid('jugador');
        Route::post('/jugadores/{jugador}/baja', [JugadorController::class, 'darDeBaja'])->whereUuid('jugador');
    });
});
